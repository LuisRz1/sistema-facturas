<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;

class FacturaController extends Controller
{
    public function index(): View
    {
        $facturas = Factura::with([
            'cliente',
            'notificaciones' => fn ($q) => $q->latest('id_notificacion'),
        ])->orderByDesc('fecha_emision')->get();

        return view('facturas.index', compact('facturas'));
    }
}
