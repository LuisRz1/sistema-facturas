<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /** Vista principal con filtros. */
    public function index()
    {
        $clientes = DB::table('cliente')
            ->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);

        return view('reportes.index', compact('clientes'));
    }

    /** JSON para previsualización AJAX. */
    public function json(Request $request)
    {
        $idCliente = $request->input('id_cliente');
        $estado    = $request->input('estado');

        $facturas = $this->queryFacturas($idCliente, $estado)->get();
        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $clienteNombre = 'TODOS LOS CLIENTES';
        if ($idCliente) {
            $cli = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if ($cli) $clienteNombre = strtoupper($cli->razon_social);
        }

        return response()->json([
            'facturas'       => $facturas->values(),
            'cliente_nombre' => $clienteNombre,
            'estado_label'   => $estado ? strtoupper($estado) : 'TODOS LOS ESTADOS',
            'resumen'        => [
                'total_facturas'    => $facturas->count(),
                'pendientes'        => $facturas->whereIn('estado', ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])->count(),
                'pagadas'           => $facturas->where('estado', 'PAGADA')->count(),
                'total_bruto'       => $facturas->sum('importe_total'),
                'total_recaudacion' => $facturas->sum('monto_recaudacion'),
                'total_neto'        => $facturas->sum('neto_caja'),
                'saldo_cobrar'      => $facturas->whereNotIn('estado', ['PAGADA', 'ANULADA'])->sum('neto_caja'),
            ],
        ]);
    }

    /** Vista PDF — se abre en nueva pestaña, el navegador imprime / guarda como PDF. */
    public function pdf(Request $request)
    {
        $idCliente = $request->input('id_cliente');
        $estado    = $request->input('estado');

        $facturas = $this->queryFacturas($idCliente, $estado)->get();
        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        // Si no hay cliente específico, agrupar por empresa
        $facturasAgrupadas = null;
        if (!$idCliente) {
            $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();
        }

        $resumen = [
            'total_facturas'    => $facturas->count(),
            'pendientes'        => $facturas->whereIn('estado', ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])->count(),
            'pagadas'           => $facturas->where('estado', 'PAGADA')->count(),
            'vencidas'          => $facturas->where('estado', 'VENCIDA')->count(),
            'total_bruto'       => $facturas->sum('importe_total'),
            'total_recaudacion' => $facturas->sum('monto_recaudacion'),
            'total_neto'        => $facturas->sum('neto_caja'),
            'saldo_cobrar'      => $facturas->whereNotIn('estado', ['PAGADA', 'ANULADA'])->sum('neto_caja'),
        ];

        $clienteNombre = 'TODOS LOS CLIENTES';
        if ($idCliente) {
            $cli = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if ($cli) $clienteNombre = strtoupper($cli->razon_social);
        }

        $estadoLabel = $estado ? strtoupper($estado) : 'TODOS LOS ESTADOS';

        return view('reportes.pdf', compact('facturas', 'facturasAgrupadas', 'resumen', 'clienteNombre', 'estadoLabel', 'idCliente'));
    }

    /** Query reutilizable con joins a detraccion/autodetraccion/retencion. */
    private function queryFacturas($idCliente, $estado)
    {
        $query = DB::table('factura as f')
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
                'f.glosa',
                'f.moneda',
                'f.importe_total',
                'f.subtotal_gravado',
                'f.monto_igv',
                'f.tipo_recaudacion',
                'f.estado',
                'f.forma_pago',
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
            ->orderBy('c.razon_social', 'asc')
            ->orderBy('f.fecha_emision', 'asc')
            ->orderBy('f.numero', 'asc');

        if ($idCliente) $query->where('f.id_cliente', $idCliente);
        if ($estado)    $query->where('f.estado', $estado);

        return $query;
    }
}
