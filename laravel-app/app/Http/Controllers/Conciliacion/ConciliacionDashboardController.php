<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConciliacionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $inicioMes = $mes . '-01';
        $finMes = now()->parse($inicioMes)->endOfMonth()->format('Y-m-d');

        // Total archivos importados en el mes
        $totalArchivos = DB::table('archivo_importado')
            ->where('activo', 1)
            ->whereBetween('fecha_importacion', [$inicioMes, $finMes])
            ->count();

        // Total movimientos bancarios en el mes
        $totalMovimientos = DB::table('movimiento_bancario')
            ->where('activo', 1)
            ->whereBetween('fecha_proceso', [$inicioMes, $finMes])
            ->count();

        // Distribución de estados de conciliación
        $estadosDistribucion = DB::table('movimiento_bancario')
            ->where('activo', 1)
            ->whereBetween('fecha_proceso', [$inicioMes, $finMes])
            ->select('estado_conciliacion', DB::raw('COUNT(*) as total'), DB::raw('SUM(importe) as monto'))
            ->groupBy('estado_conciliacion')
            ->get()
            ->keyBy('estado_conciliacion');

        $totalConMovimientos = $estadosDistribucion->sum('total');
        $conciliadoAuto = ($estadosDistribucion->get('CONCILIADO')?->total ?? 0)
            + ($estadosDistribucion->get('CONCILIADO_TOLERANCIA')?->total ?? 0);
        $conciliadoManual = $estadosDistribucion->get('CONCILIADO_MANUAL')?->total ?? 0;
        $pendiente = $estadosDistribucion->get('SIN_MATCH')?->total ?? 0;
        $ignorado = $estadosDistribucion->get('IGNORADO')?->total ?? 0;
        $extornado = $estadosDistribucion->get('EXTORNADO')?->total ?? 0;
        $duplicado = $estadosDistribucion->get('DUPLICADO_OMITIDO')?->total ?? 0;

        // Porcentajes
        $pctConciliadoAuto = $totalConMovimientos > 0 ? round(($conciliadoAuto / $totalConMovimientos) * 100, 1) : 0;
        $pctConciliadoManual = $totalConMovimientos > 0 ? round(($conciliadoManual / $totalConMovimientos) * 100, 1) : 0;
        $pctPendiente = $totalConMovimientos > 0 ? round(($pendiente / $totalConMovimientos) * 100, 1) : 0;
        $pctIgnorado = $totalConMovimientos > 0 ? round(($ignorado / $totalConMovimientos) * 100, 1) : 0;
        $pctExtornado = $totalConMovimientos > 0 ? round(($extornado / $totalConMovimientos) * 100, 1) : 0;

        // Monto total conciliado por banco
        $montoPorBanco = DB::table('movimiento_bancario')
            ->where('activo', 1)
            ->whereBetween('fecha_proceso', [$inicioMes, $finMes])
            ->whereIn('estado_conciliacion', ['CONCILIADO', 'CONCILIADO_TOLERANCIA', 'CONCILIADO_MANUAL'])
            ->select('banco', DB::raw('SUM(importe) as monto_total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('banco')
            ->orderByDesc('monto_total')
            ->get();

        // Conteo de movimientos en bandeja (SIN_MATCH pendientes de revisión)
        $countBandeja = DB::table('movimiento_bancario')
            ->where('activo', 1)
            ->where('estado_conciliacion', 'SIN_MATCH')
            ->count();

        // Últimos archivos importados
        $ultimosArchivos = DB::table('archivo_importado')
            ->where('activo', 1)
            ->orderByDesc('fecha_importacion')
            ->limit(10)
            ->get();

        // KPIs adicionales del mes actual
        $montoTotalConciliado = DB::table('movimiento_bancario')
            ->where('activo', 1)
            ->whereBetween('fecha_proceso', [$inicioMes, $finMes])
            ->whereIn('estado_conciliacion', ['CONCILIADO', 'CONCILIADO_TOLERANCIA', 'CONCILIADO_MANUAL'])
            ->sum('importe');

        $montoTotalMovimientos = DB::table('movimiento_bancario')
            ->where('activo', 1)
            ->whereBetween('fecha_proceso', [$inicioMes, $finMes])
            ->sum('importe');

        return view('conciliacion.dashboard', compact(
            'mes',
            'totalArchivos',
            'totalMovimientos',
            'estadosDistribucion',
            'totalConMovimientos',
            'conciliadoAuto',
            'conciliadoManual',
            'pendiente',
            'ignorado',
            'extornado',
            'duplicado',
            'pctConciliadoAuto',
            'pctConciliadoManual',
            'pctPendiente',
            'pctIgnorado',
            'pctExtornado',
            'montoPorBanco',
            'countBandeja',
            'ultimosArchivos',
            'montoTotalConciliado',
            'montoTotalMovimientos',
        ));
    }
}
