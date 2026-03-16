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
        // Rango de fechas — por defecto primer día del mes actual hasta hoy
        $fechaDesde = $request->input('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'f.usuario_creacion')
            ->leftJoin('detraccion as d', 'd.id_factura', '=', 'f.id_factura')
            ->leftJoin('autodetraccion as ad', 'ad.id_factura', '=', 'f.id_factura')
            ->leftJoin('retencion as r', 'r.id_factura', '=', 'f.id_factura')
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
                'f.estado',
                'f.tipo_recaudacion',
                'f.glosa',
                'f.forma_pago',
                'f.ruta_comprobante_pago',
                'f.usuario_creacion',
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
                'c.correo as cliente_correo',
                'c.celular as cliente_celular',
                'u.nombre as usuario_nombre',
                'u.apellido as usuario_apellido',
                DB::raw('CASE
                    WHEN d.total_detraccion IS NOT NULL THEN d.total_detraccion
                    WHEN ad.total_autodetraccion IS NOT NULL THEN ad.total_autodetraccion
                    WHEN r.total_retencion IS NOT NULL THEN r.total_retencion
                    ELSE 0
                END AS monto_recaudacion'),
                DB::raw('CASE
                    WHEN d.porcentaje IS NOT NULL THEN d.porcentaje
                    WHEN ad.porcentaje IS NOT NULL THEN ad.porcentaje
                    WHEN r.porcentaje IS NOT NULL THEN r.porcentaje
                    ELSE 0
                END AS porcentaje_recaudacion'),
                DB::raw('CASE
                    WHEN d.total_detraccion IS NOT NULL THEN "DETRACCION"
                    WHEN ad.total_autodetraccion IS NOT NULL THEN "AUTODETRACCION"
                    WHEN r.total_retencion IS NOT NULL THEN "RETENCION"
                    ELSE NULL
                END AS tipo_recaudacion_actual'),
            ])
            ->orderByDesc('f.fecha_emision')
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
                // Última notificación por WhatsApp
                'ultima_notif_wa' => DB::table('notificacion_factura')
                    ->where('id_factura', $f->id_factura)
                    ->where('canal', 'WHATSAPP')
                    ->orderByDesc('id_notificacion')
                    ->first(),
                // Última notificación por Correo
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

        return view('facturas.index', [
            'facturas'   => $facturasCollection,
            'clientes'   => $clientes,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    public function edit($id): JsonResponse
    {
        $factura = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->select([
                'f.id_factura','f.serie','f.numero','f.fecha_emision',
                'f.fecha_vencimiento','f.fecha_abono','f.moneda',
                'f.subtotal_gravado','f.monto_igv','f.importe_total',
                'f.estado','f.glosa','f.forma_pago','f.tipo_recaudacion',
                'c.razon_social','c.ruc',
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
            'fecha_abono'      => 'nullable|date',
            'glosa'            => 'nullable|string',
            'forma_pago'       => 'nullable|string',
            'estado'           => 'nullable|in:PENDIENTE,POR_VENCER,VENCIDA,PAGADA,ANULADA,OBSERVADA',
            'importe_total'    => 'nullable|numeric',
            'monto_igv'        => 'nullable|numeric',
            'subtotal_gravado' => 'nullable|numeric',
        ]);

        $factura->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Factura actualizada correctamente',
            'factura' => $factura,
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
                'c.estado_contacto',
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

        // Calcular estado de contacto
        $tieneCelular = !empty($validated['celular']);
        $tieneCorreo  = !empty($validated['correo']);
        $tieneDireccion = !empty($validated['direccion_fiscal']);

        if ($tieneCelular && $tieneCorreo && $tieneDireccion) {
            $validated['estado_contacto'] = 'COMPLETO';
        } elseif ($tieneCelular || $tieneCorreo) {
            $validated['estado_contacto'] = 'INCOMPLETO';
        } else {
            $validated['estado_contacto'] = 'SIN_DATOS';
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
        $request->validate([
            'comprobante' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:5120',
        ]);

        $factura = Factura::findOrFail($id);

        if (!$request->hasFile('comprobante')) {
            return response()->json(['error' => 'No se recibió ningún archivo'], 400);
        }

        $cloudUrl = $this->subirACloudinary($request->file('comprobante'), $id);

        if (!$cloudUrl) {
            return response()->json(['error' => 'No se pudo subir el archivo a Cloudinary'], 500);
        }

        $updateData = [
            'ruta_comprobante_pago' => $cloudUrl,
            'fecha_actualizacion'   => now(),
        ];

        $estadoAnterior = $factura->estado;
        if (in_array($factura->estado, ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])) {
            $updateData['estado']      = 'PAGADA';
            $updateData['fecha_abono'] = now()->toDateString();
        }

        $factura->update($updateData);

        $mensaje = in_array($estadoAnterior, ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])
            ? 'Comprobante guardado y factura marcada como PAGADA'
            : 'Imagen de factura actualizada correctamente';

        return response()->json([
            'success'  => true,
            'message'  => $mensaje,
            'estado'   => $factura->fresh()->estado,
            'imageUrl' => $cloudUrl,
        ]);
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
