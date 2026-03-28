<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class CotizacionController extends Controller
{
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
                'c.id_cotizacion', 'c.tipo_cotizacion', 'c.numero_valorizacion',
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
                ->orWhere('cl.razon_social', 'like', "%{$search}%");
        });

        $cotizaciones = $query->get();

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
            'obra'              => 'required|string|max:250',
            'periodo_inicio'    => 'required|date',
            'periodo_fin'       => 'required|date|after_or_equal:periodo_inicio',
        ]);

        $id = DB::table('cotizacion')->insertGetId([
            'id_cliente'          => $validated['id_cliente'],
            'id_maquinaria'       => $validated['tipo_cotizacion'] === 'MAQUINARIA' ? ($validated['id_maquinaria'] ?? null) : null,
            'id_agregado'         => $validated['tipo_cotizacion'] === 'AGREGADO'   ? ($validated['id_agregado'] ?? null) : null,
            'tipo_cotizacion'     => $validated['tipo_cotizacion'],
            'numero_valorizacion' => $validated['numero_valorizacion'],
            'obra'                => $validated['obra'],
            'periodo_inicio'      => $validated['periodo_inicio'],
            'periodo_fin'         => $validated['periodo_fin'],
            'base_sin_igv'        => 0,
            'total_igv'           => 0,
            'total'               => 0,
            'activo'              => 1,
            'fecha_creacion'      => now(),
        ]);

        return redirect()->route('cotizaciones.show', $id)
            ->with('success', 'Cotización creada. Ahora agrega las filas.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHOW (management view – also used as edit)
    // ──────────────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $cotizacion = DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('maquinaria as m', 'm.id_maquinaria', '=', 'c.id_maquinaria')
            ->leftJoin('agregado as a', 'a.id_agregado', '=', 'c.id_agregado')
            ->where('c.id_cotizacion', $id)->where('c.activo', 1)
            ->select('c.*', 'cl.razon_social', 'cl.ruc',
                'm.nombre as maquinaria_nombre',
                'a.nombre as agregado_nombre')
            ->first();

        if (!$cotizacion) abort(404);

        $filas = $this->getFilas($cotizacion);

        $clientes    = DB::table('cliente')->where('activo', 1)->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc']);
        $maquinarias = DB::table('maquinaria')->where('activo', 1)->orderBy('nombre')->get();
        $agregados   = DB::table('agregado')->where('activo', 1)->orderBy('nombre')->get();
        $choferes    = DB::table('chofer')->where('activo', 1)->orderBy('nombres')->get();

        return view('cotizaciones.show', compact(
            'cotizacion', 'filas', 'clientes', 'maquinarias', 'agregados', 'choferes'
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
            'obra'                => 'required|string|max:250',
            'periodo_inicio'      => 'required|date',
            'periodo_fin'         => 'required|date|after_or_equal:periodo_inicio',
        ]);

        DB::table('cotizacion')->where('id_cotizacion', $id)->update([
            'id_cliente'          => $validated['id_cliente'],
            'id_maquinaria'       => $validated['tipo_cotizacion'] === 'MAQUINARIA' ? ($validated['id_maquinaria'] ?? null) : null,
            'id_agregado'         => $validated['tipo_cotizacion'] === 'AGREGADO'   ? ($validated['id_agregado'] ?? null) : null,
            'tipo_cotizacion'     => $validated['tipo_cotizacion'],
            'numero_valorizacion' => $validated['numero_valorizacion'],
            'obra'                => $validated['obra'],
            'periodo_inicio'      => $validated['periodo_inicio'],
            'periodo_fin'         => $validated['periodo_fin'],
            'fecha_actualizacion' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Cotización actualizada.']);
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

        return view('cotizaciones.print', compact('cotizacion', 'filas'));
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
        if (!$cotizacion) return response()->json(['error' => 'Cotización no encontrada'], 404);

        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            return $this->storeMaquinariaRow($request, $cotizacion);
        } else {
            return $this->storeAgregadoRow($request, $cotizacion);
        }
    }

    private function storeMaquinariaRow(Request $request, object $cotizacion)
    {
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
            'n_parte_diario' => 'nullable|string|max:50',
        ]);

        $horasTrabajadas = round($v['hora_fin'] - $v['hora_inicio'], 2);
        $horasEfectivas  = max($horasTrabajadas, (float) $v['hora_minima']);
        $totalFila       = round($horasEfectivas * $v['precio_hora'], 2);

        // Handle parte diario image upload
        $rutaParteDiario = null;
        if ($request->hasFile('imagen_parte_diario')) {
            $rutaParteDiario = $request->file('imagen_parte_diario')
                ->store("cotizaciones/partes/{$cotizacion->id_cotizacion}", 'public');
        }

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
            'n_parte_diario'   => $v['n_parte_diario'] ?? null,
            'activo'           => 1,
            'fecha_creacion'   => now(),
        ];

        if ($this->tableHasColumn('maquinaria_cotizacion', 'ruta_parte_diario')) {
            $insertData['ruta_parte_diario'] = $rutaParteDiario;
        }

        $rowId = DB::table('maquinaria_cotizacion')->insertGetId($insertData);

        $this->recalcularTotales($cotizacion->id_cotizacion);

        $row = DB::table('maquinaria_cotizacion as mc')
            ->join('chofer as ch', 'ch.id_chofer', '=', 'mc.id_chofer')
            ->join('maquinaria as m', 'm.id_maquinaria', '=', 'mc.id_maquinaria')
            ->where('mc.id_cotizacion_maqu', $rowId)
            ->select('mc.*', 'ch.nombres as chofer_nombre', 'm.nombre as maquinaria_nombre')
            ->first();

        $totales = $this->getTotales($cotizacion->id_cotizacion);

        return response()->json(['success' => true, 'row' => $row, 'totales' => $totales]);
    }

    private function storeAgregadoRow(Request $request, object $cotizacion)
    {
        $v = $request->validate([
            'id_chofer'      => 'required|integer|exists:chofer,id_chofer',
            'id_agregado'    => 'required|integer|exists:agregado,id_agregado',
            'fecha'          => 'required|date',
            'placa'          => 'nullable|string|max:20',
            'obra_agregado'  => 'nullable|string|max:250',
            'm3'             => 'required|numeric|min:0',
            'precio_m3'      => 'required|numeric|min:0',
            'n_parte_diario' => 'nullable|string|max:50',
            'grr'            => 'nullable|string|max:50',
        ]);

        $totalFila = round($v['m3'] * $v['precio_m3'], 2);

        // Handle GRR PDF upload
        $rutaGrr = null;
        if ($request->hasFile('archivo_grr')) {
            $rutaGrr = $request->file('archivo_grr')
                ->store("cotizaciones/grr/{$cotizacion->id_cotizacion}", 'public');
        }

        // Handle parte diario image upload
        $rutaParteDiario = null;
        if ($request->hasFile('imagen_parte_diario')) {
            $rutaParteDiario = $request->file('imagen_parte_diario')
                ->store("cotizaciones/partes/{$cotizacion->id_cotizacion}", 'public');
        }

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
            'n_parte_diario'   => $v['n_parte_diario'] ?? null,
            'grr'              => $v['grr'] ?? null,
            'activo'           => 1,
            'fecha_creacion'   => now(),
        ];

        if ($this->tableHasColumn('agregado_cotizacion', 'ruta_grr')) {
            $insertData['ruta_grr'] = $rutaGrr;
        }
        if ($this->tableHasColumn('agregado_cotizacion', 'ruta_parte_diario')) {
            $insertData['ruta_parte_diario'] = $rutaParteDiario;
        }

        $rowId = DB::table('agregado_cotizacion')->insertGetId($insertData);

        $this->recalcularTotales($cotizacion->id_cotizacion);

        $row = DB::table('agregado_cotizacion as ac')
            ->join('chofer as ch', 'ch.id_chofer', '=', 'ac.id_chofer')
            ->join('agregado as a', 'a.id_agregado', '=', 'ac.id_agregado')
            ->where('ac.id_cotizacion_agr', $rowId)
            ->select('ac.*', 'ch.nombres as chofer_nombre', 'a.nombre as agregado_nombre')
            ->first();

        $totales = $this->getTotales($cotizacion->id_cotizacion);

        return response()->json(['success' => true, 'row' => $row, 'totales' => $totales]);
    }

    public function updateRow(Request $request, int $idCotizacion, int $rowId)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();
        if (!$cotizacion) return response()->json(['error' => 'No encontrado'], 404);

        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
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
                'n_parte_diario' => 'nullable|string|max:50',
            ]);

            $horasTrabajadas = round($v['hora_fin'] - $v['hora_inicio'], 2);
            $horasEfectivas  = max($horasTrabajadas, (float) $v['hora_minima']);
            $totalFila       = round($horasEfectivas * $v['precio_hora'], 2);

            $updateData = array_merge($v, [
                'horas_trabajadas'   => $horasTrabajadas,
                'total_fila'         => $totalFila,
                'fecha_actualizacion'=> now(),
            ]);

            if ($request->hasFile('imagen_parte_diario')) {
                if ($this->tableHasColumn('maquinaria_cotizacion', 'ruta_parte_diario')) {
                    $updateData['ruta_parte_diario'] = $request->file('imagen_parte_diario')
                        ->store("cotizaciones/partes/{$idCotizacion}", 'public');
                }
            }

            DB::table('maquinaria_cotizacion')
                ->where('id_cotizacion_maqu', $rowId)
                ->update($updateData);

        } else {
            $v = $request->validate([
                'id_chofer'      => 'required|integer',
                'id_agregado'    => 'required|integer',
                'fecha'          => 'required|date',
                'placa'          => 'nullable|string|max:20',
                'obra_agregado'  => 'nullable|string|max:250',
                'm3'             => 'required|numeric',
                'precio_m3'      => 'required|numeric',
                'n_parte_diario' => 'nullable|string|max:50',
                'grr'            => 'nullable|string|max:50',
            ]);

            $totalFila  = round($v['m3'] * $v['precio_m3'], 2);
            $updateData = array_merge($v, [
                'total_fila'         => $totalFila,
                'fecha_actualizacion'=> now(),
            ]);

            if ($request->hasFile('archivo_grr')) {
                if ($this->tableHasColumn('agregado_cotizacion', 'ruta_grr')) {
                    $updateData['ruta_grr'] = $request->file('archivo_grr')
                        ->store("cotizaciones/grr/{$idCotizacion}", 'public');
                }
            }
            if ($request->hasFile('imagen_parte_diario')) {
                if ($this->tableHasColumn('agregado_cotizacion', 'ruta_parte_diario')) {
                    $updateData['ruta_parte_diario'] = $request->file('imagen_parte_diario')
                        ->store("cotizaciones/partes/{$idCotizacion}", 'public');
                }
            }

            DB::table('agregado_cotizacion')
                ->where('id_cotizacion_agr', $rowId)
                ->update($updateData);
        }

        $this->recalcularTotales($idCotizacion);
        $totales = $this->getTotales($idCotizacion);

        return response()->json(['success' => true, 'totales' => $totales]);
    }

    public function destroyRow(int $idCotizacion, int $rowId)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $idCotizacion)->first();
        if (!$cotizacion) return response()->json(['error' => 'No encontrado'], 404);

        $table  = $cotizacion->tipo_cotizacion === 'MAQUINARIA'
            ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $pkCol  = $cotizacion->tipo_cotizacion === 'MAQUINARIA'
            ? 'id_cotizacion_maqu' : 'id_cotizacion_agr';

        DB::table($table)->where($pkCol, $rowId)
            ->update(['activo' => 0, 'fecha_actualizacion' => now()]);

        $this->recalcularTotales($idCotizacion);
        $totales = $this->getTotales($idCotizacion);

        return response()->json(['success' => true, 'totales' => $totales]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function getFilas(object $cotizacion): \Illuminate\Support\Collection
    {
        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            return DB::table('maquinaria_cotizacion as mc')
                ->join('chofer as ch', 'ch.id_chofer', '=', 'mc.id_chofer')
                ->join('maquinaria as m', 'm.id_maquinaria', '=', 'mc.id_maquinaria')
                ->where('mc.id_cotizacion', $cotizacion->id_cotizacion)
                ->where('mc.activo', 1)
                ->select(
                    'mc.*',
                    'ch.nombres as chofer_nombre',
                    'm.nombre as maquinaria_nombre',
                    DB::raw("'MAQUINARIA' as _tipo"),
                    DB::raw('mc.id_cotizacion_maqu as _row_id')
                )
                ->orderBy('mc.fecha')
                ->orderBy('mc.hora_inicio')
                ->get();
        } else {
            return DB::table('agregado_cotizacion as ac')
                ->join('chofer as ch', 'ch.id_chofer', '=', 'ac.id_chofer')
                ->join('agregado as a', 'a.id_agregado', '=', 'ac.id_agregado')
                ->where('ac.id_cotizacion', $cotizacion->id_cotizacion)
                ->where('ac.activo', 1)
                ->select(
                    'ac.*',
                    'ch.nombres as chofer_nombre',
                    'a.nombre as agregado_nombre',
                    DB::raw("'AGREGADO' as _tipo"),
                    DB::raw('ac.id_cotizacion_agr as _row_id')
                )
                ->orderBy('ac.fecha')
                ->get();
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
}
