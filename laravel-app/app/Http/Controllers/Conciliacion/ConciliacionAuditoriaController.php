<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConciliacionAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $usuarioId = $request->input('usuario_id');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $accion = $request->input('accion');

        $query = DB::table('movimiento_historial_estado as mh')
            ->join('usuario as u', 'u.id_usuario', '=', 'mh.usuario_id')
            ->join('movimiento_bancario as mb', 'mb.id_movimiento', '=', 'mh.id_movimiento');

        if ($usuarioId) {
            $query->where('mh.usuario_id', $usuarioId);
        }

        if ($fechaDesde) {
            $query->whereDate('mh.fecha_transicion', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('mh.fecha_transicion', '<=', $fechaHasta);
        }

        if ($accion) {
            $query->where('mh.estado_nuevo', $accion);
        }

        $registros = $query->select(
            'mh.id_historial',
            'mh.id_movimiento',
            'mh.estado_anterior',
            'mh.estado_nuevo',
            'mh.motivo',
            'mh.fecha_transicion',
            'u.nombre as usuario_nombre',
            'u.apellido as usuario_apellido',
            'mb.banco',
            'mb.descripcion',
            'mb.importe'
        )
            ->orderByDesc('mh.fecha_transicion')
            ->paginate(20)
            ->appends($request->except('page'));

        // Lista de usuarios para filtro
        $usuarios = DB::table('usuario')
            ->select('id_usuario', 'nombre', 'apellido')
            ->orderBy('nombre')
            ->get();

        // Estados (acciones) únicos para filtro
        $acciones = DB::table('movimiento_historial_estado')
            ->select('estado_nuevo as accion')
            ->distinct()
            ->orderBy('estado_nuevo')
            ->pluck('estado_nuevo');

        return view('conciliacion.auditoria', compact(
            'registros', 'usuarios', 'acciones',
            'usuarioId', 'fechaDesde', 'fechaHasta', 'accion'
        ));
    }
}
