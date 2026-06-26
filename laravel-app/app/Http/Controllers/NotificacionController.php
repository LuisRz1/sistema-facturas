<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\NotificacionFactura;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;

class NotificacionController extends Controller
{
    // Estados que indican pago pendiente (unificados con nueva DB)
    private const ESTADOS_PENDIENTES = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE', 'POR VALIDAR DETRACCION'];

    // ─── COBRANZA: FACTURAS PENDIENTES ───────────────────────────────────────

    public function enviarWhatsAppManual(int $id, WhatsAppGatewayService $gateway): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!in_array($factura->estado, self::ESTADOS_PENDIENTES)) {
            return back()->with('error', 'Solo se puede enviar a facturas en estado pendiente de pago.');
        }

        if (!$factura->cliente) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'WHATSAPP', 'COBRANZA', 'DEUDA_INICIAL', '', null, 'Sin cliente asociado.', 'ERROR', 'Factura sin cliente'));
            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->celular) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'WHATSAPP', 'COBRANZA', 'DEUDA_INICIAL', '', null, 'Sin celular registrado.', 'ERROR', 'Cliente sin celular'));
            return back()->with('error', 'El cliente no tiene celular registrado.');
        }

        $contenido = $this->buildMensajeCobranza($factura);
        $mensaje   = $contenido['mensaje'];

        $resultado = $gateway->enviar($factura->cliente->celular, $mensaje);

        NotificacionFactura::create($this->baseNotif(
            $factura->id_factura, 'WHATSAPP', 'COBRANZA', 'DEUDA_INICIAL',
            $factura->cliente->celular, null, $mensaje,
            $resultado['ok'] ? 'ENVIADO' : 'ERROR',
            $resultado['ok'] ? 'Envío manual por botón' : 'Error al enviar WhatsApp',
            $resultado['ok'] ? now() : null,
            $resultado['ok'] ? json_encode($resultado['data'], JSON_UNESCAPED_UNICODE) : $resultado['error']
        ));

        return back()->with(
            $resultado['ok'] ? 'success' : 'error',
            $resultado['ok'] ? 'WhatsApp enviado correctamente.' : 'No se pudo enviar el WhatsApp.'
        );
    }

    public function enviarCorreoManual(int $id): RedirectResponse
    {
        Log::info("enviarCorreoManual: iniciando factura #{$id}");

        try {
            $factura = Factura::with('cliente')->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error("enviarCorreoManual: factura #{$id} no encontrada - " . $e->getMessage());
            return back()->with('error', 'Factura no encontrada.');
        }

        Log::info("enviarCorreoManual: factura #{$id} estado={$factura->estado}");

        if (!in_array($factura->estado, self::ESTADOS_PENDIENTES)) {
            return back()->with('error', 'Solo se puede enviar correo a facturas en estado pendiente de pago.');
        }

        if (!$factura->cliente) {
            Log::warning("enviarCorreoManual: factura #{$id} sin cliente");
            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        $correo = trim($factura->cliente->correo ?? '');
        if ($correo === '') {
            Log::warning("enviarCorreoManual: factura #{$id} cliente sin correo");
            try {
                NotificacionFactura::create($this->baseNotif(
                    $id, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL',
                    '', 'Factura pendiente', 'Sin correo registrado.', 'ERROR', 'Cliente sin correo'
                ));
            } catch (\Throwable $e) {
                Log::error('Error al registrar notif sin correo: ' . $e->getMessage());
            }
            return back()->with('error', 'El cliente no tiene correo registrado.');
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Log::warning("enviarCorreoManual: factura #{$id} correo inválido: {$correo}");
            return back()->with('error', 'El correo del cliente no tiene un formato válido.');
        }

        Log::info("enviarCorreoManual: factura #{$id} enviando a {$correo}");

        try {
            $contenido = $this->buildMensajeCobranza($factura);
            $asunto    = $contenido['asunto'];
            $mensaje   = $contenido['mensaje'];
        } catch (\Throwable $e) {
            Log::error("enviarCorreoManual: error buildMensaje factura #{$id} - " . $e->getMessage());
            return back()->with('error', 'Error al preparar el correo.');
        }

        try {
            // Usar Symfony Email directamente — compatible con Laravel 12
            $symfonyEmail = (new Email())
                ->subject($asunto)
                ->to($correo)
                ->text($mensaje);

            Mail::mailer()->send($symfonyEmail);

            Log::info("enviarCorreoManual: factura #{$id} enviado OK a {$correo}");

            try {
                NotificacionFactura::create($this->baseNotif(
                    $id, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL',
                    $correo, $asunto, $mensaje, 'ENVIADO',
                    'Envío manual por botón', now(), 'Correo enviado correctamente'
                ));
            } catch (\Throwable $e) {
                Log::error('Error al registrar notif éxito: ' . $e->getMessage());
            }

            return back()->with('success', 'Correo enviado correctamente.');
        } catch (\Throwable $e) {
            $errorMsg = mb_substr($e->getMessage(), 0, 1000);
            Log::error("enviarCorreoManual: ERROR factura #{$id} - " . $e->getMessage());

            try {
                NotificacionFactura::create($this->baseNotif(
                    $id, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL',
                    $correo, $asunto, $mensaje, 'ERROR',
                    'Error al enviar correo', null, $errorMsg
                ));
            } catch (\Throwable $ex) {
                Log::error('Error al registrar notif fallo: ' . $ex->getMessage());
            }

            return back()->with('error', 'No se pudo enviar el correo. ' . $e->getMessage());
        }
    }

    // ─── ENVÍO DE FACTURA PAGADA ──────────────────────────────────────────────

    public function enviarFacturaPagadaWhatsApp(int $id, WhatsAppGatewayService $gateway): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!$factura->cliente?->celular) {
            return back()->with('error', 'El cliente no tiene celular registrado.');
        }

        $fechaPago = $factura->fecha_abono
            ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y')
            : 'Registrada';

        $mensaje = "*Confirmación de Pago*\n\n"
            . "Estimado cliente, le informamos que su factura *{$factura->serie}-{$factura->numero}* "
            . "ha sido procesada correctamente.\n\n"
            . " *Detalle de pago:*\n"
            . "• Factura: {$factura->serie}-{$factura->numero}\n"
            . "• Fecha de pago: {$fechaPago}\n"
            . "• Monto: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n"
            . "• Estado: ✓ PAGADA\n\n"
            . "Gracias por su confianza en nuestros servicios.\n\n"
            . "Atentamente,\nSistema de Facturación";

        $mediaUrl = $this->resolveComprobanteUrl($factura->ruta_comprobante_pago ?: null);

        $isPdf = $mediaUrl && preg_match('/\.pdf(\?|$)/i', $mediaUrl);
        if ($isPdf) {
            $fileName = 'Comprobante_' . $factura->serie . '-' . str_pad((string) $factura->numero, 8, '0', STR_PAD_LEFT) . '.pdf';
            $resultado = $gateway->enviarDocumento($factura->cliente->celular, $mediaUrl, $fileName, $mensaje);
        } else {
            $resultado = $gateway->enviar($factura->cliente->celular, $mensaje, $mediaUrl);
        }

        $observacion = $resultado['ok']
            ? ($mediaUrl ? 'Enviado con imagen del comprobante' : 'Enviado sin imagen')
            : 'Error al enviar WhatsApp';

        NotificacionFactura::create($this->baseNotif(
            $factura->id_factura, 'WHATSAPP', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
            $factura->cliente->celular, null, $mensaje,
            $resultado['ok'] ? 'ENVIADO' : 'ERROR',
            $observacion,
            $resultado['ok'] ? now() : null,
            $resultado['ok'] ? json_encode($resultado['data'], JSON_UNESCAPED_UNICODE) : $resultado['error']
        ));

        return back()->with(
            $resultado['ok'] ? 'success' : 'error',
            $resultado['ok']
                ? ($mediaUrl ? 'Comprobante enviado vía WhatsApp con imagen.' : 'Mensaje enviado (sin imagen).')
                : 'No se pudo enviar el WhatsApp.'
        );
    }

    public function enviarFacturaPagadaCorreo(int $id): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        $correo = trim($factura->cliente?->correo ?? '');
        if ($correo === '') {
            return back()->with('error', 'El cliente no tiene correo registrado.');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'El correo del cliente no tiene un formato válido: ' . $correo);
        }

        $fechaPago = $factura->fecha_abono
            ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y')
            : 'Registrada';

        $asunto  = "Confirmación de Pago - Factura {$factura->serie}-{$factura->numero}";
        $mensaje = "Estimado cliente,\n\n"
            . "Le informamos que su factura {$factura->serie}-{$factura->numero} ha sido PAGADA correctamente.\n\n"
            . "Detalle de pago:\n"
            . "- Factura: {$factura->serie}-{$factura->numero}\n"
            . "- Fecha de pago: {$fechaPago}\n"
            . "- Monto pagado: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n"
            . "- Estado: ✓ PAGADA\n";

        if ($factura->ruta_comprobante_pago) {
            $mensaje .= "\n Ver comprobante: {$factura->ruta_comprobante_pago}\n";
        }

        $mensaje .= "\nGracias por su confianza.\n\nAtentamente,\nSistema de Facturación";

        try {
            $symfonyEmail = (new Email())
                ->subject($asunto)
                ->to($correo)
                ->text($mensaje);

            Mail::mailer()->send($symfonyEmail);

            Log::info("enviarFacturaPagadaCorreo: factura #{$id} enviado OK a {$correo}");

            NotificacionFactura::create($this->baseNotif(
                $factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
                $correo, $asunto, $mensaje, 'ENVIADO',
                'Confirmación de pago', now()
            ));

            return back()->with('success', 'Confirmación de pago enviada por correo.');
        } catch (\Throwable $e) {
            $errorMsg = mb_substr($e->getMessage(), 0, 1000);
            \Illuminate\Support\Facades\Log::error('Error al enviar correo de pago factura #' . $id . ': ' . $e->getMessage());
            try {
                NotificacionFactura::create($this->baseNotif(
                    $factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
                    $correo, $asunto, $mensaje, 'ERROR',
                    'Error al enviar correo', null, $errorMsg
                ));
            } catch (\Throwable $ex) {
                \Illuminate\Support\Facades\Log::error('Error adicional al registrar notificación de fallo: ' . $ex->getMessage());
            }
            return back()->with('error', 'No se pudo enviar el correo.');
        }
    }

    // ─── HELPER PRIVADO ───────────────────────────────────────────────────────

    private function buildMensajeCobranza(Factura $factura): array
    {
        $numero = $factura->serie . '-' . $factura->numero;
        $fechaVenc = $factura->fecha_vencimiento ?: 'No registrada';
        $montoPendiente = $factura->monto_pendiente > 0 ? $factura->monto_pendiente : $factura->importe_total;
        $montoTxt = $factura->moneda . ' ' . number_format((float) $montoPendiente, 2);

        $asunto = "Recordatorio de pago - Factura {$numero}";
        $estadoLinea = 'se encuentra pendiente de pago.';
        $accionLinea = 'Le solicitamos regularizar el pago dentro del plazo correspondiente.';

        switch ($factura->estado) {
            case 'VENCIDO':
                $asunto = "Factura vencida - {$numero}";
                $estadoLinea = 'se encuentra vencida y pendiente de regularización.';
                $accionLinea = 'Solicitamos regularizar el pago a la brevedad.';
                break;
            case 'PAGO PARCIAL':
                $asunto = "Pago parcial registrado - Saldo pendiente {$numero}";
                $estadoLinea = 'registra un pago parcial y mantiene saldo pendiente.';
                $accionLinea = 'Solicitamos completar el pago del saldo pendiente.';
                break;
            case 'DIFERENCIA PENDIENTE':
                $asunto = "Diferencia pendiente por regularizar - {$numero}";
                $estadoLinea = 'presenta una diferencia pendiente por regularización.';
                $accionLinea = 'Solicitamos regularizar la diferencia pendiente informada.';
                break;
            case 'POR VALIDAR DETRACCION':
                $asunto = "Validación de detracción pendiente - {$numero}";
                $estadoLinea = 'se encuentra con detracción pendiente de validación.';
                $accionLinea = 'Solicitamos enviar o confirmar la constancia de detracción para su validación.';
                break;
        }

        $mensaje = "Estimado cliente:\n\n"
            . "Le informamos que la factura {$numero} {$estadoLinea}\n\n"
            . "Detalle:\n"
            . "Fecha de vencimiento: {$fechaVenc}\n"
            . "Monto pendiente: {$montoTxt}\n"
            . "Estado: {$factura->estado}\n\n"
            . $accionLinea . "\n"
            . "Si ya realizó la regularización, por favor omitir este mensaje.\n\n"
            . "Atentamente,\nSistema de Facturación";

        return [
            'asunto' => $asunto,
            'mensaje' => $mensaje,
        ];
    }

    private function baseNotif(
        int     $idFactura,
        string  $canal,
        string  $categoria,
        string  $tipo,
        string  $destinatario,
        ?string $asunto,
        string  $mensaje,
        string  $estado,
        ?string $observacion,
        mixed   $fechaEnvio = null,
        ?string $respuesta  = null
    ): array {
        return [
            'id_factura'          => $idFactura,
            'id_regla'            => null,
            'canal'               => $canal,
            'categoria'           => $categoria,
            'tipo_notificacion'   => $tipo,
            'numero_intento_dia'  => 1,
            'destinatario'        => $destinatario,
            'asunto'              => $asunto,
            'mensaje'             => $mensaje,
            'estado_envio'        => $estado,
            'fecha_programada'    => now(),
            'fecha_envio'         => $fechaEnvio,
            'respuesta_proveedor' => $respuesta,
            'observacion'         => $observacion,
            'fecha_creacion'      => now(),
            'fecha_actualizacion' => now(),
        ];
    }

    private function resolveComprobanteUrl(?string $storedValue): ?string
    {
        if (!$storedValue) {
            return null;
        }

        $value = trim((string) $storedValue);
        if ($value === '') {
            return null;
        }

        $key = $value;
        if (preg_match('/^https?:\/\//i', $value)) {
            $parsedPath = parse_url($value, PHP_URL_PATH) ?? '';
            $key = ltrim((string) $parsedPath, '/');

            $bucket = (string) config('filesystems.disks.s3.bucket');
            if ($bucket !== '' && str_starts_with($key, $bucket . '/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
        }

        $key = ltrim($key, '/');
        if ($key === '') {
            return null;
        }

        $disk = Storage::disk('s3');

        try {
            if (is_object($disk) && method_exists($disk, 'temporaryUrl')) {
                return call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes(60));
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }

        if (is_object($disk) && method_exists($disk, 'url')) {
            return call_user_func([$disk, 'url'], $key);
        }

        return null;
    }
}
