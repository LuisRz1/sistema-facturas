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

        $clientes = DB::table('cliente')->orderBy('razon_social')->get(['id_cliente', 'razon_social', 'ruc']);
        $usuarios = DB::table('usuario')->whereNotNull('celular')->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'celular', 'correo']);

        return view('facturas.index', [
            'facturas'   => $facturasCollection,
            'clientes'   => $clientes,
            'usuarios'   => $usuarios,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
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
     *
     * Lógica de estados SIN "POR VALIDAR DETRACCION":
     *   - DETRACCION registrada pero no validada           → PENDIENTE
     *   - DETRACCION validada + sin abono + saldo > 0     → DIFERENCIA PENDIENTE
     *   - DETRACCION validada + sin abono + saldo = 0     → PAGADA  (raro, caso de autodet)
     *   - AUTODETRACCION + cubre todo                     → PAGADA
     *   - Sin abono                                       → PENDIENTE / VENCIDO
     *   - Con abono parcial                               → PAGO PARCIAL
     *   - Abono total                                     → PAGADA
     */
    public function procesarPago(Request $request, $id): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'monto_abonado'         => 'nullable|numeric|min:0',  // Realmente opcional: puede ser 0, null o vacío
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

        // AUTODETRACCION: NO recalcular monto_abonado
        // El usuario solo puede editar fecha de depósito, los valores (recaudación, abono) vienen del frontend disabled
        // Usar los valores tal como se envían del frontend
        
        // Para otros tipos: si hay recaudación, usar ese valor
        // (los cálculos de abono se hacen en frontend en tiempo real)

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
            (bool) ($validated['validar_detraccion'] ?? false)
        );

        // IMPORTANTE: Si el estado final es PENDIENTE o VENCIDO, el monto_pendiente debe ser el importe total
        // porque aún NO se ha procesado ningún pago
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

    /**
     * Calcular estado — sin POR VALIDAR DETRACCION.
     *
     * AUTODETRACCION con recaudación → DIFERENCIA PENDIENTE (no calcula abono implícito)
     * DETRACCION validada + saldo → DIFERENCIA PENDIENTE
     * DETRACCION validada + sin saldo → PAGADA
     */
    private function calcularEstado(
        Factura $factura, float $montoAbonado, float $montoPendiente,
        float $totalRecaudacion, ?string $tipoRecaudacion, bool $validarDetraccion
    ): string {
        // AUTODETRACCION con recaudación → DIFERENCIA PENDIENTE (sin calcular abono implícito)
        if ($tipoRecaudacion === 'AUTODETRACCION' && $totalRecaudacion > 0) {
            if ($montoPendiente <= 0) return 'PAGADA';
            return 'DIFERENCIA PENDIENTE';
        }

        // AUTODETRACCION sin recaudación → PENDIENTE (sin cambios)
        if ($tipoRecaudacion === 'AUTODETRACCION') return 'PENDIENTE';

        // DETRACCION validada → determinar si queda diferencia
        if ($tipoRecaudacion === 'DETRACCION' && $validarDetraccion) {
            if ($montoPendiente <= 0) return 'PAGADA';
            // Queda saldo después de la detracción → DIFERENCIA PENDIENTE
            return 'DIFERENCIA PENDIENTE';
        }

        // DETRACCION no validada → simplemente PENDIENTE (ya no existe POR VALIDAR)
        // (el campo tipo_recaudacion='DETRACCION' en la BD indica que hay detracción pendiente de validar)

        // Sin abono → PENDIENTE o VENCIDO
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
