<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\View\View;

class FacturaController extends Controller
{
    public function index(): View
    {
        $facturas = Factura::with(['cliente', 'notificaciones' => function ($query) {
            $query->latest('id_notificacion');
        }])->get();

        return view('facturas.index', compact('facturas'));
    }
}
