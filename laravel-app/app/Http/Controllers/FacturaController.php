<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\PagoFactura;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FacturaController extends Controller
{
    /** Estados que siguen pendientes de cobro */
    private const ESTADOS_PENDIENTES = ['PENDIENTE', 'VENCIDO', 'DIFERENCIA PENDIENTE'];

    public function index(Request $request): View
    {
        $fechaDesde = $request->input('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $routeName = (string) optional($request->route())->getName();

        $tipoClienteVista = null;
        if ($routeName === 'facturas.pj') {
            $tipoClienteVista = 'PERSONA JURIDICA';
        } elseif ($routeName === 'facturas.pn') {
            $tipoClienteVista = 'PERSONA NATURAL';
        }

        $facturasRoute = in_array($routeName, ['facturas.pj', 'facturas.pn'], true)
            ? $routeName
            : 'facturas.index';

        $selects = [
            'f.id_factura', 'f.serie', 'f.numero',
            'f.fecha_emision', 'f.fecha_vencimiento',
            'f.moneda', 'f.importe_total', 'f.monto_igv',
            'f.monto_abonado', 'f.monto_pendiente', 'f.estado',
            'f.tipo_recaudacion', 'f.glosa', 'f.forma_pago',
            'f.usuario_creacion',
            'c.id_cliente', 'c.razon_social', 'c.ruc',
            'c.correo as cliente_correo', 'c.celular as cliente_celular',
            'u.nombre as usuario_nombre', 'u.apellido as usuario_apellido',
            'rec.total_recaudacion as monto_recaudacion',
            'rec.porcentaje as porcentaje_recaudacion',
            'rec.fecha_recaudacion',
            DB::raw('NULL as ruta_comprobante_pago'),
        ];

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'f.usuario_creacion')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->where('f.activo', 1)
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->when($tipoClienteVista, function ($q) use ($tipoClienteVista) {
                $q->where('c.tipo_cliente', $tipoClienteVista);
            })
            ->select($selects)
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.numero')
            ->get();

        // Cargar notificaciones en bloque (evita N+1 de 2 queries por factura)
        $allFacturaIds = $query->pluck('id_factura')->toArray();

        $notifWaMap = DB::table('notificacion_factura')
            ->whereIn('id_factura', $allFacturaIds)
            ->where('canal', 'WHATSAPP')
            ->orderByDesc('id_notificacion')
            ->get()
            ->groupBy('id_factura')
            ->map->first();

        $notifCorreoMap = DB::table('notificacion_factura')
            ->whereIn('id_factura', $allFacturaIds)
            ->where('canal', 'CORREO')
            ->orderByDesc('id_notificacion')
            ->get()
            ->groupBy('id_factura')
            ->map->first();

        $facturasCollection = collect($query->map(function ($f) use ($notifWaMap, $notifCorreoMap) {
            return (object) array_merge((array) $f, [
                'comprobante_url' => $this->resolveComprobanteUrl($f->ruta_comprobante_pago ?? null),
                'cliente' => (object) [
                    'id_cliente'   => $f->id_cliente,
                    'razon_social' => $f->razon_social,
                    'ruc'          => $f->ruc,
                    'correo'       => $f->cliente_correo,
                    'celular'      => $f->cliente_celular,
                ],
                'ultima_notif_wa'     => $notifWaMap->get($f->id_factura),
                'ultima_notif_correo' => $notifCorreoMap->get($f->id_factura),
            ]);
        }));

        $facturaIds    = $allFacturaIds;
        $creditosPorId = DB::table('credito')
            ->whereIn('id_factura', $facturaIds)
            ->where('activo', 1)
            ->get()
            ->keyBy('id_factura');

        // Verificar facturas huérfanas en bloque (evita N+1 por crédito)
        $orphanFacturaIds = [];
        if ($creditosPorId->isNotEmpty()) {
            $existingPairs = DB::table('factura')
                ->where('activo', 1)
                ->get(['serie', 'numero'])
                ->mapWithKeys(fn($r) => [$r->serie . '|' . $r->numero => true])
                ->all();

            foreach ($creditosPorId as $idFactura => $credito) {
                $key = $credito->serie_doc_modificado . '|' . $credito->numero_doc_modificado;
                if (!isset($existingPairs[$key])) {
                    $orphanFacturaIds[] = (int) $idFactura;
                }
            }
        }

        // Pre-cargar créditos de facturas ANULADAS en bloque (evita N+1 en reject)
        $anuladoIds = $facturasCollection->where('estado', 'ANULADO')->pluck('id_factura')->all();
        $anuladosConCredito = [];
        if (!empty($anuladoIds)) {
            $anuladosConCredito = array_flip(
                DB::table('credito')
                    ->whereIn('id_factura', $anuladoIds)
                    ->where('activo', 1)
                    ->pluck('id_factura')
                    ->all()
            );
        }

        $facturasParaTotales = $facturasCollection->reject(function ($f) use ($orphanFacturaIds, $anuladosConCredito) {
            if (in_array((int) $f->id_factura, $orphanFacturaIds)) {
                return true;
            }
            if ($f->estado === 'ANULADO') {
                return !isset($anuladosConCredito[$f->id_factura]);
            }
            return false;
        });

        $clientes = DB::table('cliente')
            ->when($tipoClienteVista, function ($q) use ($tipoClienteVista) {
                $q->where('tipo_cliente', $tipoClienteVista);
            })
            ->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);
        $usuarios = DB::table('usuario')->whereNotNull('celular')->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'celular', 'correo']);

        // Historial de importaciones para el acordeón
        $sincronizaciones = DB::table('sincronizacion_nubefact as sn')
            ->select([
                'sn.id_sincronizacion',
                'sn.nombre_archivo',
                'sn.fecha_inicio',
                'sn.fecha_fin',
                'sn.estado',
                'sn.total_registros_procesados',
                'sn.activo',
            ])
            ->orderByDesc('sn.fecha_inicio')
            ->limit(50)
            ->get();

        return view('facturas.index', [
            'facturas'            => $facturasCollection,
            'facturasParaTotales' => $facturasParaTotales,
            'orphanFacturaIds'    => $orphanFacturaIds,
            'clientes'            => $clientes,
            'usuarios'            => $usuarios,
            'fechaDesde'          => $fechaDesde,
            'fechaHasta'          => $fechaHasta,
            'tipoClienteVista'    => $tipoClienteVista,
            'facturasRoute'       => $facturasRoute,
            'sincronizaciones'    => $sincronizaciones,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_cliente'        => 'required|integer|exists:cliente,id_cliente',
            'serie'             => 'required|string|max:10',
            'numero'            => 'required|integer|min:1',
            'moneda'            => 'required|in:PEN,USD',
            'tipo_operacion'    => 'nullable|string|max:100',
            'fecha_emision'     => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'subtotal_gravado'  => 'required|numeric|min:0',
            'monto_igv'         => 'required|numeric|min:0',
            'importe_total'     => 'required|numeric|min:0',
            'glosa'             => 'nullable|string|max:500',
            'forma_pago'        => 'nullable|string|max:100',
            'estado'            => 'nullable|in:PENDIENTE,VENCIDO,PAGADA,DIFERENCIA PENDIENTE,POR VALIDAR DETRACCION',
            'usuario_creacion'  => 'nullable|string|max:100',
        ]);

        // Check for duplicate serie+numero per client
        $existe = DB::table('factura')
            ->where('serie', strtoupper(trim($validated['serie'])))
            ->where('numero', (int) $validated['numero'])
            ->where('id_cliente', $validated['id_cliente'])
            ->exists();
        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => "Ya existe la factura {$validated['serie']}-{$validated['numero']} para este cliente.",
            ], 422);
        }

        $estado = $validated['estado'] ?? 'PENDIENTE';
        $now    = now();
        $id = DB::table('factura')->insertGetId([
            'id_cliente'          => $validated['id_cliente'],
            'id_usuario'          => auth()->id(),
            'serie'               => strtoupper(trim($validated['serie'])),
            'numero'              => (int) $validated['numero'],
            'moneda'              => $validated['moneda'],
            'tipo_operacion'      => $validated['tipo_operacion'] ?? null,
            'fecha_emision'       => $validated['fecha_emision'],
            'fecha_vencimiento'   => $validated['fecha_vencimiento'] ?? null,
            'subtotal_gravado'    => $validated['subtotal_gravado'],
            'monto_igv'           => $validated['monto_igv'],
            'importe_total'       => $validated['importe_total'],
            'monto_abonado'       => 0,
            'monto_pendiente'     => $validated['importe_total'],
            'glosa'               => $validated['glosa'] ?? null,
            'forma_pago'          => $validated['forma_pago'] ?? null,
            'estado'              => $estado,
            'activo'              => 1,
            'usuario_creacion'    => $validated['usuario_creacion'] ?? null,
            'fecha_creacion'      => $now,
            'fecha_actualizacion' => $now,
        ]);
        $num = strtoupper(trim($validated['serie'])) . '-' . str_pad($validated['numero'], 8, '0', STR_PAD_LEFT);
        return response()->json(['success' => true, 'id_factura' => $id, 'message' => "Factura {$num} creada correctamente."]);
    }

    public function edit($id): JsonResponse
    {
        $factura = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->select([
                'f.id_factura','f.serie','f.numero','f.fecha_emision',
                'f.fecha_vencimiento','f.moneda',
                'f.subtotal_gravado','f.monto_igv','f.importe_total',
                'f.monto_abonado','f.monto_pendiente',
                'f.estado','f.glosa','f.forma_pago','f.tipo_recaudacion',
                'c.razon_social','c.ruc',
                'rec.total_recaudacion','rec.porcentaje',
            ])
            ->where('f.id_factura', $id)
            ->first();

        if (!$factura) return response()->json(['error' => 'Factura no encontrada'], 404);
        return response()->json($factura);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'fecha_emision'    => 'nullable|date',
            'fecha_vencimiento'=> 'nullable|date',
            'glosa'            => 'nullable|string',
            'forma_pago'       => 'nullable|string',
            'estado'           => 'nullable|in:PENDIENTE,VENCIDO,PAGADA,DIFERENCIA PENDIENTE',
            'importe_total'    => 'nullable|numeric',
            'monto_igv'        => 'nullable|numeric',
            'subtotal_gravado' => 'nullable|numeric',
        ]);

        $factura->update($validated);
        $num = $factura->serie . '-' . str_pad($factura->numero, 8, '0', STR_PAD_LEFT);

        // Flash para resaltar la última factura editada al recargar
        session()->flash('last_edited_factura_id', $id);

        return response()->json([
            'success'        => true,
            'message'        => "Factura {$num} actualizada correctamente.",
            'factura_num'    => $num,
            'factura'        => $factura,
            'last_edited_id' => $id,
        ]);
    }

    /**
     * Procesar pago / abono — inserta en pago_factura y recalcula totales.
     */
    public function procesarPago(Request $request, $id): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'monto_pagado'           => 'nullable|numeric|min:0',
            'fecha_pago'             => 'nullable|date',
            'cuenta_pago'            => 'nullable|string|max:50',
            'numero_operacion'       => 'nullable|string|max:100',
            'banco_origen'           => 'nullable|string|max:100',
            'forma_pago_abono'       => 'nullable|string|max:50',
            'observacion'            => 'nullable|string',
            'comprobante'            => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            // Recaudación (nivel factura)
            'total_recaudacion'      => 'nullable|numeric|min:0',
            'porcentaje_recaudacion' => 'nullable|numeric|min:0|max:100',
            'tipo_recaudacion'       => 'nullable|string|in:DETRACCION,AUTODETRACCION,RETENCION',
            'validar_detraccion'     => 'nullable|boolean',
            'fecha_recaudacion'      => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $montoPagado = round((float) ($validated['monto_pagado'] ?? 0), 2);

            // Insertar nuevo abono si el monto es mayor que cero
            if ($montoPagado > 0) {
                $rutaComprobante = null;
                if ($request->hasFile('comprobante')) {
                    $rutaComprobante = $request->file('comprobante')
                        ->store("facturas/comprobantes/{$id}", 's3');
                    if (!$rutaComprobante) {
                        throw new \RuntimeException('No se pudo subir el comprobante.');
                    }
                }

                DB::table('pago_factura')->insert([
                    'id_factura'            => $id,
                    'monto_pagado'          => $montoPagado,
                    'fecha_pago'            => $validated['fecha_pago'] ?? now()->toDateString(),
                    'cuenta_pago'           => $validated['cuenta_pago'] ?? null,
                    'ruta_comprobante_pago' => $rutaComprobante,
                    'numero_operacion'      => $validated['numero_operacion'] ?? null,
                    'banco_origen'          => $validated['banco_origen'] ?? null,
                    'forma_pago'            => $validated['forma_pago_abono'] ?? null,
                    'observacion'           => $validated['observacion'] ?? null,
                    'activo'                => 1,
                    'fecha_creacion'        => now(),
                ]);
            }

            // Recaudación (nivel factura)
            $totalRecaudacion = round((float) ($validated['total_recaudacion'] ?? 0), 2);
            $tipoRecaudacion  = $validated['tipo_recaudacion'] ?? $factura->tipo_recaudacion;
            $porcentaje       = $validated['porcentaje_recaudacion'] ?? null;
            $fechaRecaudacion = $validated['fecha_recaudacion'] ?? null;

            if ($tipoRecaudacion && $totalRecaudacion > 0) {
                DB::table('recaudacion')->updateOrInsert(
                    ['id_factura' => $id],
                    [
                        'porcentaje'        => $porcentaje ?? 0,
                        'total_recaudacion' => $totalRecaudacion,
                        'fecha_recaudacion' => $fechaRecaudacion,
                    ]
                );
            } elseif (!$tipoRecaudacion && $totalRecaudacion == 0) {
                DB::table('recaudacion')->where('id_factura', $id)->delete();
                $totalRecaudacion = 0;
            }

            // Recalcular monto_abonado sumando todos los pagos activos
            $montoAbonadoTotal = round(
                (float) DB::table('pago_factura')
                    ->where('id_factura', $id)
                    ->where('activo', 1)
                    ->sum('monto_pagado'),
                2
            );

            $importeTotal   = round((float) $factura->importe_total, 2);
            $montoPendiente = round(max(0, $importeTotal - $montoAbonadoTotal - $totalRecaudacion), 2);

            $estado = $this->calcularEstado(
                $factura, $montoAbonadoTotal, $montoPendiente,
                $totalRecaudacion, $tipoRecaudacion,
                (bool) ($validated['validar_detraccion'] ?? false),
                $fechaRecaudacion
            );

            if (in_array($estado, ['PENDIENTE', 'VENCIDO'])) {
                $montoPendiente = $importeTotal;
            }

            $factura->update([
                'monto_abonado'      => $montoAbonadoTotal,
                'monto_pendiente'    => $montoPendiente,
                'tipo_recaudacion'   => $tipoRecaudacion,
                'estado'             => $estado,
                'fecha_actualizacion'=> now(),
            ]);

            DB::commit();

            session()->flash('last_edited_factura_id', $id);

            return response()->json([
                'success'         => true,
                'estado'          => $estado,
                'monto_abonado'   => $montoAbonadoTotal,
                'monto_pendiente' => $montoPendiente,
                'message'         => "Pago registrado. Estado: {$estado}",
                'last_edited_id'  => $id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Listar pagos de una factura.
     */
    public function listarPagos($id): JsonResponse
    {
        Factura::findOrFail($id);

        $pagos = DB::table('pago_factura')
            ->where('id_factura', $id)
            ->where('activo', 1)
            ->orderBy('fecha_pago')
            ->orderBy('id_pago')
            ->get();

        $pagosArray = $pagos->map(function ($p) {
            return [
                'id_pago'          => $p->id_pago,
                'monto_pagado'     => $p->monto_pagado,
                'fecha_pago'       => $p->fecha_pago,
                'cuenta_pago'      => $p->cuenta_pago,
                'numero_operacion' => $p->numero_operacion,
                'banco_origen'     => $p->banco_origen,
                'forma_pago'       => $p->forma_pago,
                'observacion'      => $p->observacion,
                'comprobante_url'  => $this->resolveComprobanteUrl($p->ruta_comprobante_pago ?? null),
            ];
        });

        return response()->json([
            'success'       => true,
            'pagos'         => $pagosArray,
            'monto_abonado' => (float) DB::table('pago_factura')->where('id_factura', $id)->where('activo', 1)->sum('monto_pagado'),
        ]);
    }

    /**
     * Eliminar (soft-delete) un pago y recalcular totales.
     */
    public function eliminarPago(Request $request, $id, $idPago): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $pago = DB::table('pago_factura')
            ->where('id_pago', $idPago)
            ->where('id_factura', $id)
            ->where('activo', 1)
            ->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('pago_factura')->where('id_pago', $idPago)->update([
                'activo'               => 0,
                'fecha_actualizacion'  => now(),
            ]);

            $recaudacion      = DB::table('recaudacion')->where('id_factura', $id)->first();
            $totalRecaudacion = round((float) ($recaudacion->total_recaudacion ?? 0), 2);
            $fechaRecaudacion = $recaudacion->fecha_recaudacion ?? null;

            $montoAbonadoTotal = round(
                (float) DB::table('pago_factura')
                    ->where('id_factura', $id)
                    ->where('activo', 1)
                    ->sum('monto_pagado'),
                2
            );

            $importeTotal   = round((float) $factura->importe_total, 2);
            $montoPendiente = round(max(0, $importeTotal - $montoAbonadoTotal - $totalRecaudacion), 2);

            $estado = $this->calcularEstado(
                $factura, $montoAbonadoTotal, $montoPendiente,
                $totalRecaudacion, $factura->tipo_recaudacion,
                false, $fechaRecaudacion
            );

            if (in_array($estado, ['PENDIENTE', 'VENCIDO'])) {
                $montoPendiente = $importeTotal;
            }

            $factura->update([
                'monto_abonado'      => $montoAbonadoTotal,
                'monto_pendiente'    => $montoPendiente,
                'estado'             => $estado,
                'fecha_actualizacion'=> now(),
            ]);

            DB::commit();

            return response()->json([
                'success'         => true,
                'monto_abonado'   => $montoAbonadoTotal,
                'monto_pendiente' => $montoPendiente,
                'estado'          => $estado,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Editar un abono existente y recalcular totales de la factura.
     */
    public function editarPago(Request $request, $id, $idPago): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $pago = DB::table('pago_factura')
            ->where('id_pago', $idPago)
            ->where('id_factura', $id)
            ->where('activo', 1)
            ->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        $validated = $request->validate([
            'monto_pagado'     => 'required|numeric|min:0.01',
            'fecha_pago'       => 'required|date',
            'cuenta_pago'      => 'nullable|string|max:100',
            'numero_operacion' => 'nullable|string|max:100',
            'banco_origen'     => 'nullable|string|max:100',
            'forma_pago'       => 'nullable|string|max:80',
            'observacion'      => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            DB::table('pago_factura')->where('id_pago', $idPago)->update([
                'monto_pagado'        => round((float) $validated['monto_pagado'], 2),
                'fecha_pago'          => $validated['fecha_pago'],
                'cuenta_pago'         => $validated['cuenta_pago'] ?? null,
                'numero_operacion'    => $validated['numero_operacion'] ?? null,
                'banco_origen'        => $validated['banco_origen'] ?? null,
                'forma_pago'          => $validated['forma_pago'] ?? null,
                'observacion'         => $validated['observacion'] ?? null,
                'fecha_actualizacion' => now(),
            ]);

            $recaudacion      = DB::table('recaudacion')->where('id_factura', $id)->first();
            $totalRecaudacion = round((float) ($recaudacion->total_recaudacion ?? 0), 2);
            $fechaRecaudacion = $recaudacion->fecha_recaudacion ?? null;

            $montoAbonadoTotal = round(
                (float) DB::table('pago_factura')
                    ->where('id_factura', $id)
                    ->where('activo', 1)
                    ->sum('monto_pagado'),
                2
            );

            $importeTotal   = round((float) $factura->importe_total, 2);
            $montoPendiente = round(max(0, $importeTotal - $montoAbonadoTotal - $totalRecaudacion), 2);

            $estado = $this->calcularEstado(
                $factura, $montoAbonadoTotal, $montoPendiente,
                $totalRecaudacion, $factura->tipo_recaudacion,
                false, $fechaRecaudacion
            );

            if (in_array($estado, ['PENDIENTE', 'VENCIDO'])) {
                $montoPendiente = $importeTotal;
            }

            $factura->update([
                'monto_abonado'       => $montoAbonadoTotal,
                'monto_pendiente'     => $montoPendiente,
                'estado'              => $estado,
                'fecha_actualizacion' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'         => true,
                'monto_abonado'   => $montoAbonadoTotal,
                'monto_pendiente' => $montoPendiente,
                'estado'          => $estado,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function facturasPendientesCliente(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_cliente'   => 'required|integer|exists:cliente,id_cliente',
            'fecha_desde'  => 'nullable|date',
            'fecha_hasta'  => 'nullable|date',
            'tipo_cliente' => 'nullable|string|in:PERSONA JURIDICA,PERSONA NATURAL',
        ]);

        $fechaDesde = $validated['fecha_desde'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaHasta = $validated['fecha_hasta'] ?? now()->format('Y-m-d');
        $tipoClienteVista = $validated['tipo_cliente'] ?? $this->getTipoClienteByRoute($request);

        $facturas = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->where('f.id_cliente', (int) $validated['id_cliente'])
            ->where('f.activo', 1)
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->whereIn('f.estado', ['PENDIENTE', 'VENCIDO', 'POR VALIDAR DETRACCION', 'DIFERENCIA PENDIENTE'])
            ->when($tipoClienteVista, function ($q) use ($tipoClienteVista) {
                $q->where('c.tipo_cliente', $tipoClienteVista);
            })
            ->select([
                'f.id_factura', 'f.serie', 'f.numero', 'f.moneda',
                'f.estado', 'f.fecha_emision', 'f.importe_total',
                'f.monto_abonado', 'f.monto_pendiente',
            ])
            ->orderBy('f.fecha_emision')
            ->orderBy('f.numero')
            ->get();

        return response()->json([
            'success'  => true,
            'facturas' => $facturas,
        ]);
    }

    public function procesarPagoMasivo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_cliente'   => 'required|integer|exists:cliente,id_cliente',
            'monto_total'  => 'required|numeric|min:0.01',
            'fecha_abono'  => 'required|date',
            'cuenta_pago'  => 'nullable|string|max:255',
            'detalles'     => 'required',
            'comprobante'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $detallesRaw = $validated['detalles'];
        $detalles = is_string($detallesRaw)
            ? json_decode($detallesRaw, true)
            : $detallesRaw;

        if (!is_array($detalles) || empty($detalles)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar al menos una factura para el pago masivo.',
            ], 422);
        }

        $detallesNorm = collect($detalles)
            ->map(function ($row) {
                return [
                    'id_factura' => (int) ($row['id_factura'] ?? 0),
                    'monto'      => round((float) ($row['monto'] ?? 0), 2),
                ];
            })
            ->filter(fn($row) => $row['id_factura'] > 0 && $row['monto'] > 0)
            ->values();

        if ($detallesNorm->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Los montos por factura deben ser mayores a cero.',
            ], 422);
        }

        $ids = $detallesNorm->pluck('id_factura');
        if ($ids->unique()->count() !== $ids->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Hay facturas repetidas en el detalle del pago masivo.',
            ], 422);
        }

        $toCents = fn(float $n): int => (int) round($n * 100);
        $montoTotal = round((float) $validated['monto_total'], 2);
        $sumDetalle = round((float) $detallesNorm->sum('monto'), 2);

        if ($toCents($montoTotal) !== $toCents($sumDetalle)) {
            return response()->json([
                'success' => false,
                'message' => 'La suma de facturas seleccionadas debe coincidir con el monto total abonado.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $resumenCambios = [];

            $facturas = Factura::whereIn('id_factura', $ids->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id_factura');

            if ($facturas->count() !== $ids->count()) {
                throw new \RuntimeException('Una o más facturas no existen o no están disponibles.');
            }

            $recaudMap = DB::table('recaudacion')
                ->whereIn('id_factura', $ids->all())
                ->get(['id_factura', 'total_recaudacion', 'fecha_recaudacion'])
                ->keyBy('id_factura');

            $rutaComprobanteMasivo = null;
            if ($request->hasFile('comprobante')) {
                $tmpPath = $request->file('comprobante')->store('facturas/comprobantes/masivo', 's3');
                if (!$tmpPath) {
                    throw new \RuntimeException('No se pudo subir el comprobante del pago masivo.');
                }
                $rutaComprobanteMasivo = $tmpPath;
            }

            foreach ($detallesNorm as $d) {
                /** @var Factura $factura */
                $factura = $facturas->get($d['id_factura']);
                if (!$factura) {
                    throw new \RuntimeException('Factura no encontrada en la operación masiva.');
                }

                if ((int) $factura->id_cliente !== (int) $validated['id_cliente']) {
                    throw new \RuntimeException('Todas las facturas seleccionadas deben pertenecer al mismo cliente.');
                }

                if (!in_array($factura->estado, ['PENDIENTE', 'VENCIDO', 'POR VALIDAR DETRACCION', 'DIFERENCIA PENDIENTE'], true)) {
                    throw new \RuntimeException("La factura {$factura->serie}-{$factura->numero} ya no está disponible para pago masivo.");
                }

                $pendienteAntes = round((float) $factura->monto_pendiente, 2);
                if ($toCents($d['monto']) > $toCents($pendienteAntes)) {
                    throw new \RuntimeException("El monto asignado supera el pendiente de la factura {$factura->serie}-{$factura->numero}.");
                }

                $estadoAntes  = (string) $factura->estado;
                $abonadoAntes = round((float) $factura->monto_abonado, 2);

                // Insertar el abono en pago_factura
                DB::table('pago_factura')->insert([
                    'id_factura'            => $factura->id_factura,
                    'monto_pagado'          => round((float) $d['monto'], 2),
                    'fecha_pago'            => $validated['fecha_abono'],
                    'cuenta_pago'           => $validated['cuenta_pago'] ?? null,
                    'ruta_comprobante_pago' => $rutaComprobanteMasivo,
                    'activo'                => 1,
                    'fecha_creacion'        => now(),
                ]);

                // Recalcular desde la suma real de pagos activos
                $montoAbonadoNuevo = round(
                    (float) DB::table('pago_factura')
                        ->where('id_factura', $factura->id_factura)
                        ->where('activo', 1)
                        ->sum('monto_pagado'),
                    2
                );
                $recaudacion      = (float) ($recaudMap[$factura->id_factura]->total_recaudacion ?? 0);
                $fechaRecaudacion = $recaudMap[$factura->id_factura]->fecha_recaudacion ?? null;
                $montoPendienteNuevo = round(max(0, (float) $factura->importe_total - $montoAbonadoNuevo - $recaudacion), 2);

                $estadoNuevo = $this->calcularEstado(
                    $factura,
                    $montoAbonadoNuevo,
                    $montoPendienteNuevo,
                    $recaudacion,
                    $factura->tipo_recaudacion,
                    false,
                    $fechaRecaudacion
                );

                $updateData = [
                    'monto_abonado'       => $montoAbonadoNuevo,
                    'monto_pendiente'     => $montoPendienteNuevo,
                    'estado'              => $estadoNuevo,
                    'fecha_actualizacion' => now(),
                ];

                $factura->update($updateData);

                $resumenCambios[] = [
                    'id_factura' => (int) $factura->id_factura,
                    'factura' => $factura->serie . '-' . str_pad((string) $factura->numero, 8, '0', STR_PAD_LEFT),
                    'monto_aplicado' => round((float) $d['monto'], 2),
                    'estado_anterior' => $estadoAntes,
                    'estado_nuevo' => $estadoNuevo,
                    'abonado_anterior' => $abonadoAntes,
                    'abonado_nuevo' => $montoAbonadoNuevo,
                    'pendiente_anterior' => $pendienteAntes,
                    'pendiente_nuevo' => $montoPendienteNuevo,
                ];
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Pago masivo registrado correctamente.',
                'facturas_actualizadas' => $detallesNorm->count(),
                'resumen' => $resumenCambios,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function getTipoClienteByRoute(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();
        if ($routeName === 'facturas.pj') {
            return 'PERSONA JURIDICA';
        }
        if ($routeName === 'facturas.pn') {
            return 'PERSONA NATURAL';
        }
        return null;
    }

    private function calcularEstado(
        Factura $factura, float $montoAbonado, float $montoPendiente,
        float $totalRecaudacion, ?string $tipoRecaudacion, bool $validarDetraccion,
        ?string $fechaRecaudacion
    ): string {
        // Regla principal solicitada: solo cuando pendiente es 0 pasa a PAGADA.
        if ($montoPendiente <= 0) return 'PAGADA';

        // Si existe abono y aun queda saldo, pasa a DIFERENCIA PENDIENTE.
        if ($montoAbonado > 0) return 'DIFERENCIA PENDIENTE';

        if ($tipoRecaudacion === 'RETENCION' && $totalRecaudacion > 0 && !empty($fechaRecaudacion)) {
            return 'DIFERENCIA PENDIENTE';
        }

        if ($tipoRecaudacion === 'AUTODETRACCION' && $totalRecaudacion > 0) {
            return 'DIFERENCIA PENDIENTE';
        }

        if ($tipoRecaudacion === 'AUTODETRACCION') return 'PENDIENTE';

        if ($tipoRecaudacion === 'DETRACCION' && $validarDetraccion) {
            return 'DIFERENCIA PENDIENTE';
        }

        if ($montoAbonado == 0) {
            if ($factura->fecha_vencimiento && $factura->fecha_vencimiento < now()->toDateString()) return 'VENCIDO';
            return 'PENDIENTE';
        }

        return 'PENDIENTE';
    }

    public function enviarReporteVencidosUsuario(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_usuario' => 'required|integer|exists:usuario,id_usuario',
            'tipo'       => 'required|in:vencidos,pendientes,todos',
            'fecha_desde'=> 'nullable|date',
            'fecha_hasta'=> 'nullable|date',
        ]);

        $usuario = DB::table('usuario')->where('id_usuario', $validated['id_usuario'])->first();
        if (!$usuario || !$usuario->celular) {
            return response()->json(['success' => false, 'error' => 'El usuario no tiene celular registrado.'], 422);
        }

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->where('f.activo', 1);

        if ($validated['tipo'] === 'vencidos') $query->where('f.estado', 'VENCIDO');
        else $query->whereIn('f.estado', self::ESTADOS_PENDIENTES);

        if (!empty($validated['fecha_desde'])) $query->where('f.fecha_emision', '>=', $validated['fecha_desde']);
        if (!empty($validated['fecha_hasta'])) $query->where('f.fecha_emision', '<=', $validated['fecha_hasta']);

        $facturas = $query->select([
            'f.serie','f.numero','f.importe_total','f.monto_pendiente',
            'f.estado','f.fecha_vencimiento','f.moneda','c.razon_social',
        ])->orderByDesc('f.fecha_vencimiento')->get();

        if ($facturas->isEmpty()) return response()->json(['success'=>false,'error'=>'No hay facturas para enviar.'],422);

        $totalDeuda = $facturas->sum('monto_pendiente');
        $total      = $facturas->count();
        $mensaje    = "*REPORTE PENDIENTES*\nConsorcio Rodriguez Caballero S.A.C.\n".now()->format('d/m/Y H:i')."\n\n━━━━━━━━━━━━━━━\n";
        foreach ($facturas->take(15) as $f) {
            $vcto = $f->fecha_vencimiento ? "Vcto: {$f->fecha_vencimiento}" : "Sin vcto";
            $pend = number_format($f->monto_pendiente ?? $f->importe_total, 2);
            $mensaje .= "*{$f->serie}-".str_pad($f->numero,8,'0',STR_PAD_LEFT)."*\n   {$f->razon_social}\n   Pendiente: {$f->moneda} {$pend} | {$vcto}\n";
        }
        if ($total > 15) $mensaje .= "... y ".($total-15)." más\n";
        $mensaje .= "━━━━━━━━━━━━━━━\n*Total: {$total} | Deuda: S/ ".number_format($totalDeuda,2)."*";

        $gateway   = app(\App\Services\WhatsAppGatewayService::class);
        $resultado = $gateway->enviar($usuario->celular, $mensaje);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok'] ? "Enviado a {$usuario->nombre}" : 'Error: '.($resultado['error']??''),
        ]);
    }

    public function obtenerCliente($id_factura): JsonResponse
    {
        $cliente = DB::table('factura as f')->join('cliente as c','c.id_cliente','=','f.id_cliente')
            ->select(['c.id_cliente','c.razon_social','c.ruc','c.celular','c.direccion_fiscal','c.correo','c.estado_contado'])
            ->where('f.id_factura',$id_factura)->first();
        if (!$cliente) return response()->json(['error'=>'Cliente no encontrado'],404);
        return response()->json($cliente);
    }

    public function actualizarCliente(Request $request, $id_factura): JsonResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id_factura);
        $cliente = $factura->cliente;
        $validated = $request->validate([
            'razon_social'    => 'required|string|max:200',
            'ruc'             => 'required|string|min:8|max:15|unique:cliente,ruc,'.$cliente->id_cliente.',id_cliente',
            'celular'         => 'nullable|string|max:15',
            'direccion_fiscal'=> 'nullable|string|max:250',
            'correo'          => 'nullable|email|max:150',
        ]);
        $validated['fecha_actualizacion'] = now();
        $tc = !empty($validated['celular']); $te = !empty($validated['correo']); $td = !empty($validated['direccion_fiscal']);
        $validated['estado_contado'] = ($tc&&$te&&$td)?'COMPLETO':(($tc||$te)?'INCOMPLETO':'SIN_DATOS');
        $cliente->update($validated);
        return response()->json(['success'=>true,'message'=>'Cliente actualizado correctamente','cliente'=>$cliente]);
    }

    public function uploadComprobante(Request $request, $id)
    {
        if (!Schema::hasColumn('factura', 'ruta_comprobante_pago')) {
            return response()->json([
                'success' => false,
                'message' => 'Falta la columna ruta_comprobante_pago en la tabla factura. Ejecuta la migracion correspondiente.'
            ], 422);
        }

        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'comprobante' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $path = $validated['comprobante']->store("facturas/comprobantes/{$id}", 's3');
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'No se pudo subir el comprobante a S3.'], 500);
        }

        $factura->update([
            'ruta_comprobante_pago' => $path,
            'fecha_actualizacion'   => now(),
        ]);

        $url = $this->resolveComprobanteUrl($path);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante subido correctamente.',
            'url'     => $url,
            'path'    => $path,
        ]);
    }

    private function resolveComprobanteUrl(?string $storedValue): ?string
    {
        if (!$storedValue) {
            return null;
        }

        $value = trim((string) $storedValue);
        if ($value === '') {
            return null;
        }

        $key = $value;
        if (preg_match('/^https?:\/\//i', $value)) {
            $parsedPath = parse_url($value, PHP_URL_PATH) ?? '';
            $key = ltrim($parsedPath, '/');

            $bucket = (string) config('filesystems.disks.s3.bucket');
            if ($bucket !== '' && str_starts_with($key, $bucket . '/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
        }

        $key = ltrim($key, '/');
        if ($key === '') {
            return null;
        }

        $disk = Storage::disk('s3');

        try {
            if (is_object($disk) && method_exists($disk, 'temporaryUrl')) {
                return call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes(60));
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }

        if (is_object($disk) && method_exists($disk, 'url')) {
            return call_user_func([$disk, 'url'], $key);
        }

        return null;
    }
}
