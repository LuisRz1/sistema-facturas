<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConciliacionHistorialController extends Controller
{
    public function index(Request $request)
    {
        $banco = $request->input('banco');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = DB::table('archivo_importado')
            ->where('activo', 1);

        if ($banco) {
            $query->where('banco', $banco);
        }

        if ($fechaDesde) {
            $query->whereDate('fecha_importacion', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('fecha_importacion', '<=', $fechaHasta);
        }

        $archivos = $query->orderByDesc('fecha_importacion')
            ->paginate(20)
            ->appends($request->except('page'));

        // Lista de bancos para el filtro
        $bancos = DB::table('archivo_importado')
            ->select('banco')
            ->distinct()
            ->orderBy('banco')
            ->pluck('banco');

        return view('conciliacion.historial', compact('archivos', 'bancos', 'banco', 'fechaDesde', 'fechaHasta'));
    }

    public function detalle($id, Request $request)
    {
        $archivo = DB::table('archivo_importado')
            ->where('id_archivo', $id)
            ->where('activo', 1)
            ->first();

        if (!$archivo) {
            abort(404, 'Archivo no encontrado.');
        }

        $estadoFiltro = $request->input('estado');

        $query = DB::table('movimiento_bancario as mb')
            ->leftJoin('cliente as c', 'c.id_cliente', '=', 'mb.id_cliente')
            ->leftJoin('factura as f', 'f.id_factura', '=', 'mb.id_factura')
            ->where('mb.id_archivo', $id)
            ->where('mb.activo', 1);

        if ($estadoFiltro) {
            $query->where('mb.estado_conciliacion', $estadoFiltro);
        }

        $movimientos = $query->select(
            'mb.*',
            'c.razon_social as cliente_nombre',
            DB::raw("CONCAT(COALESCE(f.serie,''), '-', COALESCE(f.numero,'')) as factura_numero")
        )
            ->orderBy('mb.fecha_operacion')
            ->orderBy('mb.hora')
            ->paginate(20)
            ->appends($request->except('page'));

        // Contadores por estado
        $contadores = DB::table('movimiento_bancario')
            ->where('id_archivo', $id)
            ->where('activo', 1)
            ->select('estado_conciliacion', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_conciliacion')
            ->pluck('total', 'estado_conciliacion');

        // Lista de estados para filtro
        $estados = [
            'CONCILIADO'            => 'Conciliado',
            'CONCILIADO_TOLERANCIA' => 'Conciliado (Tolerancia)',
            'CONCILIADO_MANUAL'     => 'Conciliado Manual',
            'SIN_MATCH'             => 'Sin Match',
            'IGNORADO'              => 'Ignorado',
            'EXTORNADO'             => 'Extornado',
            'DUPLICADO_OMITIDO'     => 'Duplicado Omitido',
        ];

        return view('conciliacion.detalle', compact(
            'archivo', 'movimientos', 'estadoFiltro', 'contadores', 'estados'
        ));
    }
}
