<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\WhatsAppGatewayService;

class ReporteController extends Controller
{
    /** Vista principal con filtros. */
    public function index()
    {
        $clientes = DB::table('cliente')
            ->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc', 'celular', 'correo']);

        return view('reportes.index', compact('clientes'));
    }

    /** JSON para previsualización AJAX. */
    public function json(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $facturas = $this->queryFacturas($idCliente, $estado, $fechaDesde, $fechaHasta)->get();
        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $clienteNombre  = 'TODOS LOS CLIENTES';
        $clienteCelular = null;
        $clienteCorreo  = null;

        if ($idCliente) {
            $cli = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if ($cli) {
                $clienteNombre  = strtoupper($cli->razon_social);
                $clienteCelular = $cli->celular;
                $clienteCorreo  = $cli->correo;
            }
        }

        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);

        return response()->json([
            'facturas'        => $facturas->values(),
            'cliente_nombre'  => $clienteNombre,
            'cliente_celular' => $clienteCelular,
            'cliente_correo'  => $clienteCorreo,
            'estado_label'    => $estado ? strtoupper($estado) : 'TODOS LOS ESTADOS',
            'periodo_label'   => $periodoLabel,
            'resumen' => [
                'total_facturas'    => $facturas->count(),
                'pendientes'        => $facturas->whereIn('estado', ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])->count(),
                'pagadas'           => $facturas->where('estado', 'PAGADA')->count(),
                'total_bruto'       => $facturas->sum('importe_total'),
                'total_recaudacion' => $facturas->sum('monto_recaudacion'),
                'total_neto'        => $facturas->sum('neto_caja'),
                'saldo_cobrar'      => $facturas->whereNotIn('estado', ['PAGADA', 'ANULADA'])->sum('neto_caja'),
            ],
        ]);
    }

    /** Vista PDF — se abre en nueva pestaña. */
    public function pdf(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $facturas = $this->queryFacturas($idCliente, $estado, $fechaDesde, $fechaHasta)->get();
        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $facturasAgrupadas = null;
        if (!$idCliente) {
            $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();
        }

        $resumen = [
            'total_facturas'    => $facturas->count(),
            'pendientes'        => $facturas->whereIn('estado', ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])->count(),
            'pagadas'           => $facturas->where('estado', 'PAGADA')->count(),
            'vencidas'          => $facturas->where('estado', 'VENCIDA')->count(),
            'total_bruto'       => $facturas->sum('importe_total'),
            'total_recaudacion' => $facturas->sum('monto_recaudacion'),
            'total_neto'        => $facturas->sum('neto_caja'),
            'saldo_cobrar'      => $facturas->whereNotIn('estado', ['PAGADA', 'ANULADA'])->sum('neto_caja'),
        ];

        $clienteNombre = 'TODOS LOS CLIENTES';
        if ($idCliente) {
            $cli = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if ($cli) $clienteNombre = strtoupper($cli->razon_social);
        }

        $estadoLabel  = $estado ? strtoupper($estado) : 'TODOS LOS ESTADOS';
        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);

        return view('reportes.pdf', compact(
            'facturas', 'facturasAgrupadas', 'resumen',
            'clienteNombre', 'estadoLabel', 'idCliente', 'periodoLabel'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENVÍO POR WHATSAPP
    // ─────────────────────────────────────────────────────────────────────────

    public function enviarReporteWhatsApp(Request $request, WhatsAppGatewayService $gateway)
    {
        $idCliente  = $request->input('id_cliente');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        if (!$idCliente) {
            return response()->json([
                'success' => false,
                'error'   => 'Debes seleccionar un cliente específico para enviar el reporte.',
            ], 422);
        }

        $cliente = DB::table('cliente')->where('id_cliente', $idCliente)->first();

        if (!$cliente) {
            return response()->json(['success' => false, 'error' => 'Cliente no encontrado.'], 404);
        }

        if (!$cliente->celular) {
            return response()->json([
                'success' => false,
                'error'   => 'El cliente no tiene número de celular/WhatsApp registrado.',
            ], 422);
        }

        // ── Obtener facturas ──────────────────────────────────────────────
        $facturas = $this->queryFacturas($idCliente, $estado, $fechaDesde, $fechaHasta)->get();
        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $periodoLabel  = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel   = $estado ? strtoupper($estado) : 'TODOS';
        $totalBruto    = $facturas->sum('importe_total');
        $totalNeto     = $facturas->sum('neto_caja');
        $totalDetrac   = $facturas->sum('monto_recaudacion');
        $pendientes    = $facturas->whereIn('estado', ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])->count();
        $pagadas       = $facturas->where('estado', 'PAGADA')->count();
        $saldoCobrar   = $facturas->whereNotIn('estado', ['PAGADA', 'ANULADA'])->sum('neto_caja');

        // ── Construir detalle de facturas (máx 20 líneas para no saturar WA) ──
        $lineasFactura = '';
        $mostradas     = 0;
        foreach ($facturas as $f) {
            if ($mostradas >= 20) {
                $lineasFactura .= "... y " . ($facturas->count() - $mostradas) . " facturas más.\n";
                break;
            }
            $estado_icon = match($f->estado) {
                'PAGADA'     => '✅',
                'PENDIENTE'  => '🟡',
                'POR_VENCER' => '🟠',
                'VENCIDA'    => '🔴',
                'ANULADA'    => '⚫',
                default      => '⚪',
            };
            $lineasFactura .= "{$estado_icon} *{$f->serie}-" . str_pad($f->numero, 8, '0', STR_PAD_LEFT) . "* "
                . "| " . ($f->fecha_emision ?? '—')
                . " | {$f->moneda} " . number_format($f->importe_total, 2)
                . "\n";
            $mostradas++;
        }

        // ── URL del PDF ────────────────────────────────────────────────────
        $params    = http_build_query(array_filter([
            'id_cliente'  => $idCliente,
            'estado'      => $estado,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ]));
        $reporteUrl = url('/reportes/pdf?' . $params);

        // ── Mensaje ────────────────────────────────────────────────────────
        $mensaje = "📊 *REPORTE FINANCIERO — CRC S.A.C.*\n"
            . "══════════════════════════\n"
            . "🏢 *Empresa:* " . strtoupper($cliente->razon_social) . "\n"
            . "📅 *Período:* {$periodoLabel}\n"
            . "🔖 *Filtro estado:* {$estadoLabel}\n\n"
            . "📋 *DETALLE DE FACTURAS ({$facturas->count()})*\n"
            . "──────────────────────────\n"
            . $lineasFactura
            . "\n"
            . "💰 *RESUMEN*\n"
            . "──────────────────────────\n"
            . "• Total bruto:       S/ " . number_format($totalBruto,  2) . "\n"
            . "• Total recaudación: S/ " . number_format($totalDetrac, 2) . "\n"
            . "• Total neto caja:   S/ " . number_format($totalNeto,   2) . "\n"
            . "• Pendientes/Venc.:  *{$pendientes}* facturas\n"
            . "• Pagadas:           *{$pagadas}* facturas\n"
            . "• *Saldo por cobrar: S/ " . number_format($saldoCobrar, 2) . "*\n\n"
            . "🔗 Ver reporte completo:\n"
            . "{$reporteUrl}\n\n"
            . "_Consorcio Rodriguez Caballero S.A.C._";

        $resultado = $gateway->enviar($cliente->celular, $mensaje);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok']
                ? "Reporte enviado por WhatsApp al {$cliente->celular}"
                : 'No se pudo enviar el WhatsApp: ' . ($resultado['error'] ?? 'Error desconocido'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENVÍO POR CORREO
    // ─────────────────────────────────────────────────────────────────────────

    public function enviarReporteCorreo(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        if (!$idCliente) {
            return response()->json([
                'success' => false,
                'error'   => 'Debes seleccionar un cliente específico para enviar el reporte.',
            ], 422);
        }

        $cliente = DB::table('cliente')->where('id_cliente', $idCliente)->first();

        if (!$cliente) {
            return response()->json(['success' => false, 'error' => 'Cliente no encontrado.'], 404);
        }

        if (!$cliente->correo) {
            return response()->json([
                'success' => false,
                'error'   => 'El cliente no tiene correo electrónico registrado.',
            ], 422);
        }

        $facturas = $this->queryFacturas($idCliente, $estado, $fechaDesde, $fechaHasta)->get();
        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $facturasAgrupadas = null;
        $periodoLabel  = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel   = $estado ? strtoupper($estado) : 'TODOS LOS ESTADOS';
        $clienteNombre = strtoupper($cliente->razon_social);

        $resumen = [
            'total_facturas'    => $facturas->count(),
            'pendientes'        => $facturas->whereIn('estado', ['PENDIENTE', 'POR_VENCER', 'VENCIDA'])->count(),
            'pagadas'           => $facturas->where('estado', 'PAGADA')->count(),
            'vencidas'          => $facturas->where('estado', 'VENCIDA')->count(),
            'total_bruto'       => $facturas->sum('importe_total'),
            'total_recaudacion' => $facturas->sum('monto_recaudacion'),
            'total_neto'        => $facturas->sum('neto_caja'),
            'saldo_cobrar'      => $facturas->whereNotIn('estado', ['PAGADA', 'ANULADA'])->sum('neto_caja'),
        ];

        // Renderizar el HTML del reporte para enviarlo como cuerpo del correo
        $htmlReporte = view('reportes.pdf', compact(
            'facturas', 'facturasAgrupadas', 'resumen',
            'clienteNombre', 'estadoLabel', 'idCliente', 'periodoLabel'
        ))->render();

        // Quitar botones de impresión (no tienen sentido en email)
        $htmlReporte = preg_replace('/<div class="no-print".*?<\/div>/s', '', $htmlReporte);
        // Quitar el script de autoprint
        $htmlReporte = preg_replace('/<script>[\s\S]*?window\.print[\s\S]*?<\/script>/', '', $htmlReporte);

        $asunto = "Reporte Financiero — {$clienteNombre} — {$periodoLabel}";

        try {
            Mail::send([], [], function ($mail) use ($cliente, $asunto, $htmlReporte) {
                $mail->to($cliente->correo)
                    ->subject($asunto)
                    ->setBody($htmlReporte, 'text/html');
            });

            return response()->json([
                'success' => true,
                'message' => "Reporte enviado por correo a {$cliente->correo}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo: ' . $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function queryFacturas($idCliente, $estado, $fechaDesde = null, $fechaHasta = null)
    {
        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('detraccion as d', 'd.id_factura', '=', 'f.id_factura')
            ->leftJoin('autodetraccion as ad', 'ad.id_factura', '=', 'f.id_factura')
            ->leftJoin('retencion as r', 'r.id_factura', '=', 'f.id_factura')
            ->select([
                'f.id_factura',
                'f.serie',
                'f.numero',
                'f.fecha_emision',
                'f.fecha_vencimiento',
                'f.fecha_abono',
                'f.glosa',
                'f.moneda',
                'f.importe_total',
                'f.subtotal_gravado',
                'f.monto_igv',
                'f.tipo_recaudacion',
                'f.estado',
                'f.forma_pago',
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
                DB::raw('CASE
                    WHEN d.total_detraccion IS NOT NULL THEN d.total_detraccion
                    WHEN ad.total_autodetraccion IS NOT NULL THEN ad.total_autodetraccion
                    WHEN r.total_retencion IS NOT NULL THEN r.total_retencion
                    ELSE 0
                END AS monto_recaudacion'),
                DB::raw('CASE
                    WHEN d.porcentaje IS NOT NULL THEN d.porcentaje
                    WHEN ad.porcentaje IS NOT NULL THEN ad.porcentaje
                    WHEN r.porcentaje IS NOT NULL THEN r.porcentaje
                    ELSE 0
                END AS porcentaje_recaudacion'),
                DB::raw('CASE
                    WHEN d.total_detraccion IS NOT NULL THEN "DETRACCION"
                    WHEN ad.total_autodetraccion IS NOT NULL THEN "AUTODETRACCION"
                    WHEN r.total_retencion IS NOT NULL THEN "RETENCION"
                    ELSE NULL
                END AS tipo_recaudacion_actual'),
            ])
            ->orderBy('c.razon_social')
            ->orderBy('f.fecha_emision')
            ->orderBy('f.numero');

        if ($idCliente)  $query->where('f.id_cliente', $idCliente);
        if ($estado)     $query->where('f.estado', $estado);
        if ($fechaDesde) $query->where('f.fecha_emision', '>=', $fechaDesde);
        if ($fechaHasta) $query->where('f.fecha_emision', '<=', $fechaHasta);

        return $query;
    }

    private function buildPeriodoLabel(?string $desde, ?string $hasta): string
    {
        if ($desde && $hasta) {
            return \Carbon\Carbon::parse($desde)->format('d/m/Y')
                . ' al '
                . \Carbon\Carbon::parse($hasta)->format('d/m/Y');
        }
        if ($desde) return 'Desde ' . \Carbon\Carbon::parse($desde)->format('d/m/Y');
        if ($hasta) return 'Hasta ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y');
        return 'Todos los períodos';
    }
}
