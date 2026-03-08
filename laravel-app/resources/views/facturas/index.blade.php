<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Facturas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
            color: #1f2937;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 32px;
            color: #111827;
        }

        .header p {
            margin-top: 6px;
            color: #6b7280;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #111827;
            color: #ffffff;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-pendiente {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-pagada {
            background: #dcfce7;
            color: #166534;
        }

        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-ok {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .btn {
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }

        .btn-whatsapp {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-whatsapp:hover {
            background: #15803d;
        }

        .muted {
            color: #6b7280;
            font-size: 12px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }

        .last-status {
            min-width: 180px;
        }

        .btn-correo {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-correo:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Gestión de Facturas</h1>
        <p>Listado de facturas con prueba de envío por WhatsApp.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Factura</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Vencimiento</th>
                <th>Monto</th>
                <th>Celular</th>
                <th>Última notificación</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody>
            @forelse($facturas as $factura)
                @php
                    $ultimaNotificacion = $factura->notificaciones->first();
                @endphp
                <tr>
                    <td><strong>{{ $factura->serie }}-{{ $factura->numero }}</strong></td>
                    <td>{{ $factura->cliente->razon_social ?? 'Sin cliente' }}</td>
                    <td>
                        @if($factura->estado === 'PENDIENTE')
                            <span class="badge badge-pendiente">{{ $factura->estado }}</span>
                        @else
                            <span class="badge badge-pagada">{{ $factura->estado }}</span>
                        @endif
                    </td>
                    <td>{{ $factura->fecha_vencimiento }}</td>
                    <td>S/ {{ number_format($factura->importe_total, 2) }}</td>
                    <td>{{ $factura->cliente->celular ?? 'Sin celular' }}</td>
                    <td class="last-status">
                        @if($ultimaNotificacion)
                            <div>
                                @if($ultimaNotificacion->estado_envio === 'ENVIADO')
                                    <span class="badge badge-ok">ENVIADO</span>
                                @elseif($ultimaNotificacion->estado_envio === 'ERROR')
                                    <span class="badge badge-error">ERROR</span>
                                @else
                                    <span class="badge">{{ $ultimaNotificacion->estado_envio }}</span>
                                @endif
                            </div>
                            <div class="muted" style="margin-top: 6px;">
                                {{ $ultimaNotificacion->canal }} - {{ $ultimaNotificacion->tipo_notificacion }}
                            </div>
                            <div class="muted">
                                {{ $ultimaNotificacion->observacion }}
                            </div>
                        @else
                            <span class="muted">Sin notificaciones</span>
                        @endif
                    </td>
                    <td>
                        @if($factura->estado === 'PENDIENTE')
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <form method="POST" action="{{ route('facturas.enviar-whatsapp-manual', $factura->id_factura) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-whatsapp">Enviar WhatsApp</button>
                                </form>

                                <form method="POST" action="{{ route('facturas.enviar-correo-manual', $factura->id_factura) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-correo">Enviar Correo</button>
                                </form>
                            </div>
                        @else
                            <span class="muted">No aplica</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="no-data">No hay facturas registradas.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
