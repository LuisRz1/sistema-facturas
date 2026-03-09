<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(): View
    {
        $clientes = Cliente::orderBy('razon_social')->get();
        return view('clientes.index', compact('clientes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razon_social'    => 'required|string|max:200',
            'ruc'             => 'required|string|size:11|unique:cliente,ruc',
            'celular'         => 'nullable|string|max:15',
            'direccion_fiscal'=> 'nullable|string|max:250',
            'correo'          => 'nullable|email|max:150',
        ]);

        $data['fecha_creacion'] = now();
        $data['estado_contacto'] = $this->calcularEstadoContacto($data);

        Cliente::create($data);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $cliente = Cliente::findOrFail($id);

        $data = $request->validate([
            'razon_social'    => 'required|string|max:200',
            'ruc'             => 'required|string|size:11|unique:cliente,ruc,' . $id . ',id_cliente',
            'celular'         => 'nullable|string|max:15',
            'direccion_fiscal'=> 'nullable|string|max:250',
            'correo'          => 'nullable|email|max:150',
        ]);

        $data['fecha_actualizacion'] = now();
        $data['estado_contacto'] = $this->calcularEstadoContacto($data);

        $cliente->update($data);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    private function calcularEstadoContacto(array $data): string
    {
        $tieneCelular = !empty($data['celular']);
        $tieneCorreo  = !empty($data['correo']);
        $tieneDireccion = !empty($data['direccion_fiscal']);

        if ($tieneCelular && $tieneCorreo && $tieneDireccion) {
            return 'COMPLETO';
        }

        if ($tieneCelular || $tieneCorreo) {
            return 'INCOMPLETO';
        }

        return 'SIN_DATOS';
    }
}
