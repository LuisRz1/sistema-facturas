<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,sans-serif;color:#333333;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;">
                <tr>
                    <td style="background:#2c3e50;color:#ffffff;padding:24px;text-align:center;font-size:21px;font-weight:bold;">
                        &#128196; {{ $titulo }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 26px;color:#333333;">
                        <p style="font-size:14px;margin:0 0 18px;">Estimado cliente,</p>
                        <p style="font-size:14px;line-height:1.6;margin:0 0 20px;">
                            @if($pagada)
                                Le informamos que su factura <strong>{{ $numero }}</strong> ha sido pagada correctamente.
                            @else
                                Le recordamos que su factura <strong>{{ $numero }}</strong> se encuentra pendiente de pago.
                            @endif
                        </p>
                        <table role="presentation" width="100%" cellpadding="9" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
                            <tr style="background:#ecf0f1;">
                                <td width="63%"><strong>Factura</strong></td>
                                <td>{{ $numero }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ $fechaEtiqueta }}</strong></td>
                                <td>{{ $fecha }}</td>
                            </tr>
                            <tr style="background:#ecf0f1;">
                                <td><strong>Monto</strong></td>
                                <td>{{ $monto }}</td>
                            </tr>
                            <tr>
                                <td><strong>Estado</strong></td>
                                <td style="color:{{ $pagada ? '#059669' : '#e67e22' }};font-weight:bold;">{{ $estado }}</td>
                            </tr>
                        </table>
                        @if($pagada)
                            <p style="margin:22px 0 0;font-size:14px;line-height:1.6;">Gracias por su confianza en nuestros servicios.</p>
                        @else
                            <p style="margin:22px 0 0;font-size:14px;line-height:1.6;">Por favor, realice el pago dentro del plazo indicado.</p>
                            <p style="font-size:12px;color:#888888;margin:14px 0 0;">Si ya realizó el pago, puede ignorar este mensaje.</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#f4f4f4;text-align:center;padding:16px;font-size:12px;color:#777777;">
                        Sistema de Facturación &copy; {{ date('Y') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
