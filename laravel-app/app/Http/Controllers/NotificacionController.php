<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarCorreo;
use App\Jobs\EnviarWhatsApp;
use App\Jobs\EnviarWhatsAppDocumento;
use App\Models\Factura;
use App\Models\NotificacionFactura;
use App\Support\JobDispatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class NotificacionController extends Controller
{
    // Estados que indican pago pendiente (unificados con nueva DB)
    private const ESTADOS_PENDIENTES = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE', 'POR VALIDAR DETRACCION'];

    // ─── COBRANZA: FACTURAS PENDIENTES ───────────────────────────────────────

    public function enviarWhatsAppManual(int $id): RedirectResponse
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

        $notif = NotificacionFactura::create($this->baseNotif(
            $factura->id_factura, 'WHATSAPP', 'COBRANZA', 'DEUDA_INICIAL',
            $factura->cliente->celular, null, $contenido['mensaje'],
            'PROGRAMADO', 'Envío programado', null, null
        ));

        JobDispatch::send(EnviarWhatsApp::class, [
            $factura->cliente->celular,
            $contenido['mensaje'],
            null,
            $notif->id_notificacion,
        ]);

        return back()->with('success', 'Envío de WhatsApp programado correctamente.');
    }

    public function enviarCorreoManual(int $id): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!in_array($factura->estado, self::ESTADOS_PENDIENTES)) {
            return back()->with('error', 'Solo se puede enviar correo a facturas en estado pendiente de pago.');
        }

        if (!$factura->cliente?->correo) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL', '', 'Factura pendiente', 'Sin correo registrado.', 'ERROR', 'Cliente sin correo'));
            return back()->with('error', 'El cliente no tiene correo registrado.');
        }

        $contenido = $this->buildMensajeCobranza($factura);
        $html      = $this->buildCorreoHtml($factura, false);

        $notif = NotificacionFactura::create($this->baseNotif(
            $factura->id_factura, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL',
            $factura->cliente->correo, $contenido['asunto'], $contenido['mensaje'],
            'PROGRAMADO', 'Envío programado', null, null
        ));

        JobDispatch::send(EnviarCorreo::class, [
            $factura->cliente->correo,
            $contenido['asunto'],
            $html,
            $notif->id_notificacion,
        ]);

        return back()->with('success', 'Envío de correo programado correctamente.');
    }

    // ─── ENVÍO DE FACTURA PAGADA ──────────────────────────────────────────────

    public function enviarFacturaPagadaWhatsApp(int $id): RedirectResponse
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
        $isPdf    = $mediaUrl && preg_match('/\.pdf(\?|$)/i', $mediaUrl);

        $notif = NotificacionFactura::create($this->baseNotif(
            $factura->id_factura, 'WHATSAPP', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
            $factura->cliente->celular, null, $mensaje,
            'PROGRAMADO',
            $mediaUrl ? 'Envío programado con comprobante' : 'Envío programado sin comprobante',
            null, null
        ));

        if ($isPdf) {
            $fileName = 'Comprobante_' . $factura->serie . '-' . str_pad((string) $factura->numero, 8, '0', STR_PAD_LEFT) . '.pdf';
            JobDispatch::send(EnviarWhatsAppDocumento::class, [
                $factura->cliente->celular, $mediaUrl, $fileName, $mensaje, $notif->id_notificacion,
            ]);
        } else {
            JobDispatch::send(EnviarWhatsApp::class, [
                $factura->cliente->celular, $mensaje, $mediaUrl, $notif->id_notificacion,
            ]);
        }

        return back()->with('success', 'Envío de comprobante por WhatsApp programado correctamente.');
    }

    public function enviarFacturaPagadaCorreo(int $id): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!$factura->cliente?->correo) {
            return back()->with('error', 'El cliente no tiene correo registrado.');
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
        $html = $this->buildCorreoHtml($factura, true, $fechaPago);

        $notif = NotificacionFactura::create($this->baseNotif(
            $factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
            $factura->cliente->correo, $asunto, $mensaje,
            'PROGRAMADO', 'Envío programado', null, null
        ));

        JobDispatch::send(EnviarCorreo::class, [
            $factura->cliente->correo,
            $asunto,
            $html,
            $notif->id_notificacion,
        ]);

        return back()->with('success', 'Envío de confirmación de pago programado correctamente.');
    }

    // ─── HELPER PRIVADO ───────────────────────────────────────────────────────

    private function buildMensajeCobranza(Factura $factura): array
    {
        $numero = $factura->serie . '-' . $factura->numero;
        $fechaVenc = $factura->fecha_vencimiento ?: 'No registrada';
        $montoPendiente = $factura->monto_pendiente > 0 ? $factura->monto_pendiente : $factura->importe_total;
        $montoTxt = $factura->moneda . ' ' . number_format((float) $montoPendiente, 2);

        $asunto = "Confirmación de Pago - Factura {$numero}";
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

    private function buildCorreoHtml(Factura $factura, bool $pagada, ?string $fechaPago = null): string
    {
        $numero = $factura->serie . '-' . $factura->numero;
        $monto = $pagada
            ? (float) $factura->importe_total
            : (float) ($factura->monto_pendiente > 0 ? $factura->monto_pendiente : $factura->importe_total);

        return view('emails.factura-notificacion', [
            'titulo' => $pagada ? 'Confirmación de Pago' : 'Recordatorio de Pago',
            'numero' => $numero,
            'fechaEtiqueta' => $pagada ? 'Fecha de pago' : 'Fecha de vencimiento',
            'fecha' => $pagada ? ($fechaPago ?: 'Registrada') : ($factura->fecha_vencimiento ?: 'No registrada'),
            'monto' => $factura->moneda . ' ' . number_format($monto, 2),
            'estado' => $pagada ? 'PAGADA' : $factura->estado,
            'pagada' => $pagada,
        ])->render();
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
