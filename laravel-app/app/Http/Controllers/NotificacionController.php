<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\NotificacionFactura;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class NotificacionController extends Controller
{
    public function enviarWhatsAppManual(int $id, WhatsAppGatewayService $gateway): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if ($factura->estado !== 'PENDIENTE') {
            return back()->with('error', 'Solo se puede enviar a facturas pendientes.');
        }

        if (!$factura->cliente) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'WHATSAPP',
                'categoria' => 'COBRANZA',
                'tipo_notificacion' => 'DEUDA_INICIAL',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => null,
                'mensaje' => 'No se pudo enviar porque la factura no tiene cliente asociado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'fecha_envio' => null,
                'respuesta_proveedor' => null,
                'observacion' => 'Factura sin cliente',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->celular) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'WHATSAPP',
                'categoria' => 'COBRANZA',
                'tipo_notificacion' => 'DEUDA_INICIAL',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => null,
                'mensaje' => 'No se pudo enviar porque el cliente no tiene celular registrado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'fecha_envio' => null,
                'respuesta_proveedor' => null,
                'observacion' => 'Cliente sin celular',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            return back()->with('error', 'El cliente no tiene celular registrado.');
        }

        $mensaje = "Estimado cliente:\n\n"
            . "Le informamos que la factura {$factura->serie}-{$factura->numero} se encuentra pendiente de pago.\n\n"
            . "Detalle de la factura:\n"
            . "- Fecha de vencimiento: {$factura->fecha_vencimiento}\n"
            . "- Monto pendiente: S/ " . number_format($factura->importe_total, 2) . "\n\n"
            . "Le solicitamos realizar el pago correspondiente al BCP dentro del plazo indicado.\n"
            . "Asimismo, no olvidar el abono de la detracción en el Banco de la Nación, de corresponder.\n\n"
            . "Si ya realizó el pago, por favor omita este mensaje.\n\n"
            . "Atentamente,\n"
            . "Sistema de Facturación";

        $resultado = $gateway->enviar($factura->cliente->celular, $mensaje);

        NotificacionFactura::create([
            'id_factura' => $factura->id_factura,
            'id_regla' => null,
            'canal' => 'WHATSAPP',
            'categoria' => 'COBRANZA',
            'tipo_notificacion' => 'DEUDA_INICIAL',
            'numero_intento_dia' => 1,
            'destinatario' => $factura->cliente->celular,
            'asunto' => null,
            'mensaje' => $mensaje,
            'estado_envio' => $resultado['ok'] ? 'ENVIADO' : 'ERROR',
            'fecha_programada' => now(),
            'fecha_envio' => $resultado['ok'] ? now() : null,
            'respuesta_proveedor' => $resultado['ok']
                ? json_encode($resultado['data'], JSON_UNESCAPED_UNICODE)
                : $resultado['error'],
            'observacion' => $resultado['ok'] ? 'Envío manual por botón' : 'Error al enviar WhatsApp',
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
        ]);

        return back()->with(
            $resultado['ok'] ? 'success' : 'error',
            $resultado['ok'] ? 'WhatsApp enviado correctamente.' : 'No se pudo enviar el WhatsApp.'
        );
    }

    public function enviarCorreoManual(int $id)
    {
        $factura = \App\Models\Factura::with('cliente')->findOrFail($id);

        if ($factura->estado !== 'PENDIENTE') {
            return back()->with('error', 'Solo se puede enviar correo a facturas pendientes.');
        }

        if (!$factura->cliente) {
            \App\Models\NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'COBRANZA',
                'tipo_notificacion' => 'DEUDA_INICIAL',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => 'Factura pendiente de pago',
                'mensaje' => 'No se pudo enviar porque la factura no tiene cliente asociado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'fecha_envio' => null,
                'respuesta_proveedor' => null,
                'observacion' => 'Factura sin cliente',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->correo) {
            \App\Models\NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'COBRANZA',
                'tipo_notificacion' => 'DEUDA_INICIAL',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => 'Factura pendiente de pago',
                'mensaje' => 'No se pudo enviar porque el cliente no tiene correo registrado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'fecha_envio' => null,
                'respuesta_proveedor' => null,
                'observacion' => 'Cliente sin correo',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            return back()->with('error', 'El cliente no tiene correo registrado.');
        }

        $asunto = "Recordatorio de pago - Factura {$factura->serie}-{$factura->numero}";

        $mensaje = "Estimado cliente:\n\n"
            . "Por medio del presente, le recordamos que la factura {$factura->serie}-{$factura->numero} se encuentra pendiente de pago.\n\n"
            . "Detalle de la factura:\n"
            . "Fecha de vencimiento: {$factura->fecha_vencimiento}\n"
            . "Monto pendiente: S/ " . number_format($factura->importe_total, 2) . "\n\n"
            . "Le solicitamos efectuar el pago correspondiente al BCP dentro de la fecha establecida.\n"
            . "Asimismo, no olvidar el depósito de la detracción en el Banco de la Nación, de corresponder.\n\n"
            . "Si el pago ya fue realizado, agradeceremos hacer caso omiso a esta comunicación.\n\n"
            . "Quedamos atentos a cualquier consulta.\n\n"
            . "Atentamente,\n"
            . "Sistema de Facturación";

        try {
            Mail::raw($mensaje, function ($mail) use ($factura, $asunto) {
                $mail->to($factura->cliente->correo)
                    ->subject($asunto);
            });

            \App\Models\NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'COBRANZA',
                'tipo_notificacion' => 'DEUDA_INICIAL',
                'numero_intento_dia' => 1,
                'destinatario' => $factura->cliente->correo,
                'asunto' => $asunto,
                'mensaje' => $mensaje,
                'estado_envio' => 'ENVIADO',
                'fecha_programada' => now(),
                'fecha_envio' => now(),
                'respuesta_proveedor' => 'Correo enviado correctamente',
                'observacion' => 'Envío manual por botón',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            return back()->with('success', 'Correo enviado correctamente.');
        } catch (\Exception $e) {
            \App\Models\NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'COBRANZA',
                'tipo_notificacion' => 'DEUDA_INICIAL',
                'numero_intento_dia' => 1,
                'destinatario' => $factura->cliente->correo,
                'asunto' => $asunto,
                'mensaje' => $mensaje,
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'fecha_envio' => null,
                'respuesta_proveedor' => $e->getMessage(),
                'observacion' => 'Error al enviar correo',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            return back()->with('error', 'No se pudo enviar el correo.');
        }
    }

    /**
     * Enviar reporte de factura PAGADA vía WhatsApp
     */
    public function enviarFacturaPagadaWhatsApp(int $id, WhatsAppGatewayService $gateway): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!$factura->cliente) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'WHATSAPP',
                'categoria' => 'ENVIO_FACTURA',
                'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => null,
                'mensaje' => 'No se pudo enviar porque la factura no tiene cliente asociado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'respuesta_proveedor' => null,
                'observacion' => 'Factura sin cliente',
                'fecha_creacion' => now(),
            ]);

            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->celular) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'WHATSAPP',
                'categoria' => 'ENVIO_FACTURA',
                'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => null,
                'mensaje' => 'No se pudo enviar porque el cliente no tiene celular registrado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'respuesta_proveedor' => null,
                'observacion' => 'Cliente sin celular',
                'fecha_creacion' => now(),
            ]);

            return back()->with('error', 'El cliente no tiene celular registrado.');
        }

        $mensaje = "Estimado cliente:\n\n"
            . "Le informamos que su factura {$factura->serie}-{$factura->numero} ha sido PAGADA correctamente.\n\n"
            . "Detalle de pago:\n"
            . "- Factura: {$factura->serie}-{$factura->numero}\n"
            . "- Fecha de pago: " . ($factura->fecha_abono ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y') : 'Registrada') . "\n"
            . "- Monto pagado: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n"
            . "- Estado: ✓ PAGADA\n\n"
            . "Gracias por su confianza en nuestros servicios.\n\n"
            . "Atentamente,\n"
            . "Sistema de Facturación";

        $resultado = $gateway->enviar($factura->cliente->celular, $mensaje);

        NotificacionFactura::create([
            'id_factura' => $factura->id_factura,
            'id_regla' => null,
            'canal' => 'WHATSAPP',
            'categoria' => 'ENVIO_FACTURA',
            'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
            'numero_intento_dia' => 1,
            'destinatario' => $factura->cliente->celular,
            'asunto' => null,
            'mensaje' => $mensaje,
            'estado_envio' => $resultado['ok'] ? 'ENVIADO' : 'ERROR',
            'fecha_programada' => now(),
            'fecha_envio' => $resultado['ok'] ? now() : null,
            'respuesta_proveedor' => $resultado['ok']
                ? json_encode($resultado['data'], JSON_UNESCAPED_UNICODE)
                : $resultado['error'],
            'observacion' => 'Reporte de factura pagada',
            'fecha_creacion' => now(),
        ]);

        return back()->with(
            $resultado['ok'] ? 'success' : 'error',
            $resultado['ok'] ? 'Reporte enviado vía WhatsApp.' : 'No se pudo enviar el WhatsApp.'
        );
    }

    /**
     * Enviar reporte de factura PAGADA vía Correo
     */
    public function enviarFacturaPagadaCorreo(int $id): RedirectResponse
    {
        $factura = Factura::with('cliente')->findOrFail($id);

        if (!$factura->cliente) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'ENVIO_FACTURA',
                'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => 'Factura Pagada',
                'mensaje' => 'No se pudo enviar porque la factura no tiene cliente asociado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'observacion' => 'Factura sin cliente',
                'fecha_creacion' => now(),
            ]);

            return back()->with('error', 'La factura no tiene cliente asociado.');
        }

        if (!$factura->cliente->correo) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'ENVIO_FACTURA',
                'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
                'numero_intento_dia' => 1,
                'destinatario' => '',
                'asunto' => 'Factura Pagada',
                'mensaje' => 'No se pudo enviar porque el cliente no tiene correo registrado.',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'observacion' => 'Cliente sin correo',
                'fecha_creacion' => now(),
            ]);

            return back()->with('error', 'El cliente no tiene correo registrado.');
        }

        try {
            $asunto = "Confirmación de Pago - Factura {$factura->serie}-{$factura->numero}";
            
            $mensaje = "Estimado cliente,\n\n"
                . "Le informamos que su factura {$factura->serie}-{$factura->numero} ha sido PAGADA correctamente.\n\n"
                . "Detalle de pago:\n"
                . "- Factura: {$factura->serie}-{$factura->numero}\n"
                . "- Fecha de pago: " . ($factura->fecha_abono ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y') : 'Registrada') . "\n"
                . "- Monto pagado: {$factura->moneda} " . number_format($factura->importe_total, 2) . "\n"
                . "- Estado: ✓ PAGADA\n\n"
                . "Gracias por su confianza en nuestros servicios.\n\n"
                . "Atentamente,\n"
                . "Sistema de Facturación";

            Mail::send([], [], function ($message) use ($factura, $asunto, $mensaje) {
                $message->to($factura->cliente->correo)
                    ->subject($asunto)
                    ->setBody($mensaje);
            });

            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'ENVIO_FACTURA',
                'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
                'numero_intento_dia' => 1,
                'destinatario' => $factura->cliente->correo,
                'asunto' => $asunto,
                'mensaje' => $mensaje,
                'estado_envio' => 'ENVIADO',
                'fecha_programada' => now(),
                'fecha_envio' => now(),
                'observacion' => 'Reporte de factura pagada',
                'fecha_creacion' => now(),
            ]);

            return back()->with('success', 'Reporte enviado vía correo.');
        } catch (\Exception $e) {
            NotificacionFactura::create([
                'id_factura' => $factura->id_factura,
                'id_regla' => null,
                'canal' => 'CORREO',
                'categoria' => 'ENVIO_FACTURA',
                'tipo_notificacion' => 'ENVIO_FACTURA_PAGADA',
                'numero_intento_dia' => 1,
                'destinatario' => $factura->cliente->correo,
                'asunto' => $asunto ?? 'Factura Pagada',
                'mensaje' => $mensaje ?? '',
                'estado_envio' => 'ERROR',
                'fecha_programada' => now(),
                'respuesta_proveedor' => $e->getMessage(),
                'observacion' => 'Error al enviar correo',
                'fecha_creacion' => now(),
            ]);

            return back()->with('error', 'No se pudo enviar el correo.');
        }
    }
}
