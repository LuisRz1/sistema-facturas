<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SincronizacionController extends Controller
{
    /**
     * Página de historial de importaciones.
     */
    public function index()
    {
        $sincronizaciones = DB::table('sincronizacion_nubefact')
            ->orderByDesc('fecha_inicio')
            ->paginate(20);

        return view('facturas.historial_importaciones', compact('sincronizaciones'));
    }

    /**
     * Desactiva una importación: pone activo=0 en sincronizacion_nubefact
     * y en todas las facturas, recaudaciones y créditos vinculados.
     */
    public function desactivar(int $id)
    {
        $sinc = DB::table('sincronizacion_nubefact')->where('id_sincronizacion', $id)->first();
        if (!$sinc) {
            return response()->json(['error' => 'Importación no encontrada.'], 404);
        }

        $facturaIds = DB::table('sincronizacion_factura')
            ->where('id_sincronizacion', $id)
            ->pluck('id_factura')
            ->toArray();

        DB::beginTransaction();
        try {
            DB::table('sincronizacion_nubefact')->where('id_sincronizacion', $id)->update(['activo' => 0]);

            if (!empty($facturaIds)) {
                DB::table('factura')->whereIn('id_factura', $facturaIds)->update(['activo' => 0]);
                DB::table('recaudacion')->whereIn('id_factura', $facturaIds)->update(['activo' => 0]);
                DB::table('credito')->whereIn('id_factura', $facturaIds)->update(['activo' => 0]);
                DB::table('pago_factura')->whereIn('id_factura', $facturaIds)->update(['activo' => 0]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al desactivar la importación.'], 500);
        }

        return response()->json([
            'success'     => true,
            'mensaje'     => 'Importación desactivada. ' . count($facturaIds) . ' factura(s) ocultada(s).',
            'total'       => count($facturaIds),
        ]);
    }

    /**
     * Reactiva una importación desactivada.
     * Verifica que ninguna factura de este lote haya sido reimportada en un
     * lote posterior que sigue activo (misma serie+numero → conflicto).
     */
    public function activar(int $id)
    {
        $sinc = DB::table('sincronizacion_nubefact')->where('id_sincronizacion', $id)->first();
        if (!$sinc) {
            return response()->json(['error' => 'Importación no encontrada.'], 404);
        }

        // Facturas vinculadas a este lote
        $facturaIds = DB::table('sincronizacion_factura')
            ->where('id_sincronizacion', $id)
            ->pluck('id_factura')
            ->toArray();

        if (empty($facturaIds)) {
            return response()->json(['error' => 'Esta importación no tiene facturas vinculadas.'], 422);
        }

        // Detectar conflictos: alguna factura de este lote ya está vinculada a otro lote activo
        $conflictosSimple = [];
        foreach (DB::table('sincronizacion_factura as sf2')
            ->join('sincronizacion_nubefact as sn2', 'sn2.id_sincronizacion', '=', 'sf2.id_sincronizacion')
            ->join('factura as f2', 'f2.id_factura', '=', 'sf2.id_factura')
            ->join('cliente as c2', 'c2.id_cliente', '=', 'f2.id_cliente')
            ->whereIn('sf2.id_factura', $facturaIds)
            ->where('sf2.id_sincronizacion', '!=', $id)
            ->where('sn2.activo', 1)
            ->select('f2.serie', 'f2.numero', 'sn2.nombre_archivo', 'sn2.fecha_inicio')
            ->get() as $dup) {
            $conflictosSimple[] = [
                'factura'        => $dup->serie . '-' . str_pad($dup->numero, 8, '0', STR_PAD_LEFT),
                'en_importacion' => $dup->nombre_archivo,
                'fecha'          => $dup->fecha_inicio,
            ];
        }

        if (!empty($conflictosSimple)) {
            return response()->json([
                'error'      => 'No se puede reactivar. Las siguientes facturas ya fueron reimportadas en otro lote activo:',
                'conflictos' => $conflictosSimple,
            ], 409);
        }

        // Sin conflictos → reactivar
        DB::beginTransaction();
        try {
            DB::table('sincronizacion_nubefact')->where('id_sincronizacion', $id)->update(['activo' => 1]);
            DB::table('factura')->whereIn('id_factura', $facturaIds)->update(['activo' => 1]);
            DB::table('recaudacion')->whereIn('id_factura', $facturaIds)->update(['activo' => 1]);
            DB::table('credito')->whereIn('id_factura', $facturaIds)->update(['activo' => 1]);
            DB::table('pago_factura')->whereIn('id_factura', $facturaIds)->update(['activo' => 1]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al reactivar la importación.'], 500);
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Importación reactivada. ' . count($facturaIds) . ' factura(s) visible(s) nuevamente.',
            'total'   => count($facturaIds),
        ]);
    }

    /**
     * Devuelve las facturas de una sincronización (para el acordeón).
     */
    public function facturas(int $id)
    {
        $facturas = DB::table('factura as f')
            ->join('sincronizacion_factura as sf', 'sf.id_factura', '=', 'f.id_factura')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->where('sf.id_sincronizacion', $id)
            ->select('f.serie', 'f.numero', 'f.fecha_emision', 'f.importe_total',
                     'f.moneda', 'f.estado', 'f.activo', 'c.razon_social')
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.numero')
            ->get();

        return response()->json($facturas);
    }
}
