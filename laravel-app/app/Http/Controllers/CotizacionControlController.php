<?php

namespace App\Http\Controllers;

use App\Services\ValorizacionOcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CotizacionControlController extends Controller
{
    private function cotizacion(int $id): object
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $id)->where('activo', 1)->first();
        abort_unless($cotizacion, 404);
        return $cotizacion;
    }

    public function storeOc(Request $request, int $id, ValorizacionOcService $service)
    {
        $cotizacion = $this->cotizacion($id);
        abort_unless($cotizacion->tipo_cotizacion === 'MAQUINARIA', 422);
        $yaTieneOc = DB::table('cotizacion_orden_compra')->where('id_cotizacion', $id)->exists();
        $v = $request->validate([
            'numero' => ['required', 'string', 'max:100', Rule::unique('cotizacion_orden_compra', 'numero')],
            'horas_autorizadas' => 'required|numeric|gt:0',
            'archivo_oc' => ($yaTieneOc ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
        ]);
        $ruta = $request->hasFile('archivo_oc')
            ? $request->file('archivo_oc')->store("cotizaciones/ordenes/{$id}", 's3') : null;
        try {
            DB::transaction(function () use ($id, $v, $ruta) {
                DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
                if (!$ruta && DB::table('cotizacion_orden_compra')->where('id_cotizacion', $id)->exists()) {
                    throw ValidationException::withMessages(['archivo_oc' => 'La orden adicional requiere PDF o imagen.']);
                }
                DB::table('cotizacion_orden_compra')->insert([
                    'id_cotizacion' => $id, 'numero' => $v['numero'],
                    'horas_autorizadas' => $v['horas_autorizadas'], 'ruta_documento' => $ruta,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('cotizacion')->where('id_cotizacion', $id)->update([
                    'control_oc_activo' => 1, 'fecha_actualizacion' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            if ($ruta) Storage::disk('s3')->delete($ruta);
            throw $e;
        }
        return response()->json(['success' => true, 'resumen' => $service->resumen($id)]);
    }

    public function updateOc(Request $request, int $id, int $ocId, ValorizacionOcService $service)
    {
        $this->cotizacion($id);
        $oc = DB::table('cotizacion_orden_compra')->where('id_cotizacion', $id)->where('id_orden_compra', $ocId)->first();
        abort_unless($oc, 404);
        $v = $request->validate([
            'numero' => ['required', 'string', 'max:100', Rule::unique('cotizacion_orden_compra', 'numero')->ignore($ocId, 'id_orden_compra')],
            'horas_autorizadas' => 'required|numeric|gt:0',
            'archivo_oc' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
        ]);
        $ruta = $request->hasFile('archivo_oc')
            ? $request->file('archivo_oc')->store("cotizaciones/ordenes/{$id}", 's3') : null;
        try {
            DB::transaction(function () use ($id, $ocId, $v, $ruta) {
                DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
                $data = ['numero' => $v['numero'], 'horas_autorizadas' => $v['horas_autorizadas'], 'updated_at' => now()];
                if ($ruta) $data['ruta_documento'] = $ruta;
                DB::table('cotizacion_orden_compra')->where('id_orden_compra', $ocId)->where('id_cotizacion', $id)->update($data);
            });
        } catch (\Throwable $e) {
            if ($ruta) Storage::disk('s3')->delete($ruta);
            throw $e;
        }
        return response()->json(['success' => true, 'resumen' => $service->resumen($id)]);
    }

    public function destroyOc(int $id, int $ocId, ValorizacionOcService $service)
    {
        $this->cotizacion($id);
        $oc = DB::table('cotizacion_orden_compra')->where('id_cotizacion', $id)->where('id_orden_compra', $ocId)->first();
        abort_unless($oc, 404);

        $enUso = DB::table('maquinaria_cotizacion')
            ->where('id_cotizacion', $id)->where('id_orden_compra', $ocId)->where('activo', 1)->exists();
        if ($enUso) {
            throw ValidationException::withMessages(['orden' => 'No se puede eliminar: hay filas asignadas a esta orden de compra.']);
        }

        DB::transaction(function () use ($id, $ocId) {
            DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
            DB::table('cotizacion_orden_compra')->where('id_cotizacion', $id)->where('id_orden_compra', $ocId)->delete();
        });

        if ($oc->ruta_documento) Storage::disk('s3')->delete($oc->ruta_documento);

        return response()->json(['success' => true, 'resumen' => $service->resumen($id)]);
    }

    /** Asigna (o quita, con id_orden_compra null) la OC de una fila de maquinaria. */
    public function assignOcToRow(Request $request, int $id, int $rowId)
    {
        $cotizacion = $this->cotizacion($id);
        abort_unless($cotizacion->tipo_cotizacion === 'MAQUINARIA', 422);
        $v = $request->validate([
            'id_orden_compra' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($id, $rowId, $v) {
            DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
            $fila = DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $rowId)
                ->where('id_cotizacion', $id)->where('activo', 1)->lockForUpdate()->first();
            if (!$fila) {
                throw ValidationException::withMessages(['row' => 'La fila no pertenece a esta valorización.']);
            }

            if (!empty($v['id_orden_compra'])) {
                $oc = DB::table('cotizacion_orden_compra')
                    ->where('id_cotizacion', $id)->where('id_orden_compra', $v['id_orden_compra'])->first();
                if (!$oc) {
                    throw ValidationException::withMessages(['id_orden_compra' => 'La OC no pertenece a esta valorización.']);
                }
            }

            DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $rowId)
                ->where('id_cotizacion', $id)
                ->update(['id_orden_compra' => $v['id_orden_compra'] ?? null, 'fecha_actualizacion' => now()]);
        });

        return response()->json(['success' => true, 'resumen' => app(ValorizacionOcService::class)->resumen($id)]);
    }

    public function unassignOcFromRow(int $id, int $rowId)
    {
        $cotizacion = $this->cotizacion($id);
        abort_unless($cotizacion->tipo_cotizacion === 'MAQUINARIA', 422);

        DB::transaction(function () use ($id, $rowId) {
            DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
            $updated = DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $rowId)
                ->where('id_cotizacion', $id)->where('activo', 1)->whereNotNull('id_orden_compra')
                ->update(['id_orden_compra' => null, 'fecha_actualizacion' => now()]);
            abort_unless($updated, 404);
        });

        return response()->json(['success' => true, 'resumen' => app(ValorizacionOcService::class)->resumen($id)]);
    }

    public function assignHes(Request $request, int $id)
    {
        $cotizacion = $this->cotizacion($id);
        abort_unless($cotizacion->usa_hes, 422);
        $v = $request->validate([
            'row_ids' => 'required|array|min:1',
            'row_ids.*' => 'required|integer|distinct',
            'id_hes' => 'nullable|integer',
            'codigo' => ['required_without:id_hes', 'nullable', 'string', 'max:100', Rule::unique('cotizacion_hes', 'codigo')],
            'archivo_hes' => 'required_without:id_hes|nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
        ]);
        $table = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $pk = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'id_cotizacion_maqu' : 'id_cotizacion_agr';
        $ruta = !$request->filled('id_hes') && $request->hasFile('archivo_hes')
            ? $request->file('archivo_hes')->store("cotizaciones/hes/{$id}", 's3') : null;
        try {
            DB::transaction(function () use ($id, $v, $table, $pk, $ruta, $cotizacion) {
                DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
                $filas = DB::table($table)->where('id_cotizacion', $id)->where('activo', 1)
                    ->whereIn($pk, $v['row_ids'])->lockForUpdate()->get();
                if ($filas->count() !== count($v['row_ids']) || $filas->contains(function ($fila) use ($cotizacion) {
                    $facturable = $fila->es_facturable === null ? (float) $fila->total_fila > 0 : (bool) $fila->es_facturable;
                    return $fila->id_hes || !$facturable ||
                        ($cotizacion->tipo_cotizacion === 'MAQUINARIA' ? ValorizacionOcService::horasFacturables($fila) <= 0 : (float) $fila->m3 <= 0);
                })) {
                    throw ValidationException::withMessages(['row_ids' => 'Selecciona solo filas facturables, activas y sin HES de esta valorización.']);
                }
                if (!empty($v['id_hes'])) {
                    $hes = DB::table('cotizacion_hes')->where('id_cotizacion', $id)->where('id_hes', $v['id_hes'])->first();
                    if (!$hes) throw ValidationException::withMessages(['id_hes' => 'El HES no pertenece a esta valorización.']);
                    $hesId = $hes->id_hes;
                } else {
                    $hesId = DB::table('cotizacion_hes')->insertGetId([
                        'id_cotizacion' => $id, 'codigo' => $v['codigo'], 'ruta_documento' => $ruta,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table($table)->where('id_cotizacion', $id)->whereIn($pk, $v['row_ids'])
                    ->update(['id_hes' => $hesId, 'fecha_actualizacion' => now()]);
            });
        } catch (\Throwable $e) {
            if ($ruta) Storage::disk('s3')->delete($ruta);
            throw $e;
        }
        return response()->json(['success' => true]);
    }

    public function unassignHes(int $id, int $rowId)
    {
        $cotizacion = $this->cotizacion($id);
        $table = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $pk = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'id_cotizacion_maqu' : 'id_cotizacion_agr';
        DB::transaction(function () use ($id, $rowId, $table, $pk) {
            DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
            $updated = DB::table($table)->where('id_cotizacion', $id)->where($pk, $rowId)
                ->where('activo', 1)->whereNotNull('id_hes')
                ->update(['id_hes' => null, 'fecha_actualizacion' => now()]);
            abort_unless($updated, 404);
        });
        return response()->json(['success' => true]);
    }

    public function destroyHes(int $id, int $hesId)
    {
        $cotizacion = $this->cotizacion($id);
        $hes = DB::table('cotizacion_hes')->where('id_cotizacion', $id)->where('id_hes', $hesId)->first();
        abort_unless($hes, 404);

        $table = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $enUso = DB::table($table)->where('id_cotizacion', $id)->where('id_hes', $hesId)->where('activo', 1)->exists();
        if ($enUso) {
            throw ValidationException::withMessages(['hes' => 'No se puede eliminar: hay filas con este HES asignado.']);
        }

        DB::transaction(function () use ($id, $hesId) {
            DB::table('cotizacion')->where('id_cotizacion', $id)->lockForUpdate()->first();
            DB::table('cotizacion_hes')->where('id_cotizacion', $id)->where('id_hes', $hesId)->delete();
        });

        if ($hes->ruta_documento) Storage::disk('s3')->delete($hes->ruta_documento);

        return response()->json(['success' => true]);
    }

    public function documentoOc(int $id, int $ocId)
    {
        $this->cotizacion($id);
        $oc = DB::table('cotizacion_orden_compra')->where('id_cotizacion', $id)->where('id_orden_compra', $ocId)->first();
        abort_unless($oc && $oc->ruta_documento, 404);
        return Storage::disk('s3')->response($oc->ruta_documento);
    }

    public function documentoHes(int $id, int $hesId)
    {
        $this->cotizacion($id);
        $hes = DB::table('cotizacion_hes')->where('id_cotizacion', $id)->where('id_hes', $hesId)->first();
        abort_unless($hes && $hes->ruta_documento, 404);
        return Storage::disk('s3')->response($hes->ruta_documento);
    }
}
