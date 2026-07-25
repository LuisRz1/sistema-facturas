<?php

namespace App\Services\Conciliacion;

use App\Models\MovimientoBancario;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;

class MatchingEngine
{
    private const SCORE_AUTO_UMBRAL = 90;
    private const SCORE_MANUAL_UMBRAL = 60;

    /**
     * Evalua un movimiento bancario contra la cartera de deuda.
     * Retorna score (0-100), candidatos, y accion (AUTO / MANUAL / IGNORAR).
     *
     * Estrategias de scoring acumulativo:
     *  1. Numero de operacion coincide con promesa de pago → +60 pts (HU-E2-01)
     *  2. Monto exacto con una sola factura pendiente → +40 pts
     *  3. Nombre del ordenante en referencia bancaria → +30 pts
     *  4. Rango de monto con tolerancia → +20 pts max
     *  Penalizacion: -20 pts por cada candidato extra en ambiguedad
     */
    public function evaluar(MovimientoBancario $movimiento, array $opciones = []): array
    {
        $resultado = [
            'score' => 0,
            'estrategias_aplicadas' => [],
            'candidatos' => [],
            'accion' => 'MANUAL',
            'cliente_id' => null,
            'factura_id' => null,
        ];

        if ($movimiento->tipo_movimiento !== 'ABONO') {
            $resultado['accion'] = 'IGNORAR';
            return $resultado;
        }

        $monto = (float) $movimiento->importe;
        $referencia = strtoupper($movimiento->referencia.' '.$movimiento->descripcion);
        $numOperacion = $movimiento->numero_operacion;
        $tolerancia = $opciones['tolerancia_monto'] ?? 1.00;

        // ── Estrategia 1: Promesa de pago por numero de operacion ──
        $promesa = DB::table('pago_factura')
            ->where('numero_operacion', $numOperacion)
            ->where('activo', 1)
            ->first();

        if ($promesa) {
            $resultado['score'] += 60;
            $resultado['estrategias_aplicadas'][] = 'PROMESA_PAGO';
            $resultado['candidatos'][] = [
                'id_factura' => $promesa->id_factura,
                'id_cliente' => null,
                'razon_social' => '',
                'sub_score' => 60,
                'estrategia' => 'PROMESA_PAGO',
            ];
        }

        // ── Estrategia 2: Match exacto de monto ──
        $facturasExactas = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereIn('f.estado', ['PENDIENTE', 'VENCIDO', 'DIFERENCIA PENDIENTE'])
            ->where('f.activo', 1)
            ->where('f.monto_pendiente', $monto)
            ->select('f.id_factura', 'f.id_cliente', 'f.monto_pendiente', 'c.razon_social', 'c.ruc')
            ->get();

        if ($facturasExactas->count() === 1) {
            $resultado['score'] += 40;
            $resultado['estrategias_aplicadas'][] = 'MONTO_EXACTO_UNICO';
            $resultado['candidatos'][] = [
                'id_cliente' => $facturasExactas[0]->id_cliente,
                'id_factura' => $facturasExactas[0]->id_factura,
                'razon_social' => $facturasExactas[0]->razon_social,
                'sub_score' => 40,
                'estrategia' => 'MONTO_EXACTO_UNICO',
            ];
        } elseif ($facturasExactas->count() > 1) {
            $penalizacion = ($facturasExactas->count() - 1) * 20;
            $subScore = max(0, 30 - $penalizacion);
            $resultado['score'] += $subScore;
            $resultado['estrategias_aplicadas'][] = 'MONTO_EXACTO_MULTIPLE';
            foreach ($facturasExactas as $f) {
                $resultado['candidatos'][] = [
                    'id_cliente' => $f->id_cliente,
                    'id_factura' => $f->id_factura,
                    'razon_social' => $f->razon_social,
                    'sub_score' => $subScore,
                    'estrategia' => 'MONTO_EXACTO_MULTIPLE',
                ];
            }
        }

        // ── Estrategia 3: Match por nombre en referencia ──
        if (! empty($referencia) && strlen($referencia) >= 4) {
            $palabras = array_filter(
                explode(' ', $referencia),
                fn ($p) => strlen($p) >= 4
            );

            if (! empty($palabras)) {
                $clientesPorNombre = DB::table('cliente')
                    ->where('activo', 1)
                    ->where(function ($q) use ($palabras) {
                        foreach ($palabras as $palabra) {
                            $q->orWhere('razon_social', 'like', "%{$palabra}%");
                        }
                    })
                    ->select('id_cliente', 'razon_social', 'ruc')
                    ->limit(5)
                    ->get();

                if ($clientesPorNombre->count() === 1) {
                    $resultado['score'] += 30;
                    $resultado['estrategias_aplicadas'][] = 'NOMBRE_REFERENCIA';
                    $resultado['candidatos'][] = [
                        'id_cliente' => $clientesPorNombre[0]->id_cliente,
                        'id_factura' => null,
                        'razon_social' => $clientesPorNombre[0]->razon_social,
                        'sub_score' => 30,
                        'estrategia' => 'NOMBRE_REFERENCIA',
                    ];
                } elseif ($clientesPorNombre->count() > 1) {
                    $resultado['score'] += 10;
                    $resultado['estrategias_aplicadas'][] = 'NOMBRE_REFERENCIA_MULTIPLE';
                    foreach ($clientesPorNombre as $c) {
                        $resultado['candidatos'][] = [
                            'id_cliente' => $c->id_cliente,
                            'id_factura' => null,
                            'razon_social' => $c->razon_social,
                            'sub_score' => 10,
                            'estrategia' => 'NOMBRE_REFERENCIA_MULTIPLE',
                        ];
                    }
                }
            }
        }

        // ── Estrategia 4: Rango de monto con tolerancia ──
        $facturasRango = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->whereIn('f.estado', ['PENDIENTE', 'VENCIDO', 'DIFERENCIA PENDIENTE'])
            ->where('f.activo', 1)
            ->whereBetween('f.monto_pendiente', [$monto - $tolerancia, $monto + $tolerancia])
            ->select('f.id_factura', 'f.id_cliente', 'f.monto_pendiente', 'c.razon_social')
            ->orderByRaw('ABS(f.monto_pendiente - ?)', [$monto])
            ->limit(5)
            ->get();

        if ($facturasRango->isNotEmpty()) {
            $subScoreRango = min(20, $facturasRango->count() * 5);
            $resultado['score'] += $subScoreRango;
            $resultado['estrategias_aplicadas'][] = 'RANGO_MONTO';
            foreach ($facturasRango as $f) {
                $dif = abs((float) $f->monto_pendiente - $monto);
                $resultado['candidatos'][] = [
                    'id_cliente' => $f->id_cliente,
                    'id_factura' => $f->id_factura,
                    'razon_social' => $f->razon_social,
                    'monto_pendiente' => $f->monto_pendiente,
                    'diferencia' => round($dif, 2),
                    'sub_score' => max(0, 20 - ($dif * 10)),
                    'estrategia' => 'RANGO_MONTO',
                ];
            }
        }

        // ── Determinar accion final ──
        $resultado['score'] = min(100, $resultado['score']);

        if ($resultado['score'] >= self::SCORE_AUTO_UMBRAL && ! empty($resultado['candidatos'])) {
            $resultado['accion'] = 'AUTO';
            // Ordenar por sub_score descendente y tomar el mejor
            usort($resultado['candidatos'], fn ($a, $b) => $b['sub_score'] <=> $a['sub_score']);
            $mejor = $resultado['candidatos'][0];
            $resultado['cliente_id'] = $mejor['id_cliente'];

            // Si no tiene factura especifica, buscar la pendiente mas antigua del cliente
            if (! empty($mejor['id_factura'])) {
                $resultado['factura_id'] = $mejor['id_factura'];
            } elseif (! empty($mejor['id_cliente'])) {
                $facturaPendiente = DB::table('factura')
                    ->where('id_cliente', $mejor['id_cliente'])
                    ->whereIn('estado', ['PENDIENTE', 'VENCIDO', 'DIFERENCIA PENDIENTE'])
                    ->where('activo', 1)
                    ->where('monto_pendiente', '<=', $monto + $tolerancia)
                    ->where('monto_pendiente', '>=', $monto - $tolerancia)
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->first();

                $resultado['factura_id'] = $facturaPendiente->id_factura ?? null;
            }
        } elseif ($resultado['score'] < self::SCORE_MANUAL_UMBRAL || empty($resultado['candidatos'])) {
            $resultado['accion'] = 'MANUAL';
        } else {
            $resultado['accion'] = 'MANUAL'; // Rango medio 60-89: bandeja con sugerencias
        }

        return $resultado;
    }

