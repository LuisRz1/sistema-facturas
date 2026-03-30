<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FacturaController extends Controller
{
    /** Estados que siguen pendientes de cobro */
    private const ESTADOS_PENDIENTES = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE'];

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
            'f.fecha_emision', 'f.fecha_vencimiento', 'f.fecha_abono',
            'f.moneda', 'f.importe_total', 'f.monto_igv',
            'f.monto_abonado', 'f.monto_pendiente', 'f.estado',
            'f.tipo_recaudacion', 'f.glosa', 'f.forma_pago',
            'f.usuario_creacion', 'f.cuenta_pago',
            'c.id_cliente', 'c.razon_social', 'c.ruc',
            'c.correo as cliente_correo', 'c.celular as cliente_celular',
            'u.nombre as usuario_nombre', 'u.apellido as usuario_apellido',
            'rec.total_recaudacion as monto_recaudacion',
            'rec.porcentaje as porcentaje_recaudacion',
            'rec.fecha_recaudacion',
        ];

        if (Schema::hasColumn('factura', 'ruta_comprobante_pago')) {
            $selects[] = 'f.ruta_comprobante_pago';
        } else {
            $selects[] = DB::raw('NULL as ruta_comprobante_pago');
        }

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'f.usuario_creacion')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->when($tipoClienteVista, function ($q) use ($tipoClienteVista) {
                $q->where('c.tipo_cliente', $tipoClienteVista);
            })
            ->select($selects)
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.numero')
            ->get();

        $facturasCollection = collect($query->map(function ($f) {
            return (object) array_merge((array) $f, [
                'comprobante_url' => $this->resolveComprobanteUrl($f->ruta_comprobante_pago ?? null),
                'cliente' => (object) [
                    'id_cliente'   => $f->id_cliente,
                    'razon_social' => $f->razon_social,
                    'ruc'          => $f->ruc,
                    'correo'       => $f->cliente_correo,
                    'celular'      => $f->cliente_celular,
                ],
                'ultima_notif_wa' => DB::table('notificacion_factura')
                    ->where('id_factura', $f->id_factura)
                    ->where('canal', 'WHATSAPP')
                    ->orderByDesc('id_notificacion')
                    ->first(),
                'ultima_notif_correo' => DB::table('notificacion_factura')
                    ->where('id_factura', $f->id_factura)
                    ->where('canal', 'CORREO')
                    ->orderByDesc('id_notificacion')
                    ->first(),
            ]);
        }));

        $facturaIds    = $facturasCollection->pluck('id_factura')->toArray();
        $creditosPorId = DB::table('credito')
            ->whereIn('id_factura', $facturaIds)
            ->get()
            ->keyBy('id_factura');

        $orphanFacturaIds = [];
        foreach ($creditosPorId as $idFactura => $credito) {
            $existe = DB::table('factura')
                ->where('serie',  $credito->serie_doc_modificado)
                ->where('numero', $credito->numero_doc_modificado)
                ->exists();
            if (!$existe) {
                $orphanFacturaIds[] = (int) $idFactura;
            }
        }

        $facturasParaTotales = $facturasCollection->reject(function ($f) use ($orphanFacturaIds) {
            if (in_array((int) $f->id_factura, $orphanFacturaIds)) {
                return true;
            }
            if ($f->estado === 'ANULADO') {
                return !DB::table('credito')->where('id_factura', $f->id_factura)->exists();
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
        ]);
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
            'estado'           => 'nullable|in:PENDIENTE,VENCIDO,PAGADA,PAGO PARCIAL,DIFERENCIA PENDIENTE',
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
     * Procesar pago / abono.
     */
    public function procesarPago(Request $request, $id): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'monto_abonado'         => 'nullable|numeric|min:0',
            'total_recaudacion'     => 'nullable|numeric|min:0',
            'porcentaje_recaudacion'=> 'nullable|numeric|min:0|max:100',
            'tipo_recaudacion'      => 'nullable|string|in:DETRACCION,AUTODETRACCION,RETENCION',
            'validar_detraccion'    => 'nullable|boolean',
            'fecha_abono'           => 'nullable|date',
            'fecha_recaudacion'     => 'nullable|date',
            'cuenta_pago'           => 'nullable|string|max:255',
        ]);

        $montoAbonado     = round((float) ($validated['monto_abonado'] ?? 0), 2);
        $totalRecaudacion = round((float) ($validated['total_recaudacion'] ?? 0), 2);
        $tipoRecaudacion  = $validated['tipo_recaudacion'] ?? $factura->tipo_recaudacion;
        $porcentaje       = $validated['porcentaje_recaudacion'] ?? null;
        $fechaAbono       = $validated['fecha_abono'] ?? null;
        $fechaRecaudacion = $validated['fecha_recaudacion'] ?? null;
        $cuentaPago       = $validated['cuenta_pago'] ?? null;
        $importeTotal     = round((float) $factura->importe_total, 2);

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

        $montoPendiente = round(max(0, $importeTotal - $montoAbonado - $totalRecaudacion), 2);

        $estado = $this->calcularEstado(
            $factura, $montoAbonado, $montoPendiente,
            $totalRecaudacion, $tipoRecaudacion,
            (bool) ($validated['validar_detraccion'] ?? false),
            $fechaRecaudacion
        );

        if (in_array($estado, ['PENDIENTE', 'VENCIDO'])) {
            $montoPendiente = $importeTotal;
        }

        $factura->update([
            'monto_abonado'      => $montoAbonado,
            'monto_pendiente'    => $montoPendiente,
            'tipo_recaudacion'   => $tipoRecaudacion,
            'estado'             => $estado,
            'fecha_abono'        => $fechaAbono,
            'cuenta_pago'        => $cuentaPago,
            'fecha_actualizacion'=> now(),
        ]);

        // Flash para resaltar la última factura editada al recargar
        session()->flash('last_edited_factura_id', $id);

        return response()->json([
            'success'         => true,
            'estado'          => $estado,
            'monto_abonado'   => $montoAbonado,
            'monto_pendiente' => $montoPendiente,
            'message'         => "Pago procesado. Estado: {$estado}",
            'last_edited_id'  => $id,
        ]);
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
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->whereIn('f.estado', ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'POR VALIDAR DETRACCION', 'DIFERENCIA PENDIENTE'])
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

            $guardarRutaComprobante = $rutaComprobanteMasivo && Schema::hasColumn('factura', 'ruta_comprobante_pago');

            foreach ($detallesNorm as $d) {
                /** @var Factura $factura */
                $factura = $facturas->get($d['id_factura']);
                if (!$factura) {
                    throw new \RuntimeException('Factura no encontrada en la operación masiva.');
                }

                if ((int) $factura->id_cliente !== (int) $validated['id_cliente']) {
                    throw new \RuntimeException('Todas las facturas seleccionadas deben pertenecer al mismo cliente.');
                }

                if (!in_array($factura->estado, ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'POR VALIDAR DETRACCION', 'DIFERENCIA PENDIENTE'], true)) {
                    throw new \RuntimeException("La factura {$factura->serie}-{$factura->numero} ya no está disponible para pago masivo.");
                }

                $pendienteAntes = round((float) $factura->monto_pendiente, 2);
                if ($toCents($d['monto']) > $toCents($pendienteAntes)) {
                    throw new \RuntimeException("El monto asignado supera el pendiente de la factura {$factura->serie}-{$factura->numero}.");
                }

                $estadoAntes = (string) $factura->estado;
                $abonadoAntes = round((float) $factura->monto_abonado, 2);

                $montoAbonadoNuevo = round((float) $factura->monto_abonado + (float) $d['monto'], 2);
                $recaudacion = (float) ($recaudMap[$factura->id_factura]->total_recaudacion ?? 0);
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
                    'fecha_abono'         => $validated['fecha_abono'],
                    'cuenta_pago'         => $validated['cuenta_pago'] ?? null,
                    'fecha_actualizacion' => now(),
                ];

                if ($guardarRutaComprobante) {
                    $updateData['ruta_comprobante_pago'] = $rutaComprobanteMasivo;
                }

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

        // Si existe abono y aun queda saldo, debe quedar en PAGO PARCIAL.
        if ($montoAbonado > 0) return 'PAGO PARCIAL';

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
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura');

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
            'ruc'             => 'required|string|size:11|unique:cliente,ruc,'.$cliente->id_cliente.',id_cliente',
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
