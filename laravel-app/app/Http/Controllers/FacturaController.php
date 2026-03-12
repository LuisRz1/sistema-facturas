<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

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
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
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
}
