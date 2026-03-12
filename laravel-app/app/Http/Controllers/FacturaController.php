<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FacturaController extends Controller
{
    public function index(): View
    {
        // Obtener facturas con información de recaudación
        $facturas = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('detraccion as d', 'd.id_factura', '=', 'f.id_factura')
            ->leftJoin('autodetraccion as ad', 'ad.id_factura', '=', 'f.id_factura')
            ->leftJoin('retencion as r', 'r.id_factura', '=', 'f.id_factura')
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
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
                'c.correo as cliente_correo',
                'c.celular as cliente_celular',
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

        // Convertir a objetos mutables para carga de relaciones
        $facturasCollection = collect($facturas->map(function ($f) {
            return (object) array_merge((array) $f, [
                'cliente' => (object) [
                    'id_cliente' => $f->id_cliente,
                    'razon_social' => $f->razon_social,
                    'ruc' => $f->ruc,
                    'correo' => $f->cliente_correo,
                    'celular' => $f->cliente_celular,
                ],
                'notificaciones' => DB::table('notificacion_factura')
                    ->where('id_factura', $f->id_factura)
                    ->orderByDesc('id_notificacion')
                    ->limit(1)
                    ->get(),
            ]);
        }));

        // Obtener lista de empresas (clientes únicos)
        $clientes = DB::table('cliente')
            ->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);

        return view('facturas.index', ['facturas' => $facturasCollection, 'clientes' => $clientes]);
    }

    /**
     * Obtener datos de una factura para editar (AJAX)
     */
    public function edit($id): JsonResponse
    {
        $factura = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->select([
                'f.id_factura',
                'f.serie',
                'f.numero',
                'f.fecha_emision',
                'f.fecha_vencimiento',
                'f.fecha_abono',
                'f.moneda',
                'f.subtotal_gravado',
                'f.monto_igv',
                'f.importe_total',
                'f.estado',
                'f.glosa',
                'f.forma_pago',
                'f.tipo_recaudacion',
                'c.razon_social',
                'c.ruc',
            ])
            ->where('f.id_factura', $id)
            ->first();

        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura);
    }

    /**
     * Actualizar datos de una factura
     */
    public function update(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);

        $validated = $request->validate([
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'fecha_abono' => 'nullable|date',
            'glosa' => 'nullable|string',
            'forma_pago' => 'nullable|string',
            'estado' => 'nullable|in:PENDIENTE,POR_VENCER,VENCIDA,PAGADA,ANULADA,OBSERVADA',
            'importe_total' => 'nullable|numeric',
            'monto_igv' => 'nullable|numeric',
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
     * Subir comprobante de pago
     */
    public function uploadComprobante(Request $request, $id)
    {
        $request->validate([
            'comprobante' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $factura = Factura::findOrFail($id);

        // Guardar archivo
        if ($request->hasFile('comprobante')) {
            // Crear directorio si no existe
            $path = 'comprobantes/' . date('Y/m/');
            if (!file_exists(storage_path('app/public/' . $path))) {
                mkdir(storage_path('app/public/' . $path), 0755, true);
            }

            $file = $request->file('comprobante');
            $filename = 'comprobante_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/' . $path, $filename);

            // Actualizar factura con ruta del comprobante y cambiar estado a PAGADA
            $factura->update([
                'ruta_comprobante_pago' => $path . $filename,
                'estado' => 'PAGADA',
                'fecha_abono' => now()->toDateString(),
                'fecha_actualizacion' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comprobante guardado y factura marcada como pagada',
                'estado' => 'PAGADA',
            ]);
        }

        return response()->json(['error' => 'No se pudo guardar el comprobante'], 400);
    }
}
