<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use App\Models\MovimientoBancario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConciliacionBandejaController extends Controller
{
    /**
     * HU-E3-01: Lista de movimientos SIN_MATCH pendientes de conciliación manual.
     */
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', 25);

        $movimientos = DB::table('movimiento_bancario as m')
            ->leftJoin('cliente as c', 'm.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('factura as f', 'm.id_factura', '=', 'f.id_factura')
            ->where('m.estado_conciliacion', MovimientoBancario::ESTADO_SIN_MATCH)
            ->where('m.activo', 1)
            ->where('m.tipo_movimiento', 'ABONO')
            ->select(
                'm.id_movimiento',
                'm.banco',
                'm.moneda',
                'm.fecha_operacion',
                'm.numero_operacion',
                'm.descripcion',
                'm.referencia',
                'm.importe',
                'm.score_match',
                'm.id_cliente',
                'm.id_factura',
                'c.razon_social as cliente_razon_social',
                'c.ruc as cliente_ruc',
                'f.serie as factura_serie',
                'f.numero as factura_numero',
                'f.importe_total as factura_importe_total',
                'f.estado as factura_estado'
            )
            ->orderBy('m.fecha_operacion', 'desc')
            ->orderBy('m.id_movimiento', 'desc')
            ->paginate($perPage);

        // Datos para los dropdowns: todos los clientes activos
        $clientes = DB::table('cliente')
            ->select('id_cliente', 'razon_social', 'ruc')
            ->orderBy('razon_social')
            ->get();

        return view('conciliacion.bandeja', compact('movimientos', 'clientes'));
    }

    /**
     * HU-E3-03 corregida: Conciliación manual de un movimiento.
     */
    public function conciliarManual(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_factura' => 'required|integer|exists:factura,id_factura',
            'motivo'     => 'required|string|min:10',
        ]);

        $movimiento = MovimientoBancario::findOrFail($id);

        if ($movimiento->estado_conciliacion !== MovimientoBancario::ESTADO_SIN_MATCH) {
            return response()->json([
                'success' => false,
                'message' => 'El movimiento ya no se encuentra en estado SIN_MATCH.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($movimiento, $request) {
                $now = now();
                $userId = auth()->id();
                $userName = auth()->user() ? (auth()->user()->nombre . ' ' . auth()->user()->apellido) : 'Sistema';

                $factura = DB::table('factura')->where('id_factura', $request->id_factura)->first();
                if (!$factura) {
                    throw new \RuntimeException('Factura no encontrada.');
                }

                // Verificar que la factura no esté ya pagada
                if ($factura->estado === 'PAGADA') {
                    throw new \RuntimeException('La factura ya se encuentra en estado PAGADA.');
                }

                // 1. Actualizar movimiento a CONCILIADO_MANUAL
                DB::table('movimiento_bancario')
                    ->where('id_movimiento', $movimiento->id_movimiento)
                    ->update([
                        'estado_conciliacion'     => MovimientoBancario::ESTADO_CONCILIADO_MANUAL,
                        'id_cliente'              => $factura->id_cliente,
                        'id_factura'              => $request->id_factura,
                        'usuario_conciliador_id'  => $userId,
                        'fecha_conciliacion'      => $now,
                    ]);

                // 2. Actualizar factura a PAGADA
                DB::table('factura')
                    ->where('id_factura', $request->id_factura)
                    ->update([
                        'estado'                => 'PAGADA',
                        'monto_abonado'         => $factura->importe_total,
                        'monto_pendiente'       => 0,
                        'id_movimiento'         => $movimiento->id_movimiento,
                        'estado_conciliacion'   => 'CONCILIADO_MANUAL',
                        'fecha_actualizacion'   => $now,
                    ]);

                // 3. Insertar pago_factura
                $observacion = 'Conciliación manual por ' . $userName . '. Motivo: ' . $request->motivo;
                DB::table('pago_factura')->insert([
                    'id_factura'          => $request->id_factura,
                    'monto_pagado'        => $movimiento->importe,
                    'fecha_pago'          => $movimiento->fecha_operacion ?? $now,
                    'cuenta_pago'         => $movimiento->cuenta_bancaria ?? '',
                    'numero_operacion'    => $movimiento->numero_operacion,
                    'banco_origen'        => $movimiento->banco,
                    'forma_pago'          => 'TRANSFERENCIA',
                    'observacion'         => $observacion,
                    'activo'              => 1,
                    'fecha_creacion'      => $now,
                ]);

                // 4. Insertar historial de estado
                DB::table('movimiento_historial_estado')->insert([
                    'id_movimiento'    => $movimiento->id_movimiento,
                    'estado_anterior'  => MovimientoBancario::ESTADO_SIN_MATCH,
                    'estado_nuevo'     => MovimientoBancario::ESTADO_CONCILIADO_MANUAL,
                    'usuario_id'       => $userId,
                    'motivo'           => $request->motivo,
                    'fecha_transicion' => $now,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Movimiento conciliado manualmente con éxito.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conciliar el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descarta un movimiento marcándolo como IGNORADO.
     */
    public function descartar(Request $request, $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:10',
        ]);

        $movimiento = MovimientoBancario::findOrFail($id);

        if ($movimiento->estado_conciliacion !== MovimientoBancario::ESTADO_SIN_MATCH) {
            return response()->json([
                'success' => false,
                'message' => 'El movimiento ya no se encuentra en estado SIN_MATCH.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($movimiento, $request) {
                $now = now();
                $userId = auth()->id();

                // 1. Marcar movimiento como IGNORADO
                DB::table('movimiento_bancario')
                    ->where('id_movimiento', $movimiento->id_movimiento)
                    ->update([
                        'estado_conciliacion'    => MovimientoBancario::ESTADO_IGNORADO,
                        'usuario_conciliador_id' => $userId,
                        'fecha_conciliacion'     => $now,
                    ]);

                // 2. Insertar historial de estado
                DB::table('movimiento_historial_estado')->insert([
                    'id_movimiento'    => $movimiento->id_movimiento,
                    'estado_anterior'  => MovimientoBancario::ESTADO_SIN_MATCH,
                    'estado_nuevo'     => MovimientoBancario::ESTADO_IGNORADO,
                    'usuario_id'       => $userId,
                    'motivo'           => $request->motivo,
                    'fecha_transicion' => $now,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Movimiento descartado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descartar el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revisa una conciliación automática: confirma o descarta (revierte).
     */
    public function revisarAutoConciliacion(Request $request, $id): JsonResponse
    {
        $request->validate([
            'accion' => 'required|string|in:confirmar,descartar',
            'motivo' => 'required_if:accion,descartar|string|min:10',
        ]);

        $movimiento = MovimientoBancario::findOrFail($id);

        // Solo puede revisar movimientos CONCILIADOS automáticamente
        if ($movimiento->estado_conciliacion !== MovimientoBancario::ESTADO_CONCILIADO) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden revisar movimientos en estado CONCILIADO.',
            ], 422);
        }

        try {
            if ($request->accion === 'confirmar') {
                // Confirmar: simplemente registrar la confirmación en el historial
                DB::transaction(function () use ($movimiento) {
                    $now = now();
                    $userId = auth()->id();
                    $userName = auth()->user() ? (auth()->user()->nombre . ' ' . auth()->user()->apellido) : 'Sistema';

                    DB::table('movimiento_historial_estado')->insert([
                        'id_movimiento'    => $movimiento->id_movimiento,
                        'estado_anterior'  => MovimientoBancario::ESTADO_CONCILIADO,
                        'estado_nuevo'     => MovimientoBancario::ESTADO_CONCILIADO,
                        'usuario_id'       => $userId,
                        'motivo'           => 'Conciliación automática confirmada por ' . $userName,
                        'fecha_transicion' => $now,
                    ]);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Conciliación automática confirmada.',
                ]);
            } else {
                // Descartar: revertir la conciliación automática
                DB::transaction(function () use ($movimiento, $request) {
                    $now = now();
                    $userId = auth()->id();
                    $userName = auth()->user() ? (auth()->user()->nombre . ' ' . auth()->user()->apellido) : 'Sistema';

                    $factura = DB::table('factura')->where('id_factura', $movimiento->id_factura)->first();

                    if ($factura) {
                        // Determinar el estado original de la factura
                        $estadoOriginal = 'PENDIENTE';
                        if ($factura->fecha_vencimiento && Carbon::parse($factura->fecha_vencimiento)->isPast()) {
                            $estadoOriginal = 'VENCIDO';
                        }

                        // 1. Revertir factura a estado pendiente/vencido
                        DB::table('factura')
                            ->where('id_factura', $factura->id_factura)
                            ->update([
                                'estado'              => $estadoOriginal,
                                'monto_abonado'       => 0,
                                'monto_pendiente'     => $factura->importe_total,
                                'id_movimiento'       => null,
                                'estado_conciliacion' => null,
                                'fecha_actualizacion' => $now,
                            ]);

                        // 2. Soft-delete de los pagos asociados (activo = 0)
                        DB::table('pago_factura')
                            ->where('id_factura', $factura->id_factura)
                            ->where('activo', 1)
                            ->update([
                                'activo'              => 0,
                                'fecha_actualizacion' => $now,
                            ]);
                    }

                    // 3. Revertir movimiento a SIN_MATCH
                    DB::table('movimiento_bancario')
                        ->where('id_movimiento', $movimiento->id_movimiento)
                        ->update([
                            'estado_conciliacion'    => MovimientoBancario::ESTADO_SIN_MATCH,
                            'id_cliente'             => null,
                            'id_factura'             => null,
                            'usuario_conciliador_id' => $userId,
                            'fecha_conciliacion'     => $now,
                        ]);

                    // 4. Insertar historial
                    DB::table('movimiento_historial_estado')->insert([
                        'id_movimiento'    => $movimiento->id_movimiento,
                        'estado_anterior'  => MovimientoBancario::ESTADO_CONCILIADO,
                        'estado_nuevo'     => MovimientoBancario::ESTADO_SIN_MATCH,
                        'usuario_id'       => $userId,
                        'motivo'           => 'Conciliación automática descartada por ' . $userName . '. Motivo: ' . $request->motivo,
                        'fecha_transicion' => $now,
                    ]);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Conciliación automática revertida. El movimiento vuelve a la bandeja.',
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revisar la conciliación: ' . $e->getMessage(),
            ], 500);
        }
    }
}
