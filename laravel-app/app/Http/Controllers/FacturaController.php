<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
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

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'f.usuario_creacion')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->select([
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
            ])
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.numero')
            ->get();

        $facturasCollection = collect($query->map(function ($f) {
            return (object) array_merge((array) $f, [
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

        // ── Pre-computar notas de crédito huérfanas ──────────────────────
        // Una nota de crédito es "huérfana" si tiene registro en `credito`
        // pero la factura a la que apunta (serie+numero) no existe en BD.
        // Estas notas aparecen tachadas en la vista y se excluyen de totales.
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

        // Facturas que cuentan para los totales:
        //   - excluir estado ANULADO sin registro en credito
        //   - excluir notas de crédito cuya factura enlazada no existe
        $facturasParaTotales = $facturasCollection->reject(function ($f) use ($orphanFacturaIds) {
            // Excluir si está en la lista de huérfanas
            if (in_array((int) $f->id_factura, $orphanFacturaIds)) {
                return true;
            }
            // Excluir ANULADO que no tiene registro en credito (no es NC ligada)
            if ($f->estado === 'ANULADO') {
                return !DB::table('credito')->where('id_factura', $f->id_factura)->exists();
            }
            return false;
        });

        $clientes = DB::table('cliente')->orderBy('razon_social')->get(['id_cliente', 'razon_social', 'ruc']);
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

        return response()->json([
            'success'     => true,
            'message'     => "Factura {$num} actualizada correctamente.",
            'factura_num' => $num,
            'factura'     => $factura,
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

        $montoAbonado     = (float) ($validated['monto_abonado'] ?? 0);
        $totalRecaudacion = (float) ($validated['total_recaudacion'] ?? 0);
        $tipoRecaudacion  = $validated['tipo_recaudacion'] ?? $factura->tipo_recaudacion;
        $porcentaje       = $validated['porcentaje_recaudacion'] ?? null;
        $fechaAbono       = $validated['fecha_abono'] ?? null;
        $fechaRecaudacion = $validated['fecha_recaudacion'] ?? null;
        $cuentaPago       = $validated['cuenta_pago'] ?? null;
        $importeTotal     = (float) $factura->importe_total;

        // Gestionar tabla recaudacion
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

        $montoPendiente = max(0, $importeTotal - $montoAbonado - $totalRecaudacion);

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

        return response()->json([
            'success'         => true,
            'estado'          => $estado,
            'monto_abonado'   => $montoAbonado,
            'monto_pendiente' => $montoPendiente,
            'message'         => "Pago procesado. Estado: {$estado}",
        ]);
    }

    private function calcularEstado(
        Factura $factura, float $montoAbonado, float $montoPendiente,
        float $totalRecaudacion, ?string $tipoRecaudacion, bool $validarDetraccion,
        ?string $fechaRecaudacion
    ): string {
        if ($tipoRecaudacion === 'RETENCION' && $totalRecaudacion > 0 && !empty($fechaRecaudacion)) {
            if ($montoPendiente <= 0) return 'PAGADA';
            return 'DIFERENCIA PENDIENTE';
        }

        if ($tipoRecaudacion === 'AUTODETRACCION' && $totalRecaudacion > 0) {
            if ($montoPendiente <= 0) return 'PAGADA';
            return 'DIFERENCIA PENDIENTE';
        }

        if ($tipoRecaudacion === 'AUTODETRACCION') return 'PENDIENTE';

        if ($tipoRecaudacion === 'DETRACCION' && $validarDetraccion) {
            if ($montoPendiente <= 0) return 'PAGADA';
            return 'DIFERENCIA PENDIENTE';
        }

        if ($montoAbonado == 0) {
            if ($factura->fecha_vencimiento && $factura->fecha_vencimiento < now()->toDateString()) return 'VENCIDO';
            return 'PENDIENTE';
        }

        if ($montoPendiente <= 0) return 'PAGADA';
        return 'PAGO PARCIAL';
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
        return response()->json(['success'=>false,'message'=>'La columna ruta_comprobante_pago no existe en la base de datos.'],422);
    }
}