    /**
     * Ejecuta la conciliacion automatica: actualiza movimiento, factura, y crea pago.
     */
    public function conciliar(MovimientoBancario $movimiento, array $resultado): void
    {
        if ($resultado['accion'] !== 'AUTO') {
            return;
        }

        $idFactura = $resultado['factura_id'];
        if (! $idFactura) {
            return;
        }

        DB::transaction(function () use ($movimiento, $resultado, $idFactura) {
            $factura = Factura::find($idFactura);
            if (! $factura) {
                return;
            }

            $montoMovimiento = (float) $movimiento->importe;
            $montoPendiente = (float) $factura->monto_pendiente;
            $diferencia = abs($montoMovimiento - $montoPendiente);

            $estado = $diferencia <= 0.01 ? 'CONCILIADO' : 'CONCILIADO_TOLERANCIA';
            $estadoAnterior = $movimiento->estado_conciliacion;

            // Actualizar movimiento
            $movimiento->update([
                'estado_conciliacion' => $estado,
                'id_cliente' => $resultado['cliente_id'],
                'id_factura' => $idFactura,
                'score_match' => $resultado['score'],
                'fecha_conciliacion' => now(),
            ]);

            // Actualizar factura
            $factura->update([
                'estado' => 'PAGADA',
                'monto_abonado' => $factura->importe_total,
                'monto_pendiente' => 0,
                'fecha_actualizacion' => now(),
                'id_movimiento' => $movimiento->id_movimiento,
                'estado_conciliacion' => $estado,
            ]);

            // Registrar pago automatico
            DB::table('pago_factura')->insert([
                'id_factura' => $idFactura,
                'monto_pagado' => $montoMovimiento,
                'fecha_pago' => $movimiento->fecha_operacion ?? now()->toDateString(),
                'observacion' => "Conciliacion automatica — Mov #{$movimiento->id_movimiento} — Score: {$resultado['score']} — Estrategias: ".implode(', ', $resultado['estrategias_aplicadas']),
                'activo' => 1,
                'fecha_creacion' => now(),
            ]);

            // Historial de estado
            DB::table('movimiento_historial_estado')->insert([
                'id_movimiento' => $movimiento->id_movimiento,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estado,
                'fecha_transicion' => now(),
            ]);
        });
    }
}
