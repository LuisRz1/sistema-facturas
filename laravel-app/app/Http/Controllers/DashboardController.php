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

        // ── KPIs del período ──────────────────────────────────────────────
        $kpis = DB::table('factura as f')
            ->leftJoin('detraccion as d', 'd.id_factura', '=', 'f.id_factura')
            ->leftJoin('autodetraccion as ad', 'ad.id_factura', '=', 'f.id_factura')
            ->leftJoin('retencion as r', 'r.id_factura', '=', 'f.id_factura')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->where('f.estado', '!=', 'ANULADA')
            ->select([
                DB::raw('COUNT(f.id_factura) as total_facturas'),
                DB::raw('SUM(f.importe_total) as total_facturado'),
                DB::raw('SUM(CASE WHEN f.estado = "PAGADA" THEN f.importe_total ELSE 0 END) as total_cobrado'),
                DB::raw('SUM(CASE WHEN f.estado NOT IN ("PAGADA","ANULADA") THEN f.importe_total ELSE 0 END) as total_por_cobrar'),
                DB::raw('SUM(CASE
                    WHEN d.total_detraccion IS NOT NULL THEN d.total_detraccion
                    WHEN ad.total_autodetraccion IS NOT NULL THEN ad.total_autodetraccion
                    WHEN r.total_retencion IS NOT NULL THEN r.total_retencion
                    ELSE 0
                END) as total_recaudacion'),
                DB::raw('COUNT(CASE WHEN f.estado = "PAGADA" THEN 1 END) as count_pagadas'),
                DB::raw('COUNT(CASE WHEN f.estado IN ("PENDIENTE","POR_VENCER","VENCIDA") THEN 1 END) as count_pendientes'),
                DB::raw('COUNT(CASE WHEN f.estado = "VENCIDA" THEN 1 END) as count_vencidas'),
            ])
            ->first();

        // ── Tendencia mensual (últimos 12 meses desde fecha_hasta) ────────
        $tendencia = DB::table('factura')
            ->where('estado', '!=', 'ANULADA')
            ->where('fecha_emision', '>=', now()->parse($fechaHasta)->subMonths(11)->startOfMonth()->format('Y-m-d'))
            ->where('fecha_emision', '<=', $fechaHasta)
            ->select([
                DB::raw('DATE_FORMAT(fecha_emision, "%Y-%m") as mes'),
                DB::raw('SUM(importe_total) as total'),
                DB::raw('COUNT(*) as cantidad'),
            ])
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // ── Top 5 clientes por monto facturado en el período ──────────────
        $topClientes = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
            ->where('f.estado', '!=', 'ANULADA')
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
        $ultimasFacturas = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereBetween('f.fecha_emision', [$fechaDesde, $fechaHasta])
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
        $porEstado = DB::table('factura')
            ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
            ->select([
                'estado',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(importe_total) as monto'),
            ])
            ->groupBy('estado')
            ->get()
            ->keyBy('estado');

        return view('dashboard', compact(
            'kpis', 'tendencia', 'topClientes', 'ultimasFacturas', 'porEstado',
            'fechaDesde', 'fechaHasta'
        ));
    }
}
