<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FacturaController extends Controller
{
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
                'f.id_factura',
                'f.serie',
                'f.numero',
                'f.fecha_emision',
                'f.fecha_vencimiento',
                'f.fecha_abono',
                'f.moneda',
                'f.importe_total',
                'f.monto_igv',
                'f.monto_abonado',
                'f.monto_pendiente',
                'f.estado',
                'f.tipo_recaudacion',
                'f.glosa',
                'f.forma_pago',
                'f.cuenta_pago',
                'f.usuario_creacion',
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
                'c.correo as cliente_correo',
                'c.celular as cliente_celular',
                'u.nombre as usuario_nombre',
                'u.apellido as usuario_apellido',
                'rec.total_recaudacion as monto_recaudacion',
                'rec.porcentaje as porcentaje_recaudacion',
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

        $clientes = DB::table('cliente')
            ->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);

        // Lista de usuarios con celular para enviar reporte
        $usuarios = DB::table('usuario')
            ->whereNotNull('celular')
            ->orderBy('nombre')
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

        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura);
    }

    public function update(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'fecha_emision'    => 'nullable|date',
            'fecha_vencimiento'=> 'nullable|date',
            'glosa'            => 'nullable|string',
            'forma_pago'       => 'nullable|string',
            'estado'           => 'nullable|in:PENDIENTE,VENCIDO,PAGADA,PAGO PARCIAL,POR VALIDAR DETRACCION',
            'importe_total'    => 'nullable|numeric',
            'monto_igv'        => 'nullable|numeric',
            'subtotal_gravado' => 'nullable|numeric',
            'monto_abonado'    => 'nullable|numeric|min:0',
            'monto_pendiente'  => 'nullable|numeric|min:0',
        ]);

        // Si se actualiza monto_abonado, recalcular monto_pendiente
        if ($request->filled('monto_abonado')) {
            $montoAbonado = floatval($validated['monto_abonado']);
            $importeTotal = floatval($validated['importe_total'] ?? $factura->importe_total);
            $validated['monto_pendiente'] = max(0, $importeTotal - $montoAbonado);
            
            // Actualizar fecha_abono si se registra un abono
            if ($montoAbonado > 0 && !$factura->fecha_abono) {
                $validated['fecha_abono'] = now()->toDateString();
            }
        }

        $validated['fecha_actualizacion'] = now();
        $factura->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Factura actualizada correctamente',
            'factura' => $factura,
        ]);
    }

    /**
     * Procesar pago / abono de una factura.
     * Actualiza monto_abonado, recaudación, y determina el nuevo estado.
     */
    public function procesarPago(Request $request, $id): JsonResponse
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'monto_abonado'         => 'required|numeric|min:0',
            'total_recaudacion'     => 'nullable|numeric|min:0',
            'porcentaje_recaudacion'=> 'nullable|numeric|min:0|max:100',
            'tipo_recaudacion'      => 'nullable|string|in:DETRACCION,AUTODETRACCION,RETENCION',
            'validar_detraccion'    => 'nullable|boolean',
            'fecha_abono'           => 'nullable|date',
            'cuenta_pago'           => 'nullable|string|max:255',
        ]);

        $montoAbonado      = (float) $validated['monto_abonado'];
        $totalRecaudacion  = (float) ($validated['total_recaudacion'] ?? 0);
        $tipoRecaudacion   = $validated['tipo_recaudacion'] ?? $factura->tipo_recaudacion;
        $porcentaje        = $validated['porcentaje_recaudacion'] ?? null;
        $fechaAbono        = $validated['fecha_abono'] ?? null;
        $cuentaPago        = $validated['cuenta_pago'] ?? null;
        $importeTotal      = (float) $factura->importe_total;

        // AUTODETRACCION: monto_abonado = importe_total - total_recaudacion
        // (el cliente paga la diferencia, la autodetracción cubre el resto)
        if ($tipoRecaudacion === 'AUTODETRACCION' && $totalRecaudacion > 0) {
            $montoAbonado = max(0, $importeTotal - $totalRecaudacion);
        }

        // Actualizar o crear recaudación si aplica
        if ($tipoRecaudacion && $totalRecaudacion > 0) {
            DB::table('recaudacion')->updateOrInsert(
                ['id_factura' => $id],
                [
                    'porcentaje'        => $porcentaje ?? 0,
                    'total_recaudacion' => $totalRecaudacion,
                ]
            );
        } elseif (!$tipoRecaudacion && $totalRecaudacion == 0) {
            // Solo borrar recaudación si explícitamente se quitó el tipo
            DB::table('recaudacion')->where('id_factura', $id)->delete();
            $totalRecaudacion = 0;
        }

        // Calcular monto pendiente
        $montoPendiente = max(0, $importeTotal - $montoAbonado - $totalRecaudacion);

        // Determinar nuevo estado
        $estado = $this->calcularEstado($factura, $montoAbonado, $montoPendiente, $tipoRecaudacion, $validated['validar_detraccion'] ?? false);

        // Actualizar factura
        $updateData = [
            'monto_abonado'      => $montoAbonado,
            'monto_pendiente'    => $montoPendiente,
            'tipo_recaudacion'   => $tipoRecaudacion,
            'estado'             => $estado,
            'fecha_abono'        => $fechaAbono,
            'cuenta_pago'        => $cuentaPago,
            'fecha_actualizacion'=> now(),
        ];

        $factura->update($updateData);

        return response()->json([
            'success'         => true,
            'estado'          => $estado,
            'monto_abonado'   => $montoAbonado,
            'monto_pendiente' => $montoPendiente,
            'message'         => 'Pago procesado correctamente',
        ]);
    }

    /**
     * Calcular el estado de la factura según los montos.
     */
    private function calcularEstado(Factura $factura, float $montoAbonado, float $montoPendiente, ?string $tipoRecaudacion, bool $validarDetraccion): string
    {
        // AUTODETRACCION: la recaudación actúa como abono total.
        // importe_total = monto_abonado + total_recaudacion → pendiente = 0 → PAGADA
        if ($tipoRecaudacion === 'AUTODETRACCION' && $montoPendiente <= 0) {
            return 'PAGADA';
        }

        // DETRACCION sin validar: mantener en POR VALIDAR DETRACCION
        if (
            $tipoRecaudacion === 'DETRACCION' &&
            !$validarDetraccion &&
            $montoAbonado == 0 &&
            in_array($factura->estado, ['POR VALIDAR DETRACCION', 'PENDIENTE'])
        ) {
            return 'POR VALIDAR DETRACCION';
        }

        // Sin abono → PENDIENTE o VENCIDO
        if ($montoAbonado == 0) {
            if ($factura->fecha_vencimiento && $factura->fecha_vencimiento < now()->toDateString()) {
                return 'VENCIDO';
            }
            return 'PENDIENTE';
        }

        // Con abono
        if ($montoPendiente <= 0) {
            return 'PAGADA';
        }

        return 'PAGO PARCIAL';
    }

    /**
     * Enviar reporte de vencidos/pendientes a un usuario por WhatsApp.
     */
    public function enviarReporteVencidosUsuario(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_usuario'   => 'required|integer|exists:usuario,id_usuario',
            'tipo'         => 'required|in:vencidos,pendientes,todos',
            'fecha_desde'  => 'nullable|date',
            'fecha_hasta'  => 'nullable|date',
        ]);

        $usuario = DB::table('usuario')->where('id_usuario', $validated['id_usuario'])->first();

        if (!$usuario || !$usuario->celular) {
            return response()->json(['success' => false, 'error' => 'El usuario no tiene celular registrado.'], 422);
        }

        // Construir query según tipo
        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura');

        if ($validated['tipo'] === 'vencidos') {
            $query->whereIn('f.estado', ['VENCIDO']);
        } elseif ($validated['tipo'] === 'pendientes') {
            $query->whereIn('f.estado', ['PENDIENTE', 'POR VALIDAR DETRACCION', 'PAGO PARCIAL']);
        } else {
            $query->whereIn('f.estado', ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'POR VALIDAR DETRACCION']);
        }

        if (!empty($validated['fecha_desde'])) {
            $query->where('f.fecha_emision', '>=', $validated['fecha_desde']);
        }
        if (!empty($validated['fecha_hasta'])) {
            $query->where('f.fecha_emision', '<=', $validated['fecha_hasta']);
        }

        $facturas = $query->select([
            'f.serie', 'f.numero', 'f.importe_total', 'f.monto_pendiente',
            'f.estado', 'f.fecha_vencimiento', 'f.moneda',
            'c.razon_social',
            'rec.total_recaudacion',
        ])->orderByDesc('f.fecha_vencimiento')->get();

        if ($facturas->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No hay facturas para enviar con los filtros seleccionados.'], 422);
        }

        // Construir mensaje
        $tipoLabel = match($validated['tipo']) {
            'vencidos'   => 'VENCIDAS',
            'pendientes' => 'PENDIENTES',
            default      => 'PENDIENTES/VENCIDAS',
        };

        $totalDeuda = $facturas->sum('monto_pendiente');
        $totalFacturas = $facturas->count();

        $mensaje = "*REPORTE DE FACTURAS {$tipoLabel}*\n";
        $mensaje .= "Consorcio Rodriguez Caballero S.A.C.\n";
        $mensaje .= "Generado: " . now()->format('d/m/Y H:i') . "\n\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━\n";

        $lineCount = 0;
        foreach ($facturas->take(15) as $f) {
            $vcto = $f->fecha_vencimiento ? "Vcto: {$f->fecha_vencimiento}" : "Sin vcto";
            $pendiente = number_format($f->monto_pendiente ?? $f->importe_total, 2);
            $mensaje .= "*{$f->serie}-" . str_pad($f->numero, 8, '0', STR_PAD_LEFT) . "*\n";
            $mensaje .= "   {$f->razon_social}\n";
            $mensaje .= "   Pendiente: {$f->moneda} {$pendiente} | {$vcto}\n";
            $lineCount++;
        }

        if ($totalFacturas > 15) {
            $mensaje .= "... y " . ($totalFacturas - 15) . " facturas más\n";
        }

        $mensaje .= "━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "*TOTAL: {$totalFacturas} facturas*\n";
        $mensaje .= "*Deuda total: S/ " . number_format($totalDeuda, 2) . "*";

        // Enviar por WhatsApp
        $gateway = app(\App\Services\WhatsAppGatewayService::class);
        $resultado = $gateway->enviar($usuario->celular, $mensaje);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok']
                ? "Reporte enviado a {$usuario->nombre} ({$usuario->celular})"
                : 'No se pudo enviar: ' . ($resultado['error'] ?? 'Error desconocido'),
        ]);
    }

    /**
     * Obtener datos del cliente para editar en modal.
     */
    public function obtenerCliente($id_factura): JsonResponse
    {
        $cliente = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->select([
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
                'c.celular',
                'c.direccion_fiscal',
                'c.correo',
                'c.estado_contado',
            ])
            ->where('f.id_factura', $id_factura)
            ->first();

        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        return response()->json($cliente);
    }

    /**
     * Actualizar cliente desde la factura.
     */
    public function actualizarCliente(Request $request, $id_factura): JsonResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id_factura);
        $cliente = $factura->cliente;

        $validated = $request->validate([
            'razon_social'    => 'required|string|max:200',
            'ruc'             => 'required|string|size:11|unique:cliente,ruc,' . $cliente->id_cliente . ',id_cliente',
            'celular'         => 'nullable|string|max:15',
            'direccion_fiscal'=> 'nullable|string|max:250',
            'correo'          => 'nullable|email|max:150',
        ]);

        $validated['fecha_actualizacion'] = now();

        $tieneCelular   = !empty($validated['celular']);
        $tieneCorreo    = !empty($validated['correo']);
        $tieneDireccion = !empty($validated['direccion_fiscal']);

        if ($tieneCelular && $tieneCorreo && $tieneDireccion) {
            $validated['estado_contado'] = 'COMPLETO';
        } elseif ($tieneCelular || $tieneCorreo) {
            $validated['estado_contado'] = 'INCOMPLETO';
        } else {
            $validated['estado_contado'] = 'SIN_DATOS';
        }

        $cliente->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado correctamente',
            'cliente' => $cliente,
        ]);
    }

    public function uploadComprobante(Request $request, $id)
    {
        // ruta_comprobante_pago no existe en el esquema actual — devolver éxito si se desea agregar luego
        return response()->json([
            'success' => false,
            'message' => 'La columna ruta_comprobante_pago no existe en la base de datos. Agrega la columna con una migración para usar esta funcionalidad.',
        ], 422);
    }

    private function subirACloudinary($file, $facturaId): ?string
    {
        $cloudName    = env('CLOUDINARY_CLOUD_NAME', 'dq3rban3m');
        $uploadPreset = env('CLOUDINARY_UPLOAD_PRESET', 'ml_default');

        try {
            $response = Http::attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                'factura_' . $facturaId . '_' . time() . '.' . $file->getClientOriginalExtension()
            )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                'upload_preset' => $uploadPreset,
                'folder'        => 'comprobantes_factura',
                'public_id'     => 'factura_' . $facturaId . '_' . time(),
            ]);

            if ($response->successful()) {
                return $response->json('secure_url');
            }

            \Log::error('Cloudinary upload error', ['response' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            \Log::error('Cloudinary exception: ' . $e->getMessage());
            return null;
        }
    }
}
