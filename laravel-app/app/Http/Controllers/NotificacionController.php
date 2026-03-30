<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\NotificacionFactura;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class NotificacionController extends Controller
{
    // ─── COBRANZA: FACTURAS PENDIENTES ───────────────────────────────────────

    public function enviarWhatsAppManual(int $id, WhatsAppGatewayService $gateway): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!in_array($factura->estado, ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])) {
            return back()->with('error', 'Solo se puede enviar a facturas pendientes de pago.');
        }

        if (!$factura->cliente) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'WHATSAPP', 'COBRANZA', 'DEUDA_INICIAL', '', null, 'No se pudo enviar porque la factura no tiene cliente asociado.', 'ERROR', 'Factura sin cliente'));
            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->celular) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'WHATSAPP', 'COBRANZA', 'DEUDA_INICIAL', '', null, 'No se pudo enviar porque el cliente no tiene celular registrado.', 'ERROR', 'Cliente sin celular'));
            return back()->with('error', 'El cliente no tiene celular registrado.');
        }

        $mensaje = "Estimado cliente:\n\n"
            . "Le informamos que la factura *{$factura->serie}-{$factura->numero}* se encuentra pendiente de pago.\n\n"
            . "📋 *Detalle de la factura:*\n"
            . "• Fecha de vencimiento: {$factura->fecha_vencimiento}\n"
            . "• Monto pendiente: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n\n"
            . "Le solicitamos realizar el pago correspondiente al BCP dentro del plazo indicado.\n"
            . "Asimismo, no olvidar el abono de la detracción en el Banco de la Nación, de corresponder.\n\n"
            . "_Si ya realizó el pago, por favor omita este mensaje._\n\n"
            . "Atentamente,\n"
            . "Sistema de Facturación";

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
        $factura = \App\Models\Factura::with('cliente')->findOrFail($id);

        if (!in_array($factura->estado, ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])) {
            return back()->with('error', 'Solo se puede enviar correo a facturas pendientes de pago.');
        }

        if (!$factura->cliente) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL', '', 'Factura pendiente de pago', 'Sin cliente asociado.', 'ERROR', 'Factura sin cliente'));
            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->correo) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL', '', 'Factura pendiente de pago', 'Sin correo registrado.', 'ERROR', 'Cliente sin correo'));
            return back()->with('error', 'El cliente no tiene correo registrado.');
        }

        $asunto  = "Recordatorio de pago - Factura {$factura->serie}-{$factura->numero}";
        $mensaje = "Estimado cliente:\n\n"
            . "Por medio del presente, le recordamos que la factura {$factura->serie}-{$factura->numero} se encuentra pendiente de pago.\n\n"
            . "Detalle de la factura:\n"
            . "Fecha de vencimiento: {$factura->fecha_vencimiento}\n"
            . "Monto pendiente: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n\n"
            . "Le solicitamos efectuar el pago correspondiente al BCP dentro de la fecha establecida.\n"
            . "Asimismo, no olvidar el depósito de la detracción en el Banco de la Nación, de corresponder.\n\n"
            . "Si el pago ya fue realizado, agradeceremos hacer caso omiso a esta comunicación.\n\n"
            . "Atentamente,\nSistema de Facturación";

        try {
            $html = "
                <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f6f8; padding:20px;'>
                <tr>
                    <td align='center'>
                    
                    <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff; border-radius:10px; overflow:hidden; font-family:Arial, sans-serif;'>

                        <!-- HEADER -->
                        <tr>
                        <td style='background:#4CAF50; color:#ffffff; padding:20px; text-align:center; font-size:20px; font-weight:bold;'>
                            ✅ Confirmación de Pago
                        </td>
                        </tr>

                        <!-- BODY -->
                        <tr>
                        <td style='padding:20px; color:#333;'>

                            <p style='font-size:14px;'>Estimado cliente,</p>

                            <p style='font-size:14px;'>
                            Su factura <strong>{$factura->serie}-{$factura->numero}</strong> ha sido pagada correctamente.
                            </p>

                            <!-- TABLA DETALLE -->
                            <table width='100%' cellpadding='8' cellspacing='0' style='border-collapse:collapse; margin-top:15px;'>

                            <tr style='background:#f0f0f0;'>
                                <td><strong>Factura</strong></td>
                                <td>{$factura->serie}-{$factura->numero}</td>
                            </tr>

                            <tr>
                                <td><strong>Fecha de pago</strong></td>
                                <td>{$fechaPago}</td>
                            </tr>

                            <tr style='background:#f0f0f0;'>
                                <td><strong>Monto</strong></td>
                                <td>{$factura->moneda} " . number_format($factura->importe_total, 2) . "</td>
                            </tr>

                            <tr>
                                <td><strong>Estado</strong></td>
                                <td style='color:green; font-weight:bold;'>✓ PAGADA</td>
                            </tr>

                            </table>

                            <p style='margin-top:20px; font-size:14px;'>
                            Gracias por su confianza en nuestros servicios 🙌
                            </p>

                        </td>
                        </tr>

                        <!-- FOOTER -->
                        <tr>
                        <td style='background:#f4f4f4; text-align:center; padding:15px; font-size:12px; color:#777;'>
                            Sistema de Facturación
                        </td>
                        </tr>

                    </table>

                    </td>
                </tr>
                </table>
                ";

            NotificacionFactura::create($this->baseNotif(
                $factura->id_factura, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL',
                $factura->cliente->correo, $asunto, $mensaje, 'ENVIADO',
                'Envío manual por botón', now(), 'Correo enviado correctamente'
            ));

            return back()->with('success', 'Correo enviado correctamente.');
        } catch (\Exception $e) {
            NotificacionFactura::create($this->baseNotif(
                $factura->id_factura, 'CORREO', 'COBRANZA', 'DEUDA_INICIAL',
                $factura->cliente->correo, $asunto, $mensaje, 'ERROR',
                'Error al enviar correo', null, $e->getMessage()
            ));
            return back()->with('error', 'No se pudo enviar el correo.');
        }
    }

    // ─── ENVÍO DE FACTURA PAGADA ──────────────────────────────────────────────

    public function enviarFacturaPagadaWhatsApp(int $id, WhatsAppGatewayService $gateway): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!$factura->cliente) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'WHATSAPP', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA', '', null, 'Sin cliente asociado.', 'ERROR', 'Factura sin cliente'));
            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->celular) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'WHATSAPP', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA', '', null, 'Sin celular registrado.', 'ERROR', 'Cliente sin celular'));
            return back()->with('error', 'El cliente no tiene celular registrado.');
        }

        $fechaPago = $factura->fecha_abono
            ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y')
            : 'Registrada';

        $mensaje = "*Confirmación de Pago*\n\n"
            . "Estimado cliente, le informamos que su factura *{$factura->serie}-{$factura->numero}* ha sido procesada correctamente.\n\n"
            . " *Detalle de pago:*\n"
            . "• Factura: {$factura->serie}-{$factura->numero}\n"
            . "• Fecha de pago: {$fechaPago}\n"
            . "• Monto: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n"
            . "• Estado: ✓ PAGADA\n\n"
            . "Gracias por su confianza en nuestros servicios.\n\n"
            . "Atentamente,\nSistema de Facturación";

        $mediaUrl = $factura->ruta_comprobante_pago ?: null;

        $resultado = $gateway->enviar($factura->cliente->celular, $mensaje, $mediaUrl);

        $observacion = $resultado['ok']
            ? ($mediaUrl ? 'Enviado con imagen del comprobante' : 'Enviado sin imagen (sin comprobante)')
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
                ? ($mediaUrl ? 'Comprobante enviado vía WhatsApp con imagen.' : 'Mensaje enviado (sin imagen de comprobante adjunta).')
                : 'No se pudo enviar el WhatsApp.'
        );
    }

    public function enviarFacturaPagadaCorreo(int $id): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!$factura->cliente) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA', '', 'Factura Pagada', 'Sin cliente.', 'ERROR', 'Factura sin cliente'));
            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->correo) {
            NotificacionFactura::create($this->baseNotif($factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA', '', 'Factura Pagada', 'Sin correo.', 'ERROR', 'Cliente sin correo'));
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

        $mensaje .= "\nGracias por su confianza en nuestros servicios.\n\nAtentamente,\nSistema de Facturación";

        try {
            // ✅ Laravel 12 / Symfony Mailer: usar Mail::raw() para texto plano
            $html = "
                <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f6f8; padding:20px;'>
                <tr>
                    <td align='center'>
                    
                    <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff; border-radius:10px; font-family:Arial, sans-serif;'>

                        <!-- HEADER -->
                        <tr>
                        <td style='background:#2c3e50; color:#ffffff; padding:20px; text-align:center; font-size:20px; font-weight:bold;'>
                             Recordatorio de Pago
                        </td>
                        </tr>

                        <!-- BODY -->
                        <tr>
                        <td style='padding:20px; color:#333;'>

                            <p style='font-size:14px;'>Estimado cliente,</p>

                            <p style='font-size:14px;'>
                            Le recordamos que su factura <strong>{$factura->serie}-{$factura->numero}</strong> se encuentra pendiente de pago.
                            </p>

                            <!-- TABLA -->
                            <table width='100%' cellpadding='8' cellspacing='0' style='border-collapse:collapse; margin-top:15px;'>

                            <tr style='background:#ecf0f1;'>
                                <td><strong>Factura</strong></td>
                                <td>{$factura->serie}-{$factura->numero}</td>
                            </tr>

                            <tr>
                                <td><strong>Fecha de vencimiento</strong></td>
                                <td>{$factura->fecha_vencimiento}</td>
                            </tr>

                            <tr style='background:#ecf0f1;'>
                                <td><strong>Monto</strong></td>
                                <td>{$factura->moneda} " . number_format($factura->importe_total, 2) . "</td>
                            </tr>

                            <tr>
                                <td><strong>Estado</strong></td>
                                <td style='color:#e67e22; font-weight:bold;'>PENDIENTE</td>
                            </tr>

                            </table>

                            <p style='margin-top:20px; font-size:14px;'>
                            Por favor realizar el pago en el <strong>BCP</strong> dentro del plazo indicado.
                            </p>

                            <p style='font-size:12px; color:#888;'>
                            Si ya realizó el pago, puede ignorar este mensaje.
                            </p>

                        </td>
                        </tr>

                        <!-- FOOTER -->
                        <tr>
                        <td style='background:#f4f4f4; text-align:center; padding:15px; font-size:12px; color:#777;'>
                            Sistema de Facturación © " . date('Y') . "
                        </td>
                        </tr>

                    </table>

                    </td>
                </tr>
                </table>
                ";

                        Mail::send([], [], function ($m) use ($factura, $asunto, $html) {
                            $m->to($factura->cliente->correo)
                            ->subject($asunto)
                            ->html($html);
                        });

            NotificacionFactura::create($this->baseNotif(
                $factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
                $factura->cliente->correo, $asunto, $mensaje, 'ENVIADO',
                'Confirmación de pago', now()
            ));

            return back()->with('success', 'Confirmación de pago enviada por correo.');
        } catch (\Exception $e) {
            NotificacionFactura::create($this->baseNotif(
                $factura->id_factura, 'CORREO', 'ENVIO_FACTURA', 'ENVIO_FACTURA_PAGADA',
                $factura->cliente->correo, $asunto, $mensaje, 'ERROR',
                'Error al enviar correo', null, $e->getMessage()
            ));
            return back()->with('error', 'No se pudo enviar el correo.');
        }
    }

    // ─── HELPER PRIVADO ───────────────────────────────────────────────────────

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

    public function testCorreo()
{
    try {
        \Mail::raw('Correo de prueba', function ($m) {
            $m->to('dianacs78@hotmail.com')
              ->subject('Prueba Laravel');
        });

        return "Correo enviado correctamente";
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

}
