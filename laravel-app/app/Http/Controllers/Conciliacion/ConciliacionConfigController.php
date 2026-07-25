<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConciliacionConfigController extends Controller
{
    public function index()
    {
        // Obtener todas las configuraciones agrupadas por banco
        $configs = DB::table('banco_parser_config')
            ->orderBy('banco')
            ->orderByDesc('activo')
            ->orderByDesc('version')
            ->get()
            ->groupBy('banco');

        // Lista de bancos con configs
        $bancos = DB::table('banco_parser_config')
            ->select('banco')
            ->distinct()
            ->orderBy('banco')
            ->pluck('banco');

        return view('conciliacion.configuracion', compact('configs', 'bancos'));
    }

    public function guardarParser(Request $request)
    {
        $validated = $request->validate([
            'banco'              => 'required|string|max:100',
            'version'            => 'required|string|max:50',
            'mapeo_columnas'     => 'required|json',
            'tipos_ignorables'   => 'nullable|json',
            'tolerancia_monto'   => 'required|numeric|min:0',
            'tolerancia_dias'    => 'required|integer|min:0',
            'activo'             => 'nullable|boolean',
        ]);

        $activo = $request->boolean('activo', false);

        // Si se marca como activo, desactivar todas las otras configs del mismo banco
        if ($activo) {
            DB::table('banco_parser_config')
                ->where('banco', $validated['banco'])
                ->update(['activo' => false]);
        }

        // Buscar si ya existe una config con mismo banco y version
        $existente = DB::table('banco_parser_config')
            ->where('banco', $validated['banco'])
            ->where('version', $validated['version'])
            ->first();

        if ($existente) {
            DB::table('banco_parser_config')
                ->where('id_config', $existente->id_config)
                ->update([
                    'mapeo_columnas'   => $validated['mapeo_columnas'],
                    'tipos_ignorables' => $validated['tipos_ignorables'] ?? '[]',
                    'tolerancia_monto' => $validated['tolerancia_monto'],
                    'tolerancia_dias'  => $validated['tolerancia_dias'],
                    'activo'           => $activo,
                ]);

            return redirect()->route('conciliacion.configuracion')
                ->with('success', 'Configuración actualizada correctamente para ' . $validated['banco']);
        }

        DB::table('banco_parser_config')->insert([
            'banco'            => $validated['banco'],
            'version'          => $validated['version'],
            'mapeo_columnas'   => $validated['mapeo_columnas'],
            'tipos_ignorables' => $validated['tipos_ignorables'] ?? '[]',
            'tolerancia_monto' => $validated['tolerancia_monto'],
            'tolerancia_dias'  => $validated['tolerancia_dias'],
            'activo'           => $activo,
        ]);

        return redirect()->route('conciliacion.configuracion')
            ->with('success', 'Configuración creada correctamente para ' . $validated['banco']);
    }
}
