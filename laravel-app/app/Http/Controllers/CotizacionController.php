<?php

namespace App\Http\Controllers;

use App\Services\ValorizacionOcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class CotizacionController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS DE ALMACENAMIENTO S3
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Sube un archivo al disco S3 y devuelve la ruta relativa.
     * Las carpetas quedan ordenadas así:
     *   - Partes diario (imagen): cotizaciones/partes/{id}/
     *   - GRR (PDF/imagen):       cotizaciones/grr/{id}/
     */
    private function uploadToS3(Request $request, string $inputName, string $folder): ?string
    {
        if (! $request->hasFile($inputName)) {
            return null;
        }

        $path = $request->file($inputName)->store($folder, 's3');

        return $path ?: null;
    }

    private function resolveS3FileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
        } catch (\Throwable $e) {
            return Storage::disk('s3')->url($path);
        }
    }

    /**
     * Devuelve la URL pública de un archivo almacenado en S3.
     * Úsalo en las vistas con: CotizacionController::s3Url($ruta)
     * o directamente: Storage::disk('s3')->url($ruta)
     */
    public static function s3Url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('s3')->url($path);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LIST
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tipo     = $request->input('tipo');
        $cliente  = $request->input('id_cliente');
        $desde    = $request->input('fecha_desde');
        $hasta    = $request->input('fecha_hasta');
        $search   = $request->input('search');

        $query = DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->where('c.activo', 1)
            ->select([
                'c.id_cotizacion', 'c.tipo_cotizacion', 'c.numero_valorizacion', 'c.orden_compra', 'c.control_oc_activo',
                'c.obra', 'c.periodo_inicio', 'c.periodo_fin',
                'c.base_sin_igv', 'c.total_igv', 'c.total',
                'c.fecha_creacion',
                'cl.razon_social', 'cl.ruc',
            ])
            ->orderByDesc('c.fecha_creacion');

        if ($tipo)    $query->where('c.tipo_cotizacion', $tipo);
        if ($cliente) $query->where('c.id_cliente', $cliente);
        if ($desde)   $query->where('c.periodo_inicio', '>=', $desde);
        if ($hasta)   $query->where('c.periodo_fin', '<=', $hasta);
        if ($search)  $query->where(function ($q) use ($search) {
            $q->where('c.obra', 'like', "%{$search}%")
                ->orWhere('c.numero_valorizacion', 'like', "%{$search}%")
                ->orWhere('c.orden_compra', 'like', "%{$search}%")
                ->orWhereExists(function ($sub) use ($search) {
                    $sub->selectRaw('1')->from('cotizacion_orden_compra as oc')
                        ->whereColumn('oc.id_cotizacion', 'c.id_cotizacion')
                        ->where('oc.numero', 'like', "%{$search}%");
                })
                ->orWhere('cl.razon_social', 'like', "%{$search}%");
        });

        $cotizaciones = $query->get();
        $ordenesPorCotizacion = $cotizaciones->isEmpty() ? collect() : DB::table('cotizacion_orden_compra')
            ->whereIn('id_cotizacion', $cotizaciones->pluck('id_cotizacion'))
            ->orderBy('id_orden_compra')->get()->groupBy('id_cotizacion');
        $cotizaciones->each(function ($cot) use ($ordenesPorCotizacion) {
            $cot->orden_compra_mostrar = $cot->control_oc_activo
                ? $ordenesPorCotizacion->get($cot->id_cotizacion, collect())->pluck('numero')->implode(' · ')
                : $cot->orden_compra;
        });

        $clientes = DB::table('cliente')->where('activo', 1)
            ->orderBy('razon_social')->get(['id_cliente', 'razon_social']);

        return view('cotizaciones.index', compact('cotizaciones', 'clientes',
            'tipo', 'cliente', 'desde', 'hasta', 'search'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CREATE / STORE
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        $clientes    = DB::table('cliente')->where('activo', 1)->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);
        $maquinarias = DB::table('maquinaria')->where('activo', 1)->orderBy('nombre')->get();
        $agregados   = DB::table('agregado')->where('activo', 1)->orderBy('nombre')->get();
        $choferes    = DB::table('chofer')->where('activo', 1)->orderBy('nombres')->get();

        return view('cotizaciones.create', compact('clientes', 'maquinarias', 'agregados', 'choferes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_cotizacion'   => 'required|in:MAQUINARIA,AGREGADO',
            'id_cliente'        => 'required|integer|exists:cliente,id_cliente',
            'id_maquinaria'     => 'nullable|required_if:tipo_cotizacion,MAQUINARIA|integer|exists:maquinaria,id_maquinaria',
            'id_agregado'       => 'nullable|required_if:tipo_cotizacion,AGREGADO|integer|exists:agregado,id_agregado',
            'numero_valorizacion' => 'required|string|max:20',
            'orden_compra'      => 'nullable|required_if:tipo_cotizacion,MAQUINARIA|string|max:100',
            'horas_oc'          => 'nullable|required_if:tipo_cotizacion,MAQUINARIA|numeric|gt:0',
            'archivo_oc'        => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'usa_hes'           => 'nullable|boolean',
            'obra'              => 'required|string|max:250',
            'periodo_inicio'    => 'required|date',
            'periodo_fin'       => 'required|date|after_or_equal:periodo_inicio',
        ]);

        $rutaOc = $this->uploadToS3($request, 'archivo_oc', 'cotizaciones/ordenes');
        try {
            $id = DB::transaction(function () use ($validated, $rutaOc) {
                $id = DB::table('cotizacion')->insertGetId([
            'id_cliente'          => $validated['id_cliente'],
            'id_maquinaria'       => $validated['tipo_cotizacion'] === 'MAQUINARIA' ? ($validated['id_maquinaria'] ?? null) : null,
            'id_agregado'         => $validated['tipo_cotizacion'] === 'AGREGADO'   ? ($validated['id_agregado'] ?? null) : null,
            'tipo_cotizacion'     => $validated['tipo_cotizacion'],
            'numero_valorizacion' => $validated['numero_valorizacion'],
            'orden_compra'       => $validated['orden_compra'] ?? null,
            'control_oc_activo'  => $validated['tipo_cotizacion'] === 'MAQUINARIA',
            'usa_hes'            => (bool) ($validated['usa_hes'] ?? false),
            'obra'                => $validated['obra'],
            'periodo_inicio'      => $validated['periodo_inicio'],
            'periodo_fin'         => $validated['periodo_fin'],
            'base_sin_igv'        => 0,
            'total_igv'           => 0,
            'total'               => 0,
            'activo'              => 1,
            'fecha_creacion'      => now(),
                ]);
                if ($validated['tipo_cotizacion'] === 'MAQUINARIA') {
                    DB::table('cotizacion_orden_compra')->insert([
                        'id_cotizacion' => $id,
                        'numero' => $validated['orden_compra'],
                        'horas_autorizadas' => $validated['horas_oc'],
                        'ruta_documento' => $rutaOc,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                return $id;
            });
        } catch (\Throwable $e) {
            if ($rutaOc) Storage::disk('s3')->delete($rutaOc);
            throw $e;
        }

        return redirect()->route('cotizaciones.show', $id)
            ->with('success', 'Cotización creada. Ahora agrega las filas.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHOW
    // ──────────────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $cotizacion = DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('maquinaria as m', 'm.id_maquinaria', '=', 'c.id_maquinaria')
            ->leftJoin('agregado as a', 'a.id_agregado', '=', 'c.id_agregado')
            ->where('c.id_cotizacion', $id)->where('c.activo', 1)
            ->select('c.*', 'cl.razon_social', 'cl.ruc', 'cl.celular', 'cl.correo', 'cl.direccion_fiscal',
                'm.nombre as maquinaria_nombre',
                'a.nombre as agregado_nombre')
            ->first();

        if (!$cotizacion) abort(404);

        $filas = $this->getFilas($cotizacion);
        $ocResumen = $cotizacion->tipo_cotizacion === 'MAQUINARIA'
            ? app(ValorizacionOcService::class)->resumen($id) : null;
        $hesList = DB::table('cotizacion_hes')->where('id_cotizacion', $id)->orderBy('id_hes')->get();

        $clientes    = DB::table('cliente')->where('activo', 1)->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);
        $maquinarias = DB::table('maquinaria')->where('activo', 1)->orderBy('nombre')->get();
        $agregados   = DB::table('agregado')->where('activo', 1)->orderBy('nombre')->get();
        $choferes    = DB::table('chofer')->where('activo', 1)->orderBy('nombres')->get();

        return view('cotizaciones.show', compact(
            'cotizacion', 'filas', 'clientes', 'maquinarias', 'agregados', 'choferes', 'ocResumen', 'hesList'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UPDATE HEADER
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'tipo_cotizacion'     => 'required|in:MAQUINARIA,AGREGADO',
            'id_cliente'          => 'required|integer|exists:cliente,id_cliente',
            'id_maquinaria'       => 'nullable|required_if:tipo_cotizacion,MAQUINARIA|integer|exists:maquinaria,id_maquinaria',
            'id_agregado'         => 'nullable|required_if:tipo_cotizacion,AGREGADO|integer|exists:agregado,id_agregado',
            'numero_valorizacion' => 'required|string|max:20',
            'orden_compra'      => 'nullable|string|max:100',
            'usa_hes'           => 'nullable|boolean',
            'obra'                => 'required|string|max:250',
            'periodo_inicio'      => 'required|date',
            'periodo_fin'         => 'required|date|after_or_equal:periodo_inicio',
        ]);

        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $id)->where('activo', 1)->first();
        abort_unless($cotizacion, 404);
        if ($cotizacion->tipo_cotizacion !== $validated['tipo_cotizacion']) {
            return response()->json(['success' => false, 'message' => 'No se puede cambiar el tipo de una valorización existente.'], 422);
        }
        if ($cotizacion->control_oc_activo && $validated['orden_compra'] !== $cotizacion->orden_compra) {
            return response()->json(['success' => false, 'message' => 'Edita cada orden de compra desde el panel de OCs.'], 422);
        }
        $usaHes = (bool) ($validated['usa_hes'] ?? $cotizacion->usa_hes);
        $tablaFilas = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        if (!$usaHes && $cotizacion->usa_hes && DB::table($tablaFilas)->where('id_cotizacion', $id)->whereNotNull('id_hes')->exists()) {
            return response()->json(['success' => false, 'message' => 'Desasigna primero todas las filas HES.'], 422);
        }
        DB::table('cotizacion')->where('id_cotizacion', $id)->update([
            'id_cliente'          => $validated['id_cliente'],
            'id_maquinaria'       => $validated['tipo_cotizacion'] === 'MAQUINARIA' ? ($validated['id_maquinaria'] ?? null) : null,
            'id_agregado'         => $validated['tipo_cotizacion'] === 'AGREGADO'   ? ($validated['id_agregado'] ?? null) : null,
            'tipo_cotizacion'     => $validated['tipo_cotizacion'],
            'numero_valorizacion' => $validated['numero_valorizacion'],
            'orden_compra'       => $validated['orden_compra'] ?? null,
            'usa_hes'            => $usaHes,
            'obra'                => $validated['obra'],
            'periodo_inicio'      => $validated['periodo_inicio'],
            'periodo_fin'         => $validated['periodo_fin'],
            'fecha_actualizacion' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Cotización actualizada.']);
    }

    public function updateCliente(Request $request, int $id)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $id)->first();
        if (!$cotizacion) {
            return response()->json(['success' => false, 'message' => 'Cotización no encontrada.'], 404);
        }

        $cliente = DB::table('cliente')->where('id_cliente', $cotizacion->id_cliente)->first();
        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        $validated = $request->validate([
            'razon_social'     => 'required|string|max:200',
            'ruc'              => 'required|string|min:8|max:15|unique:cliente,ruc,' . $cliente->id_cliente . ',id_cliente',
            'celular'          => 'nullable|string|max:15',
            'correo'           => 'nullable|email|max:150',
            'direccion_fiscal' => 'nullable|string|max:250',
        ]);

        DB::table('cliente')
            ->where('id_cliente', $cliente->id_cliente)
            ->update(array_merge($validated, ['fecha_actualizacion' => now()]));

        return response()->json(['success' => true, 'message' => 'Datos del cliente actualizados correctamente.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRINT VIEW
    // ──────────────────────────────────────────────────────────────────────────

    public function print(int $id)
    {
        $cotizacion = DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->where('c.id_cotizacion', $id)->where('c.activo', 1)
            ->select('c.*', 'cl.razon_social', 'cl.ruc')
            ->first();

        if (!$cotizacion) abort(404);

        $filas = $this->getFilas($cotizacion);
        $ocResumen = $cotizacion->tipo_cotizacion === 'MAQUINARIA'
            ? app(ValorizacionOcService::class)->resumen($id) : null;

        return view('cotizaciones.print', compact('cotizacion', 'filas', 'ocResumen'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DELETE
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        DB::table('cotizacion')->where('id_cotizacion', $id)
            ->update(['activo' => 0, 'fecha_actualizacion' => now()]);

        return response()->json(['success' => true, 'message' => 'Cotización eliminada.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AJAX – ROWS
    // ──────────────────────────────────────────────────────────────────────────

    public function storeRow(Request $request, int $idCotizacion)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();
        if (!$cotizacion || !$cotizacion->activo) return response()->json(['error' => 'Cotización no encontrada'], 404);

        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            return $this->storeMaquinariaRow($request, $cotizacion);
        } else {
            return $this->storeAgregadoRow($request, $cotizacion);
        }
    }

    private function storeMaquinariaRow(Request $request, object $cotizacion)
    {
        $parteDiarioColumn = $this->getParteDiarioColumn('maquinaria_cotizacion');

        $v = $request->validate([
            'id_chofer'      => 'required|integer|exists:chofer,id_chofer',
            'id_maquinaria'  => 'required|integer|exists:maquinaria,id_maquinaria',
            'fecha'          => 'required|date',
            'placa'          => 'nullable|string|max:20',
            'obra_maquina'   => 'nullable|string|max:250',
            'hora_inicio'    => 'required|numeric',
            'hora_fin'       => 'required|numeric|gt:hora_inicio',
            'hora_minima'    => 'required|numeric|min:0',
            'precio_hora'    => 'required|numeric|min:0',
            'cobrar_fila'    => 'nullable|in:0,1',
            'completar_salto' => 'nullable|boolean',
            'nueva_oc_numero' => ['nullable', 'required_with:nueva_oc_horas,nueva_oc_archivo', 'string', 'max:100', \Illuminate\Validation\Rule::unique('cotizacion_orden_compra', 'numero')->where('id_cotizacion', $cotizacion->id_cotizacion)],
            'nueva_oc_horas' => 'nullable|required_with:nueva_oc_numero,nueva_oc_archivo|numeric|gt:0',
            'nueva_oc_archivo' => 'nullable|required_with:nueva_oc_numero,nueva_oc_horas|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'n_parte_diario' => 'nullable|string|max:50',
            'numero_factura' => 'nullable|string|max:50',
            'imagen_parte_diario' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
        ]);

        $horasTrabajadas = round($v['hora_fin'] - $v['hora_inicio'], 2);
        $horasEfectivas  = max($horasTrabajadas, (float) $v['hora_minima']);
        $esAjuste        = false;
        $debeCobrar      = (($v['cobrar_fila'] ?? '1') === '1') && !$esAjuste;
        $totalFila       = $debeCobrar ? round($horasEfectivas * $v['precio_hora'], 2) : 0;

        // ── Subir imagen parte diario a S3 ─────────────────────────────────
        $rutaParteDiario = $this->uploadToS3(
            $request,
            'imagen_parte_diario',
            "cotizaciones/partes/{$cotizacion->id_cotizacion}"
        );
        $rutaNuevaOc = $this->uploadToS3($request, 'nueva_oc_archivo', "cotizaciones/ordenes/{$cotizacion->id_cotizacion}");

        $insertData = [
            'id_cotizacion'    => $cotizacion->id_cotizacion,
            'id_chofer'        => $v['id_chofer'],
            'id_maquinaria'    => $v['id_maquinaria'],
            'fecha'            => $v['fecha'],
            'placa'            => $v['placa'] ?? null,
            'obra_maquina'     => $v['obra_maquina'] ?? $cotizacion->obra,
            'hora_inicio'      => $v['hora_inicio'],
            'hora_fin'         => $v['hora_fin'],
            'hora_minima'      => $v['hora_minima'],
            'horas_trabajadas' => $horasTrabajadas,
            'precio_hora'      => $v['precio_hora'],
            'total_fila'       => $totalFila,
            'es_facturable'    => $debeCobrar,
            'es_ajuste_horometro' => $esAjuste,
            'n_parte_diario'   => $v['n_parte_diario'] ?? null,
            'numero_factura'   => $v['numero_factura'] ?? null,
            'activo'           => 1,
            'fecha_creacion'   => now(),
        ];

        if ($parteDiarioColumn) {
            $insertData[$parteDiarioColumn] = $rutaParteDiario;
        }

        try {
            $rowId = DB::transaction(function () use ($cotizacion, $insertData, $v, $rutaNuevaOc) {
                DB::table('cotizacion')->where('id_cotizacion', $cotizacion->id_cotizacion)->lockForUpdate()->first();
                if (!empty($v['nueva_oc_numero'])) {
                    if (!$cotizacion->control_oc_activo || !$rutaNuevaOc) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['nueva_oc_archivo' => 'La OC adicional requiere un documento.']);
                    }
                    DB::table('cotizacion_orden_compra')->insert([
                        'id_cotizacion' => $cotizacion->id_cotizacion,
                        'numero' => $v['nueva_oc_numero'], 'horas_autorizadas' => $v['nueva_oc_horas'],
                        'ruta_documento' => $rutaNuevaOc, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (!empty($v['completar_salto'])) {
                    $anterior = DB::table('maquinaria_cotizacion')
                        ->where('id_cotizacion', $cotizacion->id_cotizacion)
                        ->where('id_maquinaria', $v['id_maquinaria'])->where('activo', 1)
                        ->where(function ($q) use ($v) {
                            $q->where('fecha', '<', $v['fecha'])
                                ->orWhere(function ($same) use ($v) {
                                    $same->where('fecha', $v['fecha'])->where('hora_inicio', '<', $v['hora_inicio']);
                                });
                        })
                        ->orderByDesc('fecha')->orderByDesc('hora_fin')->first();
                    if ($anterior && (float)$v['hora_inicio'] > (float)$anterior->hora_fin + 0.05) {
                        $ajuste = $insertData;
                        $ajuste['hora_inicio'] = $anterior->hora_fin;
                        $ajuste['hora_fin'] = $v['hora_inicio'];
                        $ajuste['horas_trabajadas'] = round((float)$v['hora_inicio'] - (float)$anterior->hora_fin, 2);
                        $ajuste['hora_minima'] = 0;
                        $ajuste['precio_hora'] = 0;
                        $ajuste['total_fila'] = 0;
                        $ajuste['es_facturable'] = false;
                        $ajuste['es_ajuste_horometro'] = true;
                        $ajuste['n_parte_diario'] = null;
                        $ajuste['numero_factura'] = null;
                        if ($this->getParteDiarioColumn('maquinaria_cotizacion')) {
                            $ajuste[$this->getParteDiarioColumn('maquinaria_cotizacion')] = null;
                        }
                        DB::table('maquinaria_cotizacion')->insert($ajuste);
                    }
                }
                $rowId = DB::table('maquinaria_cotizacion')->insertGetId($insertData);
                if ($cotizacion->control_oc_activo) {
                    app(ValorizacionOcService::class)->recalcular($cotizacion->id_cotizacion);
                }
                $this->recalcularTotales($cotizacion->id_cotizacion);
                return $rowId;
            });
        } catch (\Throwable $e) {
            if ($rutaParteDiario) Storage::disk('s3')->delete($rutaParteDiario);
            if ($rutaNuevaOc) Storage::disk('s3')->delete($rutaNuevaOc);
            throw $e;
        }

        $row = DB::table('maquinaria_cotizacion as mc')
            ->join('chofer as ch', 'ch.id_chofer', '=', 'mc.id_chofer')
            ->join('maquinaria as m', 'm.id_maquinaria', '=', 'mc.id_maquinaria')
            ->where('mc.id_cotizacion_maqu', $rowId)
            ->select('mc.*', DB::raw("TRIM(CONCAT(ch.nombres,' ',COALESCE(ch.apellido_paterno,''),' ',COALESCE(ch.apellido_materno,''))) as chofer_nombre"), DB::raw("CONCAT(m.nombre, CASE WHEN m.numero_maquina IS NOT NULL AND m.numero_maquina != '' THEN CONCAT(' — ', m.numero_maquina) ELSE '' END) as maquinaria_nombre"))
            ->first();

        if ($row && $parteDiarioColumn === 'ruta_n_parte_diario' && isset($row->ruta_n_parte_diario)) {
            $row->ruta_parte_diario = $row->ruta_n_parte_diario;
        }

        // Agregar URL pública de S3 al objeto devuelto
        if ($row && isset($row->ruta_parte_diario) && $row->ruta_parte_diario !== '') {
            $row->url_parte_diario = $this->resolveS3FileUrl($row->ruta_parte_diario);
        }

        $totales = $this->getTotales($cotizacion->id_cotizacion);

        return response()->json(['success' => true, 'row' => $row, 'totales' => $totales]);
    }

    private function storeAgregadoRow(Request $request, object $cotizacion)
    {
        $parteDiarioColumn = $this->getParteDiarioColumn('agregado_cotizacion');

        $v = $request->validate([
            'id_chofer'      => 'required|integer|exists:chofer,id_chofer',
            'id_agregado'    => 'required|integer|exists:agregado,id_agregado',
            'fecha'          => 'required|date',
            'placa'          => 'nullable|string|max:20',
            'obra_agregado'  => 'nullable|string|max:250',
            'm3'             => 'required|numeric|min:0',
            'precio_m3'      => 'required|numeric|min:0',
            'cobrar_fila'    => 'nullable|in:0,1',
            'n_parte_diario' => 'nullable|string|max:50',
            'numero_factura' => 'nullable|string|max:50',
            'grr'            => 'nullable|string|max:50',
            'imagen_parte_diario' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'archivo_grr'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $debeCobrar = (($v['cobrar_fila'] ?? '1') === '1');
        $totalFila  = $debeCobrar ? round($v['m3'] * $v['precio_m3'], 2) : 0;

        // ── Subir imagen parte diario a S3 ─────────────────────────────────
        $rutaParteDiario = $this->uploadToS3(
            $request,
            'imagen_parte_diario',
            "cotizaciones/partes/{$cotizacion->id_cotizacion}"
        );

        // ── Subir PDF/imagen GRR a S3 ──────────────────────────────────────
        $rutaGrr = $this->uploadToS3(
            $request,
            'archivo_grr',
            "cotizaciones/grr/{$cotizacion->id_cotizacion}"
        );

        $insertData = [
            'id_cotizacion'    => $cotizacion->id_cotizacion,
            'id_chofer'        => $v['id_chofer'],
            'id_agregado'      => $v['id_agregado'],
            'fecha'            => $v['fecha'],
            'placa'            => $v['placa'] ?? null,
            'obra_agregado'    => $v['obra_agregado'] ?? $cotizacion->obra,
            'm3'               => $v['m3'],
            'precio_m3'        => $v['precio_m3'],
            'total_fila'       => $totalFila,
            'es_facturable'    => $debeCobrar,
            'n_parte_diario'   => $v['n_parte_diario'] ?? null,
            'numero_factura'   => $v['numero_factura'] ?? null,
            'grr'              => $v['grr'] ?? null,
            'activo'           => 1,
            'fecha_creacion'   => now(),
        ];

        if ($this->tableHasColumn('agregado_cotizacion', 'ruta_grr')) {
            $insertData['ruta_grr'] = $rutaGrr;
        }
        if ($parteDiarioColumn) {
            $insertData[$parteDiarioColumn] = $rutaParteDiario;
        }

        $rowId = DB::transaction(function () use ($cotizacion, $insertData) {
            DB::table('cotizacion')->where('id_cotizacion', $cotizacion->id_cotizacion)->lockForUpdate()->first();
            $rowId = DB::table('agregado_cotizacion')->insertGetId($insertData);
            $this->recalcularTotales($cotizacion->id_cotizacion);
            return $rowId;
        });

        $row = DB::table('agregado_cotizacion as ac')
            ->join('chofer as ch', 'ch.id_chofer', '=', 'ac.id_chofer')
            ->join('agregado as a', 'a.id_agregado', '=', 'ac.id_agregado')
            ->where('ac.id_cotizacion_agr', $rowId)
            ->select('ac.*', DB::raw("TRIM(CONCAT(ch.nombres,' ',COALESCE(ch.apellido_paterno,''),' ',COALESCE(ch.apellido_materno,''))) as chofer_nombre"), DB::raw("CONCAT(a.nombre, CASE WHEN a.numero_agregado IS NOT NULL AND a.numero_agregado != '' THEN CONCAT(' (', a.numero_agregado, ')') ELSE '' END) as agregado_nombre"))
            ->first();

        if ($row && $parteDiarioColumn === 'ruta_n_parte_diario' && isset($row->ruta_n_parte_diario)) {
            $row->ruta_parte_diario = $row->ruta_n_parte_diario;
        }

        // Agregar URLs públicas de S3 al objeto devuelto
        if ($row) {
            if (isset($row->ruta_parte_diario) && $row->ruta_parte_diario !== '') {
                $row->url_parte_diario = $this->resolveS3FileUrl($row->ruta_parte_diario);
            }
            if (isset($row->ruta_grr) && $row->ruta_grr !== '') {
                $row->url_grr = $this->resolveS3FileUrl($row->ruta_grr);
            }
        }

        $totales = $this->getTotales($cotizacion->id_cotizacion);

        return response()->json(['success' => true, 'row' => $row, 'totales' => $totales]);
    }

    public function updateRow(Request $request, int $idCotizacion, int $rowId)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();
        if (!$cotizacion || !$cotizacion->activo) return response()->json(['error' => 'No encontrado'], 404);

        $table = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $pk = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'id_cotizacion_maqu' : 'id_cotizacion_agr';
        $currentRow = DB::table($table)->where($pk, $rowId)->where('id_cotizacion', $idCotizacion)->where('activo', 1)->first();
        abort_unless($currentRow, 404);

        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            $parteDiarioColumn = $this->getParteDiarioColumn('maquinaria_cotizacion');

            $v = $request->validate([
                'id_chofer'      => 'required|integer',
                'id_maquinaria'  => 'required|integer',
                'fecha'          => 'required|date',
                'placa'          => 'nullable|string|max:20',
                'obra_maquina'   => 'nullable|string|max:250',
                'hora_inicio'    => 'required|numeric',
                'hora_fin'       => 'required|numeric|gt:hora_inicio',
                'hora_minima'    => 'required|numeric',
                'precio_hora'    => 'required|numeric',
                'cobrar_fila'    => 'nullable|in:0,1',
                'completar_salto' => 'nullable|boolean',
                'nueva_oc_numero' => ['nullable', 'required_with:nueva_oc_horas,nueva_oc_archivo', 'string', 'max:100', \Illuminate\Validation\Rule::unique('cotizacion_orden_compra', 'numero')->where('id_cotizacion', $idCotizacion)],
                'nueva_oc_horas' => 'nullable|required_with:nueva_oc_numero,nueva_oc_archivo|numeric|gt:0',
                'nueva_oc_archivo' => 'nullable|required_with:nueva_oc_numero,nueva_oc_horas|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
                'n_parte_diario' => 'nullable|string|max:50',
                'numero_factura' => 'nullable|string|max:50',
                'imagen_parte_diario' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            ]);

            $horasTrabajadas = round($v['hora_fin'] - $v['hora_inicio'], 2);
            $horasEfectivas  = max($horasTrabajadas, (float) $v['hora_minima']);
            $debeCobrar      = (($v['cobrar_fila'] ?? '1') === '1') && !$currentRow->es_ajuste_horometro;
            $totalFila       = $debeCobrar ? round($horasEfectivas * $v['precio_hora'], 2) : 0;
            $rutaNuevaOc = $this->uploadToS3($request, 'nueva_oc_archivo', "cotizaciones/ordenes/{$idCotizacion}");

            $updateData = array_merge($v, [
                'horas_trabajadas'    => $horasTrabajadas,
                'total_fila'          => $totalFila,
                'es_facturable'       => $debeCobrar,
                'fecha_actualizacion' => now(),
            ]);
            if (!$debeCobrar) $updateData['id_hes'] = null;

            // Subir nueva imagen parte diario a S3 si viene en la request
            if ($request->hasFile('imagen_parte_diario')) {
                if ($parteDiarioColumn) {
                    $updateData[$parteDiarioColumn] = $this->uploadToS3(
                        $request,
                        'imagen_parte_diario',
                        "cotizaciones/partes/{$idCotizacion}"
                    );
                }
            }

            // Quitar la clave del archivo del array de actualización (no es columna)
            unset($updateData['imagen_parte_diario'], $updateData['cobrar_fila'], $updateData['completar_salto'], $updateData['nueva_oc_numero'], $updateData['nueva_oc_horas'], $updateData['nueva_oc_archivo']);

            try {
                DB::transaction(function () use ($idCotizacion, $rowId, $updateData, $cotizacion, $v, $rutaNuevaOc) {
                    DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->lockForUpdate()->first();
                    abort_unless(DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $rowId)
                        ->where('id_cotizacion', $idCotizacion)->where('activo', 1)->lockForUpdate()->first(), 404);
                    if (!empty($v['nueva_oc_numero'])) {
                        if (!$cotizacion->control_oc_activo || !$rutaNuevaOc) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['nueva_oc_archivo' => 'La OC adicional requiere un documento.']);
                        }
                        DB::table('cotizacion_orden_compra')->insert([
                            'id_cotizacion' => $idCotizacion, 'numero' => $v['nueva_oc_numero'],
                            'horas_autorizadas' => $v['nueva_oc_horas'], 'ruta_documento' => $rutaNuevaOc,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    if (!empty($v['completar_salto'])) {
                        $anterior = DB::table('maquinaria_cotizacion')
                            ->where('id_cotizacion', $idCotizacion)->where('id_maquinaria', $v['id_maquinaria'])
                            ->where('id_cotizacion_maqu', '<>', $rowId)->where('activo', 1)
                            ->where(function ($q) use ($v) {
                                $q->where('fecha', '<', $v['fecha'])
                                    ->orWhere(function ($same) use ($v) {
                                        $same->where('fecha', $v['fecha'])->where('hora_inicio', '<', $v['hora_inicio']);
                                    });
                            })
                            ->orderByDesc('fecha')->orderByDesc('hora_fin')->first();
                        if ($anterior && (float)$v['hora_inicio'] > (float)$anterior->hora_fin + 0.05) {
                            DB::table('maquinaria_cotizacion')->insert([
                                'id_cotizacion' => $idCotizacion, 'id_chofer' => $v['id_chofer'],
                                'id_maquinaria' => $v['id_maquinaria'], 'fecha' => $v['fecha'],
                                'placa' => $v['placa'] ?? null, 'obra_maquina' => $v['obra_maquina'] ?? $cotizacion->obra,
                                'hora_inicio' => $anterior->hora_fin, 'hora_fin' => $v['hora_inicio'],
                                'hora_minima' => 0, 'horas_trabajadas' => round((float)$v['hora_inicio'] - (float)$anterior->hora_fin, 2),
                                'precio_hora' => 0, 'total_fila' => 0, 'es_facturable' => false,
                                'es_ajuste_horometro' => true, 'activo' => 1, 'fecha_creacion' => now(),
                            ]);
                        }
                    }
                    DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $rowId)
                        ->where('id_cotizacion', $idCotizacion)->where('activo', 1)->update($updateData);
                    if ($cotizacion->control_oc_activo) app(ValorizacionOcService::class)->recalcular($idCotizacion);
                    $this->recalcularTotales($idCotizacion);
                });
            } catch (\Throwable $e) {
                if ($rutaNuevaOc) Storage::disk('s3')->delete($rutaNuevaOc);
                throw $e;
            }

        } else {
            $parteDiarioColumn = $this->getParteDiarioColumn('agregado_cotizacion');

            $v = $request->validate([
                'id_chofer'      => 'required|integer',
                'id_agregado'    => 'required|integer',
                'fecha'          => 'required|date',
                'placa'          => 'nullable|string|max:20',
                'obra_agregado'  => 'nullable|string|max:250',
                'm3'             => 'required|numeric',
                'precio_m3'      => 'required|numeric',
                'cobrar_fila'    => 'nullable|in:0,1',
                'n_parte_diario' => 'nullable|string|max:50',
                'numero_factura' => 'nullable|string|max:50',
                'grr'            => 'nullable|string|max:50',
                'imagen_parte_diario' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
                'archivo_grr'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            ]);

            $debeCobrar = (($v['cobrar_fila'] ?? '1') === '1');
            $totalFila  = $debeCobrar ? round($v['m3'] * $v['precio_m3'], 2) : 0;
            $updateData = array_merge($v, [
                'total_fila'          => $totalFila,
                'es_facturable'       => $debeCobrar,
                'fecha_actualizacion' => now(),
            ]);
            if (!$debeCobrar) $updateData['id_hes'] = null;

            if ($request->hasFile('archivo_grr')) {
                if ($this->tableHasColumn('agregado_cotizacion', 'ruta_grr')) {
                    $updateData['ruta_grr'] = $this->uploadToS3(
                        $request,
                        'archivo_grr',
                        "cotizaciones/grr/{$idCotizacion}"
                    );
                }
            }
            if ($request->hasFile('imagen_parte_diario')) {
                if ($parteDiarioColumn) {
                    $updateData[$parteDiarioColumn] = $this->uploadToS3(
                        $request,
                        'imagen_parte_diario',
                        "cotizaciones/partes/{$idCotizacion}"
                    );
                }
            }

            // Quitar claves de archivos del array
            unset($updateData['imagen_parte_diario'], $updateData['archivo_grr'], $updateData['cobrar_fila']);

            DB::transaction(function () use ($idCotizacion, $rowId, $updateData) {
                DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->lockForUpdate()->first();
                $updated = DB::table('agregado_cotizacion')->where('id_cotizacion_agr', $rowId)
                    ->where('id_cotizacion', $idCotizacion)->where('activo', 1)->update($updateData);
                abort_unless($updated, 404);
                $this->recalcularTotales($idCotizacion);
            });
        }

        $totales = $this->getTotales($idCotizacion);

        return response()->json(['success' => true, 'totales' => $totales]);
    }

    public function destroyRow(int $idCotizacion, int $rowId)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();
        if (!$cotizacion || !$cotizacion->activo) return response()->json(['error' => 'No encontrado'], 404);

        $table  = $cotizacion->tipo_cotizacion === 'MAQUINARIA'
            ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $pkCol  = $cotizacion->tipo_cotizacion === 'MAQUINARIA'
            ? 'id_cotizacion_maqu' : 'id_cotizacion_agr';

        DB::transaction(function () use ($idCotizacion, $rowId, $table, $pkCol, $cotizacion) {
            DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->lockForUpdate()->first();
            $changed = DB::table($table)->where($pkCol, $rowId)->where('id_cotizacion', $idCotizacion)
                ->where('activo', 1)->update(['activo' => 0, 'id_hes' => null, 'fecha_actualizacion' => now()]);
            abort_unless($changed, 404);
            if ($cotizacion->control_oc_activo) app(ValorizacionOcService::class)->recalcular($idCotizacion);
            $this->recalcularTotales($idCotizacion);
        });
        $totales = $this->getTotales($idCotizacion);

        return response()->json(['success' => true, 'totales' => $totales]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function getFilas(object $cotizacion): \Illuminate\Support\Collection
    {
        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            $parteDiarioColumn = $this->getParteDiarioColumn('maquinaria_cotizacion');

            $query = DB::table('maquinaria_cotizacion as mc')
                ->leftJoin('cotizacion_hes as hes', 'hes.id_hes', '=', 'mc.id_hes')
                ->join('chofer as ch', 'ch.id_chofer', '=', 'mc.id_chofer')
                ->join('maquinaria as m', 'm.id_maquinaria', '=', 'mc.id_maquinaria')
                ->where('mc.id_cotizacion', $cotizacion->id_cotizacion)
                ->where('mc.activo', 1)
                ->select(
                    'mc.*', 'hes.codigo as codigo_hes',
                    DB::raw("TRIM(CONCAT(ch.nombres,' ',COALESCE(ch.apellido_paterno,''),' ',COALESCE(ch.apellido_materno,''))) as chofer_nombre"),
                    DB::raw("CONCAT(m.nombre, CASE WHEN m.numero_maquina IS NOT NULL AND m.numero_maquina != '' THEN CONCAT(' — ', m.numero_maquina) ELSE '' END) as maquinaria_nombre"),
                    DB::raw("'MAQUINARIA' as _tipo"),
                    DB::raw('mc.id_cotizacion_maqu as _row_id')
                )
                ->orderBy('mc.fecha')
                ->orderBy('mc.hora_inicio');

            if ($parteDiarioColumn === 'ruta_n_parte_diario') {
                $query->addSelect(DB::raw('mc.ruta_n_parte_diario as ruta_parte_diario'));
            }

            $filas = $query->get();

            // Adjuntar URLs públicas de S3
            $filas = $filas->map(function ($fila) {
                if (isset($fila->ruta_parte_diario) && $fila->ruta_parte_diario !== '') {
                    $fila->url_parte_diario = $this->resolveS3FileUrl($fila->ruta_parte_diario);
                }
                return $fila;
            });
            return app(ValorizacionOcService::class)->detallarFilas($filas, (bool) $cotizacion->control_oc_activo);
        } else {
            $parteDiarioColumn = $this->getParteDiarioColumn('agregado_cotizacion');

            $query = DB::table('agregado_cotizacion as ac')
                ->leftJoin('cotizacion_hes as hes', 'hes.id_hes', '=', 'ac.id_hes')
                ->join('chofer as ch', 'ch.id_chofer', '=', 'ac.id_chofer')
                ->join('agregado as a', 'a.id_agregado', '=', 'ac.id_agregado')
                ->where('ac.id_cotizacion', $cotizacion->id_cotizacion)
                ->where('ac.activo', 1)
                ->select(
                    'ac.*', 'hes.codigo as codigo_hes',
                    DB::raw("TRIM(CONCAT(ch.nombres,' ',COALESCE(ch.apellido_paterno,''),' ',COALESCE(ch.apellido_materno,''))) as chofer_nombre"),
                    DB::raw("CONCAT(a.nombre, CASE WHEN a.numero_agregado IS NOT NULL AND a.numero_agregado != '' THEN CONCAT(' (', a.numero_agregado, ')') ELSE '' END) as agregado_nombre"),
                    DB::raw("'AGREGADO' as _tipo"),
                    DB::raw('ac.id_cotizacion_agr as _row_id')
                )
                ->orderBy('ac.fecha');

            if ($parteDiarioColumn === 'ruta_n_parte_diario') {
                $query->addSelect(DB::raw('ac.ruta_n_parte_diario as ruta_parte_diario'));
            }

            $filas = $query->get();

            // Adjuntar URLs públicas de S3
            return $filas->map(function ($fila) {
                if (isset($fila->ruta_parte_diario) && $fila->ruta_parte_diario !== '') {
                    $fila->url_parte_diario = $this->resolveS3FileUrl($fila->ruta_parte_diario);
                }
                if (isset($fila->ruta_grr) && $fila->ruta_grr !== '') {
                    $fila->url_grr = $this->resolveS3FileUrl($fila->ruta_grr);
                }
                return $fila;
            });
        }
    }

    private function recalcularTotales(int $idCotizacion): void
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();

        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            $total = DB::table('maquinaria_cotizacion')
                ->where('id_cotizacion', $idCotizacion)
                ->where('activo', 1)
                ->sum('total_fila');
        } else {
            $total = DB::table('agregado_cotizacion')
                ->where('id_cotizacion', $idCotizacion)
                ->where('activo', 1)
                ->sum('total_fila');
        }

        $total       = round((float) $total, 2);
        $baseSinIgv  = round($total / 1.18, 2);
        $totalIgv    = round($total - $baseSinIgv, 2);

        DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->update([
            'total'               => $total,
            'base_sin_igv'        => $baseSinIgv,
            'total_igv'           => $totalIgv,
            'fecha_actualizacion' => now(),
        ]);
    }

    private function getTotales(int $idCotizacion): array
    {
        $c = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();
        return [
            'total'        => $c->total,
            'base_sin_igv' => $c->base_sin_igv,
            'total_igv'    => $c->total_igv,
        ];
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '::' . $column;
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasColumn($table, $column);
        }
        return $cache[$key];
    }

    private function getParteDiarioColumn(string $table): ?string
    {
        if ($this->tableHasColumn($table, 'ruta_parte_diario')) {
            return 'ruta_parte_diario';
        }

        if ($this->tableHasColumn($table, 'ruta_n_parte_diario')) {
            return 'ruta_n_parte_diario';
        }

        return null;
    }
}
