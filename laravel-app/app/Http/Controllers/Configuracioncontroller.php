<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConfiguracionController extends Controller
{
    private string $gatewayUrl;

    public function __construct()
    {
        $this->gatewayUrl = env('WHATSAPP_GATEWAY_URL', 'http://localhost:3001');
    }

    public function index()
    {
        return view('configuracion.index');
    }

    /**
     * Obtiene el estado actual del worker de WhatsApp.
     * Retorna JSON con { ok, listo, qr? }
     */
    public function whatsappStatus()
    {
        try {
            $response = Http::timeout(5)->get("{$this->gatewayUrl}/status");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['ok' => false, 'listo' => false, 'error' => 'No responde']);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'listo' => false,
                'error' => 'Worker no disponible: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Solicita el QR actual al worker.
     * El worker debe exponer GET /qr que retorna { qr: "string_base64_o_data_url" }
     */
    public function whatsappQr()
    {
        try {
            $response = Http::timeout(10)->get("{$this->gatewayUrl}/qr");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['ok' => false, 'error' => 'No se pudo obtener el QR']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Desconecta la sesión de WhatsApp (logout).
     */
    public function whatsappLogout()
    {
        try {
            $response = Http::timeout(10)->post("{$this->gatewayUrl}/logout");
            return response()->json(['ok' => true, 'message' => 'Sesión cerrada.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
