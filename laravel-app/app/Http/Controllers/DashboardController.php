<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $tipoCliente = strtoupper((string) $request->input('tipo_cliente', 'PERSONA JURIDICA'));
        if (!in_array($tipoCliente, ['PERSONA JURIDICA', 'PERSONA NATURAL'], true)) {
            $tipoCliente = 'PERSONA JURIDICA';
        }

        $estadosPendientes = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'POR VALIDAR DETRACCION', 'DIFERENCIA PENDIENTE'];
        $fechaTendenciaDesde = now()->parse($fechaHasta)->subMonths(11)->startOfMonth()->format('Y-m-d');

        $orphanIdsEnRango = function (string $desde, string $hasta) use ($tipoCliente): array {
            return DB::table('factura as f')
                ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
                ->join('credito as cr', 'cr.id_factura', '=', 'f.id_factura')
                ->leftJoin('factura as fo', function ($join) {
                    $join->on('fo.serie', '=', 'cr.serie_doc_modificado')
                        ->on('fo.numero', '=', 'cr.numero_doc_modificado');
                })
                ->whereBetween('f.fecha_emision', [$desde, $hasta])
                ->where('c.tipo_cliente', $tipoCliente)
                ->whereNull('fo.id_factura')
                ->pluck('f.id_factura')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        };

        $applyTotalesScope = function ($query, array $orphanIds) {
            if (!empty($orphanIds)) {
                $query->whereNotIn('f.id_factura', $orphanIds);
            }

            // Regla negocio: ANULADO solo cuenta si tiene registro en credito.
            return $query->where(function ($q) {
                $q->where('f.estado', '!=', 'ANULADO')
                    ->orWhereExists(function ($sq) {
                        $sq->select(DB::raw(1))
                            ->from('credito as cr2')
                            ->whereColumn('cr2.id_factura', 'f.id_factura');
                    });
            });
        };

        $orphanPeriodo = $orphanIdsEnRango($fechaDesde, $fechaHasta);
        $orphanTendencia = $orphanIdsEnRango($fechaTendenciaDesde, $fechaHasta);

        // ── KPIs del período ──────────────────────────────────────────────
        $kpisQuery = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->where('c.tipo_cliente', $tipoCliente);

        $kpis = $applyTotalesScope($kpisQuery, $orphanPeriodo)
            ->select([
                DB::raw('COUNT(f.id_factura) as total_facturas'),
                DB::raw('SUM(CASE WHEN f.estado = "ANULADO" THEN 0 ELSE COALESCE(f.importe_total, 0) END) as total_facturado'),
                DB::raw('SUM(CASE WHEN f.estado = "ANULADO" THEN 0 ELSE GREATEST(COALESCE(f.importe_total, 0) - COALESCE(f.monto_pendiente, 0), 0) END) as total_cobrado'),
                DB::raw('SUM(CASE WHEN f.estado IN ("PENDIENTE","VENCIDO","PAGO PARCIAL","POR VALIDAR DETRACCION","DIFERENCIA PENDIENTE") THEN COALESCE(f.monto_pendiente, 0) ELSE 0 END) as total_por_cobrar'),
                DB::raw('SUM(COALESCE(rec.total_recaudacion, 0)) as total_recaudacion'),
                DB::raw('COUNT(CASE WHEN f.estado = "PAGADA" THEN 1 END) as count_pagadas'),
                DB::raw('COUNT(CASE WHEN f.estado IN ("PENDIENTE","VENCIDO","PAGO PARCIAL","POR VALIDAR DETRACCION","DIFERENCIA PENDIENTE") THEN 1 END) as count_pendientes'),
                DB::raw('COUNT(CASE WHEN f.estado = "VENCIDO" THEN 1 END) as count_vencidas'),
            ])
            ->first();

        // ── Tendencia mensual (últimos 12 meses desde fecha_hasta) ────────
        $tendenciaQuery = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->where('f.fecha_emision', '>=', $fechaTendenciaDesde)
            ->where('f.fecha_emision', '<=', $fechaHasta)
            ->where('c.tipo_cliente', $tipoCliente);

        $tendencia = $applyTotalesScope($tendenciaQuery, $orphanTendencia)
            ->select([
                DB::raw('DATE_FORMAT(f.fecha_emision, "%Y-%m") as mes'),
                DB::raw('SUM(f.importe_total) as total'),
                DB::raw('COUNT(*) as cantidad'),
            ])
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // ── Top 5 clientes por monto facturado en el período ──────────────
        $topClientesQuery = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->where('c.tipo_cliente', $tipoCliente);

        $topClientes = $applyTotalesScope($topClientesQuery, $orphanPeriodo)
            ->select([
                'c.razon_social',
                DB::raw('SUM(f.importe_total) as total'),
                DB::raw('COUNT(f.id_factura) as cantidad'),
            ])
            ->groupBy('c.id_cliente', 'c.razon_social')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ── Últimas 8 facturas del período ────────────────────────────────
        $ultimasFacturasQuery = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->where('c.tipo_cliente', $tipoCliente);

        $ultimasFacturas = $applyTotalesScope($ultimasFacturasQuery, $orphanPeriodo)
            ->select([
                'f.id_factura', 'f.serie', 'f.numero',
                'f.importe_total', 'f.estado', 'f.fecha_emision', 'f.glosa',
                'c.razon_social',
            ])
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.numero')
            ->limit(8)
            ->get();

        // ── Distribución por estado (para mini donut) ─────────────────────
        $porEstadoQuery = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->where('c.tipo_cliente', $tipoCliente);

        $porEstado = $applyTotalesScope($porEstadoQuery, $orphanPeriodo)
            ->select([
                'f.estado',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(f.importe_total) as monto'),
            ])
            ->groupBy('f.estado')
            ->get()
            ->keyBy('estado');

        return view('dashboard', compact(
            'kpis', 'tendencia', 'topClientes', 'ultimasFacturas', 'porEstado',
            'fechaDesde', 'fechaHasta', 'tipoCliente'
        ));
    }
}
