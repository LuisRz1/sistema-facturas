<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogosController extends Controller
{
    public function index()
    {
        $choferes    = DB::table('chofer')->where('activo', 1)->orderBy('nombres')->get();
        $maquinarias = DB::table('maquinaria')->where('activo', 1)->orderBy('nombre')->get();
        $agregados   = DB::table('agregado')->where('activo', 1)->orderBy('nombre')->get();

        return view('catalogos.index', compact('choferes', 'maquinarias', 'agregados'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHOFERES
    // ──────────────────────────────────────────────────────────────────────────

    public function storeChofer(Request $request)
    {
        $v = $request->validate([
            'nombres'           => 'required|string|max:100',
            'apellido_paterno'  => 'nullable|string|max:100',
            'apellido_materno'  => 'nullable|string|max:100',
        ]);

        DB::table('chofer')->insert(array_merge($v, [
            'activo'         => 1,
            'fecha_creacion' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'Chofer creado.']);
    }

    public function updateChofer(Request $request, int $id)
    {
        $v = $request->validate([
            'nombres'           => 'required|string|max:100',
            'apellido_paterno'  => 'nullable|string|max:100',
            'apellido_materno'  => 'nullable|string|max:100',
        ]);

        DB::table('chofer')->where('id_chofer', $id)->update(array_merge($v, [
            'fecha_actualizacion' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'Chofer actualizado.']);
    }

    public function destroyChofer(int $id)
    {
        $enUsoEnMaquinaria = DB::table('maquinaria_cotizacion')
            ->where('id_chofer', $id)
            ->where('activo', 1)
            ->exists();

        $enUsoEnAgregado = DB::table('agregado_cotizacion')
            ->where('id_chofer', $id)
            ->where('activo', 1)
            ->exists();

        if ($enUsoEnMaquinaria || $enUsoEnAgregado) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el chofer porque tiene valorizaciones ligadas.',
            ], 422);
        }

        DB::table('chofer')->where('id_chofer', $id)->update([
            'activo'              => 0,
            'fecha_actualizacion' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MAQUINARIAS
    // ──────────────────────────────────────────────────────────────────────────

    public function storeMaquinaria(Request $request)
    {
        $v = $request->validate([
            'nombre'         => 'required|string|max:150',
            'numero_maquina' => 'nullable|string|max:50',
        ]);

        DB::table('maquinaria')->insert(array_merge($v, [
            'activo'         => 1,
            'fecha_creacion' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'Maquinaria creada.']);
    }

    public function updateMaquinaria(Request $request, int $id)
    {
        $v = $request->validate([
            'nombre'         => 'required|string|max:150',
            'numero_maquina' => 'nullable|string|max:50',
        ]);

        DB::table('maquinaria')->where('id_maquinaria', $id)->update(array_merge($v, [
            'fecha_actualizacion' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'Maquinaria actualizada.']);
    }

    public function destroyMaquinaria(int $id)
    {
        $enUso = DB::table('cotizacion')
            ->where('id_maquinaria', $id)
            ->where('activo', 1)
            ->exists();

        if ($enUso) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la maquinaria porque tiene valorizaciones ligadas.',
            ], 422);
        }

        DB::table('maquinaria')->where('id_maquinaria', $id)->update([
            'activo'              => 0,
            'fecha_actualizacion' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AGREGADOS
    // ──────────────────────────────────────────────────────────────────────────

    public function storeAgregado(Request $request)
    {
        $v = $request->validate([
            'nombre'          => 'required|string|max:150',
            'numero_agregado' => 'nullable|string|max:50',
        ]);

        DB::table('agregado')->insert(array_merge($v, [
            'activo'         => 1,
            'fecha_creacion' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'Agregado creado.']);
    }

    public function updateAgregado(Request $request, int $id)
    {
        $v = $request->validate([
            'nombre'          => 'required|string|max:150',
            'numero_agregado' => 'nullable|string|max:50',
        ]);

        DB::table('agregado')->where('id_agregado', $id)->update(array_merge($v, [
            'fecha_actualizacion' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'Agregado actualizado.']);
    }

    public function destroyAgregado(int $id)
    {
        $enUso = DB::table('cotizacion')
            ->where('id_agregado', $id)
            ->where('activo', 1)
            ->exists();

        if ($enUso) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el agregado porque tiene valorizaciones ligadas.',
            ], 422);
        }

        DB::table('agregado')->where('id_agregado', $id)->update([
            'activo'              => 0,
            'fecha_actualizacion' => now(),
        ]);
        return response()->json(['success' => true]);
    }
}
