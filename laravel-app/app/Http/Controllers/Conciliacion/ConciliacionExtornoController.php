<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use App\Models\Extorno;
use App\Models\MovimientoBancario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConciliacionExtornoController extends Controller
{
    /** Monto a partir del cual se requiere aprobación de un administrador */
    private const UMBRAL_APROBACION = 5000.00;

    /** ID de rol para Operador */
    private const ROL_OPERADOR = 2;

    /** ID de rol para Administrador */
    private const ROL_ADMIN = 1;

    /**
     * HU-E4-03: Solicitar extorno de un movimiento conciliado.
     */
    public function solicitar(Request $request, $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:10',
        ]);

        $movimiento = MovimientoBancario::findOrFail($id);

        // Verificar que el movimiento esté en un estado conciliable
        $estadosConciliados = [
            MovimientoBancario::ESTADO_CONCILIADO,
            MovimientoBancario::ESTADO_CONCILIADO_TOLERANCIA,
            MovimientoBancario::ESTADO_CONCILIADO_MANUAL,
        ];

        if (!in_array($movimiento->estado_conciliacion, $estadosConciliados, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden extornar movimientos en estado conciliado.',
            ], 422);
        }

        $usuario = auth()->user();
        $monto = $movimiento->importe;

        // Si el monto supera el umbral Y el usuario es Operador, requiere aprobación
        if ($monto > self::UMBRAL_APROBACION && $usuario && $usuario->id_rol == self::ROL_OPERADOR) {
            try {
                DB::transaction(function () use ($movimiento, $request, $monto) {
                    $now = now();
                    $userId = auth()->id();

                    // Crear extorno en estado pendiente de aprobación
                    DB::table('extorno')->insert([
                        'id_movimiento'   => $movimiento->id_movimiento,
                        'usuario_id'      => $userId,
                        'aprobado_por_id' => null,
                        'motivo'          => $request->motivo,
                        'monto'           => $monto,
                        'estado'          => Extorno::ESTADO_PENDIENTE,
                        'fecha_extorno'   => $now,
                    ]);

                    // Registrar en historial
                    DB::table('movimiento_historial_estado')->insert([
                        'id_movimiento'    => $movimiento->id_movimiento,
                        'estado_anterior'  => $movimiento->estado_conciliacion,
                        'estado_nuevo'     => $movimiento->estado_conciliacion, // no cambia aún
                        'usuario_id'       => $userId,
                        'motivo'           => 'Solicitud de extorno pendiente de aprobación. Motivo: ' . $request->motivo,
                        'fecha_transicion' => $now,
                    ]);
                });

                return response()->json([
                    'success'             => true,
                    'message'             => 'La solicitud de extorno requiere aprobación de un administrador.',
                    'requiere_aprobacion' => true,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la solicitud de extorno: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Si no requiere aprobación, ejecutar directamente
        try {
            $this->ejecutarExtorno($movimiento, $request->motivo);

            return response()->json([
                'success'             => true,
                'message'             => 'Extorno ejecutado correctamente.',
                'requiere_aprobacion' => false,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el extorno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprueba una solicitud de extorno pendiente (solo Admin).
     */
    public function aprobar(Request $request, $id): JsonResponse
    {
        $usuario = auth()->user();
        if (!$usuario || $usuario->id_rol != self::ROL_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede aprobar extornos.',
            ], 403);
        }

        $extorno = DB::table('extorno')->where('id_extorno', $id)->first();
        if (!$extorno) {
            return response()->json([
                'success' => false,
                'message' => 'Extorno no encontrado.',
            ], 404);
        }

        if ($extorno->estado !== Extorno::ESTADO_PENDIENTE) {
            return response()->json([
                'success' => false,
                'message' => 'El extorno no se encuentra en estado pendiente de aprobación.',
            ], 422);
        }

        try {
            $movimiento = MovimientoBancario::findOrFail($extorno->id_movimiento);

            DB::transaction(function () use ($movimiento, $extorno, $id) {
                $now = now();
                $userId = auth()->id();

                // Ejecutar el extorno
                $this->ejecutarExtorno($movimiento, $extorno->motivo);

                // Actualizar extorno como EJECUTADO
                DB::table('extorno')
                    ->where('id_extorno', $id)
                    ->update([
                        'estado'          => Extorno::ESTADO_EJECUTADO,
                        'aprobado_por_id' => $userId,
                        'fecha_extorno'   => $now,
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Extorno aprobado y ejecutado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el extorno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechaza una solicitud de extorno pendiente.
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        $usuario = auth()->user();
        if (!$usuario || $usuario->id_rol != self::ROL_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede rechazar extornos.',
            ], 403);
        }

        $extorno = DB::table('extorno')->where('id_extorno', $id)->first();
        if (!$extorno) {
            return response()->json([
                'success' => false,
                'message' => 'Extorno no encontrado.',
            ], 404);
        }

        if ($extorno->estado !== Extorno::ESTADO_PENDIENTE) {
            return response()->json([
                'success' => false,
                'message' => 'El extorno no se encuentra en estado pendiente de aprobación.',
            ], 422);
        }

        try {
            $now = now();

            DB::table('extorno')
                ->where('id_extorno', $id)
                ->update([
                    'estado'          => Extorno::ESTADO_RECHAZADO,
                    'aprobado_por_id' => auth()->id(),
                    'fecha_extorno'   => $now,
                ]);

            // Registrar en historial
            DB::table('movimiento_historial_estado')->insert([
                'id_movimiento'    => $extorno->id_movimiento,
                'estado_anterior'  => MovimientoBancario::ESTADO_CONCILIADO,
                'estado_nuevo'     => MovimientoBancario::ESTADO_CONCILIADO,
                'usuario_id'       => auth()->id(),
                'motivo'           => 'Extorno rechazado por administrador.',
                'fecha_transicion' => $now,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Extorno rechazado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el extorno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejecuta el extorno: revierte la factura, soft-deletea el pago,
     * marca el movimiento como EXTORNADO e inserta historial.
     *
     * @param MovimientoBancario $movimiento
     * @param string $motivo
     * @return void
     */
    private function ejecutarExtorno(MovimientoBancario $movimiento, string $motivo): void
    {
        $now = now();
        $userId = auth()->id();
        $userName = auth()->user() ? (auth()->user()->nombre . ' ' . auth()->user()->apellido) : 'Sistema';
        $estadoAnterior = $movimiento->estado_conciliacion;

        // 1. Revertir la factura asociada
        if ($movimiento->id_factura) {
            $factura = DB::table('factura')->where('id_factura', $movimiento->id_factura)->first();

            if ($factura) {
                // Determinar el estado original
                $estadoOriginal = 'PENDIENTE';
                if ($factura->fecha_vencimiento && Carbon::parse($factura->fecha_vencimiento)->isPast()) {
                    $estadoOriginal = 'VENCIDO';
                }

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
        }

        // 3. Marcar movimiento como EXTORNADO
        DB::table('movimiento_bancario')
            ->where('id_movimiento', $movimiento->id_movimiento)
            ->update([
                'estado_conciliacion'    => MovimientoBancario::ESTADO_EXTORNADO,
                'id_cliente'             => null,
                'id_factura'             => null,
                'usuario_conciliador_id' => $userId,
                'fecha_conciliacion'     => $now,
            ]);

        // 4. Insertar historial de estado
        DB::table('movimiento_historial_estado')->insert([
            'id_movimiento'    => $movimiento->id_movimiento,
            'estado_anterior'  => $estadoAnterior,
            'estado_nuevo'     => MovimientoBancario::ESTADO_EXTORNADO,
            'usuario_id'       => $userId,
            'motivo'           => 'Extorno ejecutado por ' . $userName . '. Motivo: ' . $motivo,
            'fecha_transicion' => $now,
        ]);
    }
}
