<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\Email;
use App\Services\WhatsAppGatewayService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ReporteController extends Controller
{
    public function index()
    {
        $clientes = DB::table('cliente')->orderBy('razon_social')
            ->get(['id_cliente', 'razon_social', 'ruc', 'celular', 'correo']);
        return view('reportes.index', compact('clientes'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Calcula los IDs de facturas que son NCs ligadas a una factura que NO existe
     * en la base de datos (notas de crédito huérfanas).
     * Replica exactamente la lógica de FacturaController::index().
     */
    private function getOrphanFacturaIds(Collection $facturas): array
    {
        $facturaIds = $facturas->pluck('id_factura')->toArray();
        if (empty($facturaIds)) {
            return [];
        }

        $creditos = DB::table('credito')
            ->whereIn('id_factura', $facturaIds)
            ->where('activo', 1)
            ->get();

        $orphanIds = [];
        foreach ($creditos as $credito) {
            $existe = DB::table('factura')
                ->where('serie',  $credito->serie_doc_modificado)
                ->where('numero', $credito->numero_doc_modificado)
                ->where('activo', 1)
                ->exists();
            if (!$existe) {
                $orphanIds[] = (int) $credito->id_factura;
            }
        }

        return $orphanIds;
    }

    /**
     * Filtra la colección de facturas para usarla en totales:
     *   - Excluye NCs huérfanas (tienen credito pero la factura enlazada no existe).
     *   - Excluye ANULADO sin registro en credito (no son NCs ligadas).
     *   - Incluye ANULADO que SÍ tienen credito válido (NCs ligadas a factura existente).
     */
    private function filtrarParaTotales(Collection $facturas, array $orphanIds): Collection
    {
        // Pre-cargamos qué IDs tienen un registro en credito para evitar N+1
        $facturaIds     = $facturas->pluck('id_factura')->toArray();
        $idsConCredito  = empty($facturaIds)
            ? []
            : DB::table('credito')
                ->whereIn('id_factura', $facturaIds)
                ->where('activo', 1)
                ->pluck('id_factura')
                ->map(fn($id) => (int) $id)
                ->toArray();

        return $facturas->filter(function ($f) use ($orphanIds, $idsConCredito) {
            // 1. Excluir NCs huérfanas (independientemente del estado)
            if (in_array((int) $f->id_factura, $orphanIds)) {
                return false;
            }
            // 2. Para ANULADO: incluir SOLO si tiene registro credito
            //    (es una NC ligada cuya factura original sí existe)
            if ($f->estado === 'ANULADO') {
                return in_array((int) $f->id_factura, $idsConCredito);
            }
            return true;
        });
    }

    /**
     * Normalizar estados: si PENDIENTE está en el filtro, agregar también ANULADO
     * para que las NCs (aunque sean huérfanas) aparezcan en el reporte y puedan
     * mostrarse tachadas.
     */
    private function normalizarEstadosFiltro(array $estadosFiltro): array
    {
        if (in_array('PENDIENTE', $estadosFiltro) && !in_array('ANULADO', $estadosFiltro)) {
            $estadosFiltro[] = 'ANULADO';
        }
        return $estadosFiltro;
    }

    /**
     * Replica los indicadores de la pantalla Gestión de Facturas.
     */
    private function buildDashboardMetrics(Collection $facturasParaTotales): array
    {
        $totalFacturado = (float) $facturasParaTotales->sum('importe_total');
        $saldoPendiente = (float) $facturasParaTotales
            ->whereIn('estado', ['PENDIENTE', 'VENCIDO', 'DIFERENCIA PENDIENTE'])
            ->sum('monto_pendiente');
        $cobrado = (float) $facturasParaTotales->where('estado', 'PAGADA')->sum('importe_total');
        $montoRecaudacion = (float) $facturasParaTotales->sum('monto_recaudacion');
        $recaudDepositada = (float) $facturasParaTotales
            ->filter(fn($f) => !empty($f->fecha_recaudacion))
            ->sum('monto_recaudacion');

        return [
            'total_facturado'      => $totalFacturado,
            'saldo_pendiente'      => $saldoPendiente,
            'cobrado'              => $cobrado,
            'monto_recaudacion'    => $montoRecaudacion,
            'recaud_depositada'    => $recaudDepositada,
            'recaud_sin_confirmar' => max($montoRecaudacion - $recaudDepositada, 0),
        ];
    }

    /**
     * Agrega a cada fila un campo doc_relacion con formato:
     *   SERIE-NUMERO / SERIE-LIGADA-NUMERO-LIGADO
     * Ejemplo:
     *   FC01-00000215 / FF01-00006183
     */
    private function enriquecerRelacionCredito(Collection $facturas): Collection
    {
        if ($facturas->isEmpty()) {
            return $facturas;
        }

        $facturaIds = $facturas->pluck('id_factura')->map(fn($id) => (int) $id)->values()->all();

        $creditosDirectos = DB::table('credito')
            ->whereIn('id_factura', $facturaIds)
            ->where('activo', 1)
            ->get(['id_factura', 'serie_doc_modificado', 'numero_doc_modificado']);

        $creditosInversosQuery = DB::table('credito')
            ->where('activo', 1)
            ->select(['id_factura', 'serie_doc_modificado', 'numero_doc_modificado']);

        $facturas->each(function ($f) use ($creditosInversosQuery) {
            $creditosInversosQuery->orWhere(function ($q) use ($f) {
                $q->where('serie_doc_modificado', $f->serie)
                    ->where('numero_doc_modificado', $f->numero);
            });
        });

        $creditos = $creditosDirectos
            ->merge($creditosInversosQuery->get())
            ->unique(fn($c) => ((int) $c->id_factura) . '|' . $c->serie_doc_modificado . '|' . (int) $c->numero_doc_modificado)
            ->values();

        $creditoPorFacturaId = $creditos->keyBy(fn($c) => (int) $c->id_factura);
        $creditoPorDocMod    = $creditos->keyBy(fn($c) => $c->serie_doc_modificado . '|' . (int) $c->numero_doc_modificado);

        $facturasNc = DB::table('factura')
            ->whereIn('id_factura', $creditos->pluck('id_factura')->map(fn($id) => (int) $id)->unique()->values()->all())
            ->where('activo', 1)
            ->get(['id_factura', 'serie', 'numero'])
            ->keyBy(fn($f) => (int) $f->id_factura);

        return $facturas->map(function ($f) use ($creditoPorFacturaId, $creditoPorDocMod, $facturasNc) {
            $docActual = $f->serie . '-' . str_pad((string) $f->numero, 8, '0', STR_PAD_LEFT);
            $docLigado = null;

            // Caso 1: esta factura es nota de crédito y modifica otro documento.
            $creditoInfo = $creditoPorFacturaId->get((int) $f->id_factura);
            if ($creditoInfo) {
                $docLigado = $creditoInfo->serie_doc_modificado . '-' . str_pad((string) $creditoInfo->numero_doc_modificado, 8, '0', STR_PAD_LEFT);
            } else {
                // Caso 2: esta factura está siendo modificada por una NC.
                $keyMod = $f->serie . '|' . (int) $f->numero;
                $creditoAsociado = $creditoPorDocMod->get($keyMod);
                if ($creditoAsociado) {
                    $nc = $facturasNc->get((int) $creditoAsociado->id_factura);
                    if ($nc) {
                        $docLigado = $nc->serie . '-' . str_pad((string) $nc->numero, 8, '0', STR_PAD_LEFT);
                    }
                }
            }

            $f->doc_relacion = $docLigado ? ($docActual . ' / ' . $docLigado) : null;
            return $f;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

    public function json(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        if ($estado) {
            $estadosFiltro = [$estado];
        } else {
            $estadosFiltro = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE', 'PAGADA'];
        }
        $estadosFiltro = $this->normalizarEstadosFiltro($estadosFiltro);

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja         = $f->importe_total - ($f->monto_recaudacion ?? 0);
            $f->pendiente_display = $f->monto_pendiente;
            return $f;
        });
        $facturas = $this->enriquecerRelacionCredito($facturas);

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds   = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);

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
                'total_facturas'    => $facturasParaTotales->where('estado', '!=', 'ANULADO')->count(),
                'pendientes'        => $facturasParaTotales->whereNotIn('estado', ['PAGADA', 'ANULADO'])->count(),
                'pagadas'           => $facturasParaTotales->where('estado', 'PAGADA')->count(),
                'total_bruto'       => $facturasParaTotales->where('estado', '!=', 'ANULADO')->sum('importe_total'),
                'total_recaudacion' => $facturasParaTotales->where('estado', '!=', 'ANULADO')->sum('monto_recaudacion'),
                'total_neto'        => $facturasParaTotales->where('estado', '!=', 'ANULADO')->sum('neto_caja'),
                'saldo_cobrar'      => $facturasParaTotales->where('estado', '!=', 'ANULADO')->sum('pendiente_display'),
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $idCliente    = $request->input('id_cliente');
        $fechaDesde   = $request->input('fecha_desde');
        $fechaHasta   = $request->input('fecha_hasta');
        $estadosParam = $request->input('estados', []);
        $estadoSimple = $request->input('estado');

        if ($estadoSimple) {
            $estadosFiltro = [$estadoSimple];
        } elseif (!empty($estadosParam)) {
            $estadosFiltro = (array) $estadosParam;
        } else {
            $estadosFiltro = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE'];
        }
        $estadosFiltro = $this->normalizarEstadosFiltro($estadosFiltro);

        $usuarioIdsParam = $request->input('usuario_ids', []);
        $usuarioIdSimple = $request->input('usuario_id');
        $usuarioIds      = $usuarioIdSimple ? [$usuarioIdSimple] : (array) $usuarioIdsParam;

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja         = $f->importe_total - ($f->monto_recaudacion ?? 0);
            $f->pendiente_display = $f->monto_pendiente;
            return $f;
        });
        $facturas = $this->enriquecerRelacionCredito($facturas);

        // ── Pagos por factura (pago_factura) ───────────────────────────────
        $facturaIds = $facturas->pluck('id_factura')->toArray();
        $pagosMap   = DB::table('pago_factura')
            ->whereIn('id_factura', $facturaIds)
            ->where('activo', 1)
            ->orderBy('fecha_pago')
            ->orderBy('id_pago')
            ->get(['id_factura', 'fecha_pago', 'monto_pagado', 'banco_origen', 'cuenta_pago'])
            ->groupBy('id_factura');

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds    = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);

        // Todas las facturas agrupadas (incl. huérfanas tachadas) para la vista
        $facturasAgrupadas         = $facturas->groupBy('razon_social')->sortKeys();
        // Solo las que cuentan en totales para calcular subtotales en la vista
        $facturasAgrupParaTotales  = $facturasParaTotales->groupBy('razon_social')->sortKeys();

        $resumen = [
            'total_facturas'    => $facturasParaTotales->count(),
            'pendientes'        => $facturasParaTotales->count(),
            'pagadas'           => 0,
            'vencidas'          => $facturasParaTotales->where('estado', 'VENCIDO')->count(),
            'total_bruto'       => $facturasParaTotales->sum('importe_total'),
            'total_recaudacion' => $facturasParaTotales->sum('monto_recaudacion'),
            'total_neto'        => $facturasParaTotales->sum('neto_caja'),
            'saldo_cobrar'      => $facturasParaTotales->sum('pendiente_display'),
        ];
        $dashboard = $this->buildDashboardMetrics($facturasParaTotales);

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

        $usuariosDestino = !empty($usuarioIds)
            ? DB::table('usuario')->whereIn('id_usuario', $usuarioIds)->get()->all()
            : [];

        $todosUsuarios = DB::table('usuario')
            ->where(function ($q) { $q->whereNotNull('celular')->orWhereNotNull('correo'); })
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'celular', 'correo']);

        $estadoLabel       = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);
        $periodoLabel      = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadosFiltroJson = json_encode($estadosFiltro);

        return view('reportes.pdf', compact(
            'facturas', 'facturasAgrupadas', 'facturasAgrupParaTotales', 'resumen',
            'dashboard',
            'clienteNombre', 'estadoLabel', 'idCliente', 'periodoLabel',
            'fechaDesde', 'fechaHasta', 'clienteCelular', 'clienteCorreo',
            'usuariosDestino', 'todosUsuarios', 'estadosFiltroJson',
            'orphanFacturaIds',
            'pagosMap'
        ));
    }

    public function enviarReporteWhatsApp(Request $request, WhatsAppGatewayService $gateway)
    {
        $idCliente  = $request->input('id_cliente');
        $usuarioId  = $request->input('usuario_id');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $tipoReporte = $request->input('tipo_reporte', 'detallado');

        $celular = null;
        $nombre  = null;
        if ($usuarioId) {
            $dest = DB::table('usuario')->where('id_usuario', $usuarioId)->first();
            if (!$dest || !$dest->celular) {
                return response()->json(['success' => false, 'error' => 'El usuario no tiene celular registrado.'], 422);
            }
            $celular = $dest->celular;
            $nombre  = $dest->nombre . ' ' . $dest->apellido;
        } elseif ($idCliente) {
            $cliente = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if (!$cliente || !$cliente->celular) {
                return response()->json(['success' => false, 'error' => 'El cliente no tiene celular registrado.'], 422);
            }
            $celular = $cliente->celular;
            $nombre  = $cliente->razon_social;
        } else {
            return response()->json(['success' => false, 'error' => 'Debes seleccionar un cliente o usuario destino.'], 422);
        }

        $estadosParam  = $request->input('estados', []);
        $estadosFiltro = !empty($estadosParam)
            ? (array) $estadosParam
            : ($estado ? [$estado] : ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE']);
        $estadosFiltro = $this->normalizarEstadosFiltro($estadosFiltro);

        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel  = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);

        if ($tipoReporte === 'general') {
            try {
                $htmlReporte = $this->deudaGeneral($request)->render();
                $htmlReporte = preg_replace('/<div class="no-print".*?<\/div>/s', '', $htmlReporte);
                $htmlReporte = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $htmlReporte);
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlReporte)->setPaper('a4', 'portrait');
                $pdfContent = $pdf->output();
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'error' => 'No se pudo generar el PDF: ' . $e->getMessage()], 500);
            }

            $cloudUrl = $this->subirPdfACloudinary($pdfContent, $estadoLabel, $periodoLabel);
            if (!$cloudUrl) {
                return response()->json(['success' => false, 'error' => 'No se pudo subir el PDF a Cloudinary.'], 500);
            }

            $partes        = ['Reporte_Deuda_General'];
            $partes[]      = preg_replace('/[^A-Za-z0-9]/', '_', $estadoLabel);
            if ($fechaDesde) $partes[] = str_replace('-', '', $fechaDesde);
            if ($fechaHasta) $partes[] = 'al_' . str_replace('-', '', $fechaHasta);
            $nombreArchivo = implode('_', $partes) . '.pdf';
            $caption       = "*Reporte Deuda General — CRC S.A.C.*\n{$periodoLabel}\nEstado: {$estadoLabel}";
            $resultado     = $gateway->enviarDocumento($celular, $cloudUrl, $nombreArchivo, $caption);

            return response()->json([
                'success' => $resultado['ok'],
                'message' => $resultado['ok']
                    ? "PDF enviado por WhatsApp a {$nombre} ({$celular})"
                    : 'No se pudo enviar: ' . ($resultado['error'] ?? 'Error'),
            ]);
        }

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja         = $f->importe_total - ($f->monto_recaudacion ?? 0);
            $f->pendiente_display = $f->monto_pendiente;
            return $f;
        });
        $facturas = $this->enriquecerRelacionCredito($facturas);

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds    = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);

        $periodoLabel      = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel       = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);
        $clienteNombre     = strtoupper($nombre ?? 'TODOS LOS CLIENTES');
        $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();
        $facturasAgrupParaTotales = $facturasParaTotales->groupBy('razon_social')->sortKeys();
        $usuarioDestino    = null;
        $todosUsuarios     = collect([]);
        $estadosFiltroJson = json_encode($estadosFiltro);

        $resumen = [
            'total_facturas'    => $facturasParaTotales->count(),
            'pendientes'        => $facturasParaTotales->count(),
            'pagadas'           => 0,
            'vencidas'          => $facturasParaTotales->where('estado', 'VENCIDO')->count(),
            'total_bruto'       => $facturasParaTotales->sum('importe_total'),
            'total_recaudacion' => $facturasParaTotales->sum('monto_recaudacion'),
            'total_neto'        => $facturasParaTotales->sum('neto_caja'),
            'saldo_cobrar'      => $facturasParaTotales->sum('pendiente_display'),
        ];
        $dashboard = $this->buildDashboardMetrics($facturasParaTotales);

        try {
            // Genera el mismo reporte "Por Empresa" que el usuario está viendo en pantalla.
            $htmlReporte = view('reportes.pdf', compact(
                'facturas', 'facturasAgrupadas', 'facturasAgrupParaTotales', 'resumen',
                'dashboard',
                'clienteNombre', 'estadoLabel', 'idCliente', 'periodoLabel',
                'usuarioDestino', 'todosUsuarios', 'estadosFiltroJson',
                'fechaDesde', 'fechaHasta', 'orphanFacturaIds'
            ))->render();
            $htmlReporte = preg_replace('/<div class="no-print".*?<\/div>/s', '', $htmlReporte);
            $htmlReporte = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $htmlReporte);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlReporte)->setPaper('a4', 'landscape');
            $pdfContent = $pdf->output();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'No se pudo generar el PDF: ' . $e->getMessage()], 500);
        }

        $cloudUrl = $this->subirPdfACloudinary($pdfContent, $estadoLabel, $periodoLabel);
        if (!$cloudUrl) {
            return response()->json(['success' => false, 'error' => 'No se pudo subir el PDF a Cloudinary.'], 500);
        }

        $partes        = ['Reporte'];
        $partes[]      = preg_replace('/[^A-Za-z0-9]/', '_', $estadoLabel);
        if ($fechaDesde) $partes[] = str_replace('-', '', $fechaDesde);
        if ($fechaHasta) $partes[] = 'al_' . str_replace('-', '', $fechaHasta);
        $nombreArchivo = implode('_', $partes) . '.pdf';
        $caption       = "*Reporte Financiero — CRC S.A.C.*\n{$periodoLabel}\n{$facturas->count()} facturas · Saldo: S/ " . number_format($resumen['saldo_cobrar'], 2);
        $resultado     = $gateway->enviarDocumento($celular, $cloudUrl, $nombreArchivo, $caption);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok']
                ? "PDF enviado por WhatsApp a {$nombre} ({$celular})"
                : 'No se pudo enviar: ' . ($resultado['error'] ?? 'Error'),
        ]);
    }

    private function subirPdfACloudinary(string $pdfContent, string $estadoLabel, string $periodo): ?string
    {
        $cloudName    = env('CLOUDINARY_CLOUD_NAME', 'dq3rban3m');
        $uploadPreset = env('CLOUDINARY_UPLOAD_PRESET', 'ml_default');
        $slug         = preg_replace('/[^a-z0-9_\-]/', '_', strtolower($estadoLabel));
        $publicId     = 'reporte_' . $slug . '_' . now()->format('Ymd_His');
        try {
            $response = \Illuminate\Support\Facades\Http::attach('file', $pdfContent, $publicId . '.pdf')
                ->post("https://api.cloudinary.com/v1_1/{$cloudName}/raw/upload", [
                    'upload_preset' => $uploadPreset,
                    'folder'        => 'reportes_financieros',
                    'public_id'     => $publicId,
                    'resource_type' => 'raw',
                ]);
            if ($response->successful()) {
                return str_replace('/raw/upload/', '/raw/upload/fl_attachment/', $response->json('secure_url'));
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function enviarReporteCorreo(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $usuarioId  = $request->input('usuario_id');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $tipoReporte = $request->input('tipo_reporte', 'detallado');

        $correo = null;
        $nombre = null;
        if ($usuarioId) {
            $dest = DB::table('usuario')->where('id_usuario', $usuarioId)->first();
            if (!$dest || !$dest->correo) {
                return response()->json(['success' => false, 'error' => 'El usuario no tiene correo registrado.'], 422);
            }
            $correo = $dest->correo;
            $nombre = $dest->nombre . ' ' . $dest->apellido;
        } elseif ($idCliente) {
            $cliente = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if (!$cliente || !$cliente->correo) {
                return response()->json(['success' => false, 'error' => 'El cliente no tiene correo registrado.'], 422);
            }
            $correo = $cliente->correo;
            $nombre = $cliente->razon_social;
        } else {
            return response()->json(['success' => false, 'error' => 'Debes seleccionar un cliente o usuario destino.'], 422);
        }

        $estadosParam  = $request->input('estados', []);
        $estadosFiltro = !empty($estadosParam)
            ? (array) $estadosParam
            : ($estado ? [$estado] : ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE']);
        $estadosFiltro = $this->normalizarEstadosFiltro($estadosFiltro);

        if ($tipoReporte === 'general') {
            $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
            $htmlReporte  = $this->deudaGeneral($request)->render();
            $htmlReporte  = preg_replace('/<div class="no-print".*?<\/div>/s', '', $htmlReporte);
            $htmlReporte  = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $htmlReporte);
            $asunto       = "Reporte Deuda General — {$periodoLabel}";

            try {
                $symfonyEmail = (new Email())
                    ->from(config('mail.from.address', 'sistema@crcsac.com'))
                    ->subject($asunto)
                    ->to($correo)
                    ->html($htmlReporte);
                Mail::mailer()->send($symfonyEmail);
                return response()->json(['success' => true, 'message' => "Reporte enviado por correo a {$correo}"]);
            } catch (\Throwable $e) {
                Log::error('Error al enviar reporte general por correo: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'No se pudo enviar el correo: ' . $e->getMessage()]);
            }
        }

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja         = $f->importe_total - ($f->monto_recaudacion ?? 0);
            $f->pendiente_display = $f->monto_pendiente;
            return $f;
        });
        $facturas = $this->enriquecerRelacionCredito($facturas);

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds    = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);

        $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();
        $periodoLabel      = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel       = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);
        $clienteNombre     = strtoupper($nombre ?? 'TODOS LOS CLIENTES');
        $usuarioDestino    = null;
        $todosUsuarios     = collect([]);
        $estadosFiltroJson = json_encode($estadosFiltro);

        $resumen = [
            'total_facturas'    => $facturasParaTotales->count(),
            'pendientes'        => $facturasParaTotales->count(),
            'pagadas'           => 0,
            'vencidas'          => $facturasParaTotales->where('estado', 'VENCIDO')->count(),
            'total_bruto'       => $facturasParaTotales->sum('importe_total'),
            'total_recaudacion' => $facturasParaTotales->sum('monto_recaudacion'),
            'total_neto'        => $facturasParaTotales->sum('neto_caja'),
            'saldo_cobrar'      => $facturasParaTotales->sum('pendiente_display'),
        ];
        $dashboard = $this->buildDashboardMetrics($facturasParaTotales);

        // Para el PDF del correo usamos facturasAgrupParaTotales también
        $facturasAgrupParaTotales = $facturasParaTotales->groupBy('razon_social')->sortKeys();

        $htmlReporte = view('reportes.pdf', compact(
            'facturas', 'facturasAgrupadas', 'facturasAgrupParaTotales', 'resumen',
            'dashboard',
            'clienteNombre', 'estadoLabel', 'idCliente', 'periodoLabel',
            'usuarioDestino', 'todosUsuarios', 'estadosFiltroJson',
            'fechaDesde', 'fechaHasta', 'orphanFacturaIds'
        ))->render();
        $htmlReporte = preg_replace('/<div class="no-print".*?<\/div>/s', '', $htmlReporte);
        $asunto      = "Reporte Financiero — {$clienteNombre} — {$periodoLabel}";

        try {
            $symfonyEmail = (new Email())
                ->from(config('mail.from.address', 'sistema@crcsac.com'))
                ->subject($asunto)
                ->to($correo)
                ->html($htmlReporte);
            Mail::mailer()->send($symfonyEmail);
            return response()->json(['success' => true, 'message' => "Reporte enviado por correo a {$correo}"]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar reporte específico por correo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'No se pudo enviar el correo: ' . $e->getMessage()]);
        }
    }

    private function queryFacturas($idCliente, $estado, $fechaDesde = null, $fechaHasta = null)
    {
        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->where('f.activo', 1)
            ->select([
                'f.id_factura', 'f.serie', 'f.numero',
                'f.fecha_emision', 'f.fecha_vencimiento',
                'f.glosa', 'f.moneda', 'f.importe_total',
                'f.subtotal_gravado', 'f.monto_igv',
                'f.monto_abonado', 'f.monto_pendiente',
                'f.tipo_recaudacion', 'f.estado', 'f.forma_pago',
                'c.id_cliente', 'c.razon_social', 'c.ruc',
                DB::raw('COALESCE(rec.total_recaudacion, 0) AS monto_recaudacion'),
                DB::raw('COALESCE(rec.porcentaje, 0) AS porcentaje_recaudacion'),
                DB::raw('rec.fecha_recaudacion AS fecha_recaudacion'),
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
            return \Carbon\Carbon::parse($desde)->format('d/m/Y') . ' al ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y');
        }
        if ($desde) return 'Desde ' . \Carbon\Carbon::parse($desde)->format('d/m/Y');
        if ($hasta) return 'Hasta ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y');
        return 'Todos los períodos';
    }

    public function deudaGeneral(Request $request)
    {
        $fechaDesde   = $request->input('fecha_desde');
        $fechaHasta   = $request->input('fecha_hasta');
        $estadosParam = $request->input('estados', []);
        $estadoSimple = $request->input('estado');

        if ($estadoSimple)          $estadosFiltro = [$estadoSimple];
        elseif (!empty($estadosParam)) $estadosFiltro = (array) $estadosParam;
        else                           $estadosFiltro = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE'];
        $estadosFiltro = $this->normalizarEstadosFiltro($estadosFiltro);

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->whereIn('f.estado', $estadosFiltro)
            ->where('f.activo', 1)
            ->select([
                'f.id_factura', 'c.id_cliente', 'c.razon_social', 'c.ruc',
                'f.moneda', 'f.estado', 'f.importe_total', 'f.monto_pendiente', 'f.subtotal_gravado', 'f.monto_igv', 'f.monto_abonado',
                DB::raw('COALESCE(rec.total_recaudacion, 0) AS monto_recaudacion'),
                DB::raw('COALESCE(rec.porcentaje, 0) AS porcentaje_recaudacion'),
                'rec.fecha_recaudacion',
            ]);

        if ($fechaDesde) $query->where('f.fecha_emision', '>=', $fechaDesde);
        if ($fechaHasta) $query->where('f.fecha_emision', '<=', $fechaHasta);

        $facturas = $query->get();

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);
        $dashboard = $this->buildDashboardMetrics($facturasParaTotales);

        $clientes = [];
        foreach ($facturasParaTotales as $f) {

            $id = $f->id_cliente;
            if (!isset($clientes[$id])) {
                $clientes[$id] = [
                    'razon_social'       => $f->razon_social,
                    'ruc'                => $f->ruc,
                    'deuda_pen'          => 0,
                    'deuda_usd'          => 0,
                    'subtotal_pen'       => 0,
                    'subtotal_usd'       => 0,
                    'igv_pen'            => 0,
                    'igv_usd'            => 0,
                    // Recaudación: siempre en soles (total_recaudacion)
                    'recaudacion_pen'    => 0,  // suma de TODAS las soles (de cualquier moneda)
                    'recaud_cobrada_pen' => 0,  // soles pagados (fecha_recaudacion set)
                    // Equivalentes USD pagados (para facturas USD con fecha_recaudacion)
                    'recaudacion_usd'    => 0,
                    'abonado_pen'        => 0,
                    'abonado_usd'        => 0,
                    'pendiente_pen'      => 0,
                    'pendiente_usd'      => 0,
                    'pagadas_pen'        => 0,
                    'pagadas_usd'        => 0,
                    'facturas'           => 0,
                    'estados'            => [],
                ];
            }
            $clientes[$id]['facturas']++;
            $pendienteReal = $f->monto_pendiente;
            // Recaudación siempre en soles (total_recaudacion), sin importar la moneda de la factura
            $clientes[$id]['recaudacion_pen'] += $f->monto_recaudacion;
            if (!empty($f->fecha_recaudacion)) {
                $clientes[$id]['recaud_cobrada_pen'] += $f->monto_recaudacion;
            }
            if ($f->moneda === 'USD') {
                $clientes[$id]['deuda_usd']        += $f->importe_total;
                $clientes[$id]['subtotal_usd']     += ($f->subtotal_gravado ?? 0);
                $clientes[$id]['igv_usd']          += ($f->monto_igv ?? 0);
                $clientes[$id]['abonado_usd']      += ($f->monto_abonado ?? 0);
                $clientes[$id]['pendiente_usd']    += $pendienteReal;
                // Para USD: guardar el equivalente USD pagado en recaudacion_usd
                if (!empty($f->fecha_recaudacion) && ($f->porcentaje_recaudacion ?? 0) > 0) {
                    $clientes[$id]['recaudacion_usd'] += $f->porcentaje_recaudacion;
                }
                if ($f->estado === 'PAGADA') $clientes[$id]['pagadas_usd']++;
            } else {
                $clientes[$id]['deuda_pen']        += $f->importe_total;
                $clientes[$id]['subtotal_pen']     += ($f->subtotal_gravado ?? 0);
                $clientes[$id]['igv_pen']          += ($f->monto_igv ?? 0);
                $clientes[$id]['abonado_pen']      += ($f->monto_abonado ?? 0);
                $clientes[$id]['pendiente_pen']    += $pendienteReal;
                if ($f->estado === 'PAGADA') $clientes[$id]['pagadas_pen']++;
            }
            if (!in_array($f->estado, $clientes[$id]['estados'])) {
                $clientes[$id]['estados'][] = $f->estado;
            }
        }
        uasort($clientes, fn($a, $b) => strcmp($a['razon_social'], $b['razon_social']));

        $totalPen              = array_sum(array_column($clientes, 'deuda_pen'));
        $totalUsd              = array_sum(array_column($clientes, 'deuda_usd'));
        $totalSubtotalPen      = array_sum(array_column($clientes, 'subtotal_pen'));
        $totalSubtotalUsd      = array_sum(array_column($clientes, 'subtotal_usd'));
        $totalIgvPen           = array_sum(array_column($clientes, 'igv_pen'));
        $totalIgvUsd           = array_sum(array_column($clientes, 'igv_usd'));
        $totalRecaudacionPen   = array_sum(array_column($clientes, 'recaudacion_pen'));
        $totalRecaudCobradaPen = array_sum(array_column($clientes, 'recaud_cobrada_pen'));
        $totalRecaudacionUsd   = array_sum(array_column($clientes, 'recaudacion_usd'));
        $totalAbonadoPen       = array_sum(array_column($clientes, 'abonado_pen'));
        $totalAbonadoUsd       = array_sum(array_column($clientes, 'abonado_usd'));
        $totalPendientePen     = array_sum(array_column($clientes, 'pendiente_pen'));
        $totalPendienteUsd     = array_sum(array_column($clientes, 'pendiente_usd'));

        $estadoLabel  = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);
        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);

        $usuarioIdsParam = $request->input('usuario_ids', []);
        $usuarioIdSimple = $request->input('usuario_id');
        $usuarioIds      = $usuarioIdSimple ? [$usuarioIdSimple] : (array) $usuarioIdsParam;
        $usuariosDestino = !empty($usuarioIds)
            ? DB::table('usuario')->whereIn('id_usuario', $usuarioIds)->get()->all()
            : [];

        $todosUsuarios = DB::table('usuario')
            ->where(function ($q) { $q->whereNotNull('celular')->orWhereNotNull('correo'); })
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'celular', 'correo']);
        $estadosFiltroJson = json_encode($estadosFiltro);

        return view('reportes.deuda_general', compact(
            'clientes', 'totalPen', 'totalUsd',
            'totalSubtotalPen', 'totalSubtotalUsd',
            'totalIgvPen', 'totalIgvUsd',
            'totalRecaudacionPen', 'totalRecaudCobradaPen', 'totalRecaudacionUsd',
            'totalAbonadoPen', 'totalAbonadoUsd',
            'totalPendientePen', 'totalPendienteUsd', 'periodoLabel', 'fechaDesde', 'fechaHasta',
            'estadoLabel', 'usuariosDestino', 'todosUsuarios', 'estadosFiltroJson', 'dashboard'
        ));
    }

    public function exportExcel(Request $request)
    {
        $idCliente    = $request->input('id_cliente');
        $fechaDesde   = $request->input('fecha_desde');
        $fechaHasta   = $request->input('fecha_hasta');
        $estadosParam = $request->input('estados', []);
        $estadoSimple = $request->input('estado');
        $modo         = $request->input('modo', 'por_cliente'); // por_cliente | una_hoja | resumen

        if ($estadoSimple) {
            $estadosFiltro = [$estadoSimple];
        } elseif (!empty($estadosParam)) {
            $estadosFiltro = (array) $estadosParam;
        } else {
            $estadosFiltro = ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE'];
        }
        $estadosFiltro = $this->normalizarEstadosFiltro($estadosFiltro);

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja         = $f->importe_total - ($f->monto_recaudacion ?? 0);
            $f->pendiente_display = $f->monto_pendiente;
            return $f;
        });

        $facturas = $this->enriquecerRelacionCredito($facturas);

        $facturaIdsExcel = $facturas->pluck('id_factura')->toArray();
        $pagosMap        = DB::table('pago_factura')
            ->whereIn('id_factura', $facturaIdsExcel)
            ->where('activo', 1)
            ->orderBy('fecha_pago')->orderBy('id_pago')
            ->get(['id_factura', 'fecha_pago', 'monto_pagado', 'banco_origen', 'cuenta_pago'])
            ->groupBy('id_factura');

        $orphanFacturaIds    = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);
        $facturasAgrupadas   = $facturas->groupBy('razon_social')->sortKeys();
        $facturasAgrupParaTotales = $facturasParaTotales->groupBy('razon_social')->sortKeys();

        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel  = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);

        // ── Colores ARGB ──────────────────────────────────────────────────
        $C_COMPANY = 'FF0F172A'; // dark navy
        $C_PERIOD  = 'FF1E3A5F'; // dark blue
        $C_HEADER  = 'FF1E40AF'; // blue-800
        $C_ALT     = 'FFEFF6FF'; // blue-50
        $C_TOTAL   = 'FFFEF9C3'; // yellow-100
        $C_GROUP   = 'FFE0E7FF'; // indigo-100
        $C_WHITE   = 'FFFFFFFF';
        $stateColors = [
            'PENDIENTE'              => 'FFD97706',
            'VENCIDO'                => 'FFDC2626',
            'PAGO PARCIAL'           => 'FF1D4ED8',
            'PAGADO'                 => 'FF059669',
            'DIFERENCIA PENDIENTE'   => 'FF7C3AED',
            'POR VALIDAR DETRACCION' => 'FFB45309',
            'ANULADO'                => 'FF6B7280',
        ];

        // ── Helper: cabeceraEmpresa(sheet, row, cols, periodoLabel, estadoLabel) ──
        $cabeceraEmpresa = function ($sheet, string $empresa, string $ruc, string $periodo, string $estado, int $lastCol) use ($C_COMPANY, $C_PERIOD) {
            $LC = $this->getColumn($lastCol);
            // Fila 1 — nombre empresa
            $sheet->mergeCells("A1:{$LC}1");
            $sheet->setCellValue('A1', 'CONSORCIO RODRIGUEZ CABALLERO S.A.C.');
            $sheet->getStyle("A1:{$LC}1")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_COMPANY]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(22);
            // Fila 2 — subtítulo
            $sheet->mergeCells("A2:{$LC}2");
            $sheet->setCellValue('A2', 'Reporte Financiero de Gestión — ' . $empresa . ($ruc ? '  ·  RUC: ' . $ruc : ''));
            $sheet->getStyle("A2:{$LC}2")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_COMPANY]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(2)->setRowHeight(18);
            // Fila 3 — período
            $sheet->mergeCells("A3:{$LC}3");
            $sheet->setCellValue('A3', 'PERÍODO: ' . $periodo . '   |   ESTADO: ' . $estado . '   |   Generado: ' . now()->format('d/m/Y H:i'));
            $sheet->getStyle("A3:{$LC}3")->applyFromArray([
                'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FFDBEAFE']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_PERIOD]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getRowDimension(3)->setRowHeight(14);
        };

        // ── Helper: estilo cabecera columnas ─────────────────────────────
        $estiloHeader = [
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF93C5FD']]],
        ];

        $estiloTotal = [
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_TOTAL]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFD97706']]],
        ];

        $spreadsheet = new Spreadsheet();

        // ═════════════════════════════════════════════════════════════════
        // MODO: POR CLIENTE (multi-hoja)
        // ═════════════════════════════════════════════════════════════════
        if ($modo === 'por_cliente') {
            // ── Hoja 0: Resumen de clientes ────────────────────────────
            $sRes = $spreadsheet->getActiveSheet();
            $sRes->setTitle('Resumen Clientes');
            $cabeceraEmpresa($sRes, 'TODOS LOS CLIENTES', '', $periodoLabel, $estadoLabel, 11);

            $hdrs = ['#', 'CLIENTE', 'RUC', 'SUBTOTAL', 'IGV', 'RECAUDACIÓN', 'TOTAL', 'ABONADO', 'PENDIENTE', 'FAC.', 'ESTADOS'];
            $hRow = 5;
            foreach ($hdrs as $ci => $h) {
                $sRes->setCellValue($this->getColumn($ci + 1) . $hRow, $h);
            }
            $sRes->getStyle("A{$hRow}:K{$hRow}")->applyFromArray($estiloHeader);
            $sRes->getRowDimension($hRow)->setRowHeight(16);
            $sRes->freezePane("A" . ($hRow + 1));

            $dRow = $hRow + 1;
            $idxE = 1;
            foreach ($facturasAgrupadas as $empresa => $grp) {
                $tot = $facturasAgrupParaTotales[$empresa] ?? collect();
                $pendCalc = $tot->sum(fn($f) => $f->pendiente_display ?? $f->monto_pendiente ?? 0);
                $estados  = $grp->pluck('estado')->unique()->values()->implode(', ');
                $altFill  = $idxE % 2 === 0
                    ? ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_ALT]]
                    : ['fillType' => Fill::FILL_NONE];

                $sRes->setCellValue("A{$dRow}", $idxE++);
                $sRes->setCellValue("B{$dRow}", $empresa);
                $sRes->setCellValue("C{$dRow}", (string) ($grp->first()->ruc ?? ''));
                $sRes->setCellValue("D{$dRow}", (float) $tot->sum('subtotal_gravado'));
                $sRes->setCellValue("E{$dRow}", (float) $tot->sum('monto_igv'));
                $sRes->setCellValue("F{$dRow}", (float) $tot->sum('monto_recaudacion'));
                $sRes->setCellValue("G{$dRow}", (float) $tot->sum('importe_total'));
                $sRes->setCellValue("H{$dRow}", (float) $tot->sum('monto_abonado'));
                $sRes->setCellValue("I{$dRow}", (float) $pendCalc);
                $sRes->setCellValue("J{$dRow}", $tot->count());
                $sRes->setCellValue("K{$dRow}", $estados);
                foreach (['D','E','F','G','H','I'] as $nc) {
                    $sRes->getStyle("{$nc}{$dRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
                if ($altFill['fillType'] !== Fill::FILL_NONE) {
                    $sRes->getStyle("A{$dRow}:K{$dRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($C_ALT);
                }
                $dRow++;
            }
            // Fila totales
            $sRes->setCellValue("B{$dRow}", 'TOTALES GENERALES');
            $sRes->setCellValue("D{$dRow}", (float) $facturasParaTotales->sum('subtotal_gravado'));
            $sRes->setCellValue("E{$dRow}", (float) $facturasParaTotales->sum('monto_igv'));
            $sRes->setCellValue("F{$dRow}", (float) $facturasParaTotales->sum('monto_recaudacion'));
            $sRes->setCellValue("G{$dRow}", (float) $facturasParaTotales->sum('importe_total'));
            $sRes->setCellValue("H{$dRow}", (float) $facturasParaTotales->sum('monto_abonado'));
            $sRes->setCellValue("I{$dRow}", (float) $facturasParaTotales->sum(fn($f) => $f->pendiente_display ?? $f->monto_pendiente ?? 0));
            $sRes->setCellValue("J{$dRow}", $facturasParaTotales->count());
            $sRes->getStyle("A{$dRow}:K{$dRow}")->applyFromArray($estiloTotal);
            foreach (['D','E','F','G','H','I'] as $nc) {
                $sRes->getStyle("{$nc}{$dRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            foreach (range('A', 'K') as $col) { $sRes->getColumnDimension($col)->setAutoSize(true); }

            // ── Hojas por cliente ──────────────────────────────────────
            $shIdx = 1;
            foreach ($facturasAgrupadas as $empresa => $grpFact) {
                $ds = $spreadsheet->createSheet($shIdx++);
                $shName = preg_replace('~[\\\\/*?:\[\]]~', '-', (string) $empresa);
                $ds->setTitle(substr(trim($shName) ?: 'Cliente_' . $shIdx, 0, 31));

                $ruc1 = (string) ($grpFact->first()->ruc ?? '');
                $cabeceraEmpresa($ds, $empresa, $ruc1, $periodoLabel, $estadoLabel, 15);

                $detHdrs = ['#','EMISIÓN','VENCIMIENTO','FACTURA','GLOSA','SUBTOTAL','IGV','RECAUDACIÓN','F.RECAUD.','TOTAL','TIPO REC.','ABONADO','PAGOS (FECHA / MONTO)','PENDIENTE','ESTADO'];
                $hRow2 = 5;
                foreach ($detHdrs as $ci => $h) { $ds->setCellValue($this->getColumn($ci + 1) . $hRow2, $h); }
                $ds->getStyle("A{$hRow2}:O{$hRow2}")->applyFromArray($estiloHeader);
                $ds->getRowDimension($hRow2)->setRowHeight(18);
                $ds->freezePane("A" . ($hRow2 + 1));

                $dRow2 = $hRow2 + 1;
                $idxF  = 1;
                $totGrp = $facturasAgrupParaTotales[$empresa] ?? collect();

                foreach ($grpFact as $f) {
                    $esH = in_array((int) $f->id_factura, $orphanFacturaIds);
                    $rec = (float) ($f->monto_recaudacion ?? 0);
                    $pen = $esH ? 0 : ($f->pendiente_display ?? $f->monto_pendiente ?? 0);
                    $pagosStr = (function () use ($f, $pagosMap) {
                        $pp = $pagosMap->get($f->id_factura, collect());
                        if ($pp->isEmpty()) return '—';
                        return $pp->map(fn($p) => ($p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') : '—') . '  ' . number_format((float)$p->monto_pagado, 2))->implode("\n");
                    })();

                    $ds->setCellValue("A{$dRow2}", $idxF++);
                    $ds->setCellValue("B{$dRow2}", $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—');
                    $ds->setCellValue("C{$dRow2}", $f->fecha_vencimiento ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—');
                    $ds->setCellValue("D{$dRow2}", $f->serie . '-' . str_pad((string) $f->numero, 8, '0', STR_PAD_LEFT));
                    $ds->setCellValue("E{$dRow2}", $f->glosa ?? '—');
                    $ds->setCellValue("F{$dRow2}", !$esH && ($f->subtotal_gravado ?? 0) > 0 ? (float) $f->subtotal_gravado : null);
                    $ds->setCellValue("G{$dRow2}", !$esH && ($f->monto_igv ?? 0) > 0 ? (float) $f->monto_igv : null);
                    $ds->setCellValue("H{$dRow2}", $rec > 0 ? $rec : null);
                    $ds->setCellValue("I{$dRow2}", $f->fecha_recaudacion ? \Carbon\Carbon::parse($f->fecha_recaudacion)->format('d/m/Y') : '—');
                    $ds->setCellValue("J{$dRow2}", (float) ($f->importe_total ?? 0));
                    $ds->setCellValue("K{$dRow2}", $f->tipo_recaudacion ?? '—');
                    $ds->setCellValue("L{$dRow2}", ($f->monto_abonado ?? 0) > 0 ? (float) $f->monto_abonado : null);
                    $ds->setCellValue("M{$dRow2}", $pagosStr);
                    $ds->setCellValue("N{$dRow2}", $esH ? null : (float) $pen);
                    $ds->setCellValue("O{$dRow2}", $f->estado . ($esH ? ' [NC SIN FACTURA]' : ''));

                    // Formato numérico
                    foreach (['F','G','H','J','L','N'] as $nc) {
                        $ds->getStyle("{$nc}{$dRow2}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    $ds->getStyle("M{$dRow2}")->getAlignment()->setWrapText(true);
                    $ds->getStyle("O{$dRow2}")->getFont()->getColor()->setARGB($stateColors[$f->estado] ?? 'FF374151');

                    // Alternado
                    if ($idxF % 2 === 0) {
                        $ds->getStyle("A{$dRow2}:O{$dRow2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($C_ALT);
                    }
                    if ($esH) {
                        $ds->getStyle("A{$dRow2}:O{$dRow2}")->getFont()->setStrikethrough(true)->getColor()->setARGB('FF9CA3AF');
                    }
                    $ds->getRowDimension($dRow2)->setRowHeight(-1); // auto
                    $dRow2++;
                }

                // Totales por cliente
                $ds->setCellValue("D{$dRow2}", 'TOTALES');
                $ds->setCellValue("F{$dRow2}", (float) $totGrp->sum('subtotal_gravado'));
                $ds->setCellValue("G{$dRow2}", (float) $totGrp->sum('monto_igv'));
                $ds->setCellValue("H{$dRow2}", (float) $totGrp->sum('monto_recaudacion'));
                $ds->setCellValue("J{$dRow2}", (float) $totGrp->sum('importe_total'));
                $ds->setCellValue("L{$dRow2}", (float) $totGrp->sum('monto_abonado'));
                $ds->setCellValue("N{$dRow2}", (float) $totGrp->sum(fn($f) => $f->pendiente_display ?? $f->monto_pendiente ?? 0));
                $ds->getStyle("A{$dRow2}:O{$dRow2}")->applyFromArray($estiloTotal);
                foreach (['F','G','H','J','L','N'] as $nc) {
                    $ds->getStyle("{$nc}{$dRow2}")->getNumberFormat()->setFormatCode('#,##0.00');
                }

                // Anchos de columna
                $ds->getColumnDimension('A')->setWidth(4);
                $ds->getColumnDimension('B')->setWidth(10);
                $ds->getColumnDimension('C')->setWidth(10);
                $ds->getColumnDimension('D')->setWidth(16);
                $ds->getColumnDimension('E')->setWidth(28);
                $ds->getColumnDimension('F')->setWidth(12);
                $ds->getColumnDimension('G')->setWidth(11);
                $ds->getColumnDimension('H')->setWidth(12);
                $ds->getColumnDimension('I')->setWidth(10);
                $ds->getColumnDimension('J')->setWidth(12);
                $ds->getColumnDimension('K')->setWidth(14);
                $ds->getColumnDimension('L')->setWidth(12);
                $ds->getColumnDimension('M')->setWidth(28);
                $ds->getColumnDimension('N')->setWidth(13);
                $ds->getColumnDimension('O')->setWidth(22);
            }

        // ═════════════════════════════════════════════════════════════════
        // MODO: TODO EN UNA HOJA
        // ═════════════════════════════════════════════════════════════════
        } elseif ($modo === 'una_hoja') {
            $su = $spreadsheet->getActiveSheet();
            $su->setTitle('Facturas');
            $cabeceraEmpresa($su, 'TODOS LOS CLIENTES', '', $periodoLabel, $estadoLabel, 17);

            $uHdrs = ['#','CLIENTE','RUC','EMISIÓN','VENCIMIENTO','FACTURA','GLOSA','SUBTOTAL','IGV','RECAUDACIÓN','F.RECAUD.','TOTAL','TIPO REC.','ABONADO','PAGOS (FECHA / MONTO)','PENDIENTE','ESTADO'];
            $hRow3 = 5;
            foreach ($uHdrs as $ci => $h) { $su->setCellValue($this->getColumn($ci + 1) . $hRow3, $h); }
            $su->getStyle("A{$hRow3}:Q{$hRow3}")->applyFromArray($estiloHeader);
            $su->getRowDimension($hRow3)->setRowHeight(18);
            $su->freezePane("A" . ($hRow3 + 1));

            $dRow3 = $hRow3 + 1;
            $idxG  = 1;
            foreach ($facturasAgrupadas as $empresa => $grpFact) {
                // Fila separadora de cliente (sin merge)
                $su->setCellValue("A{$dRow3}", '  ▶  ' . strtoupper($empresa) . '  —  ' . ($grpFact->first()->ruc ?? ''));
                $su->getStyle("A{$dRow3}:Q{$dRow3}")->applyFromArray([
                    'font'  => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1E3A5F']],
                    'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_GROUP]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $su->getRowDimension($dRow3)->setRowHeight(15);
                $dRow3++;

                foreach ($grpFact as $f) {
                    $esH = in_array((int) $f->id_factura, $orphanFacturaIds);
                    $rec = (float) ($f->monto_recaudacion ?? 0);
                    $pen = $esH ? 0 : ($f->pendiente_display ?? $f->monto_pendiente ?? 0);
                    $pagosStr = (function () use ($f, $pagosMap) {
                        $pp = $pagosMap->get($f->id_factura, collect());
                        if ($pp->isEmpty()) return '—';
                        return $pp->map(fn($p) => ($p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') : '—') . '  ' . number_format((float)$p->monto_pagado, 2))->implode("\n");
                    })();

                    $su->setCellValue("A{$dRow3}", $idxG++);
                    $su->setCellValue("B{$dRow3}", $empresa);
                    $su->setCellValue("C{$dRow3}", (string) ($f->ruc ?? ''));
                    $su->setCellValue("D{$dRow3}", $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—');
                    $su->setCellValue("E{$dRow3}", $f->fecha_vencimiento ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—');
                    $su->setCellValue("F{$dRow3}", $f->serie . '-' . str_pad((string) $f->numero, 8, '0', STR_PAD_LEFT));
                    $su->setCellValue("G{$dRow3}", $f->glosa ?? '—');
                    $su->setCellValue("H{$dRow3}", !$esH && ($f->subtotal_gravado ?? 0) > 0 ? (float) $f->subtotal_gravado : null);
                    $su->setCellValue("I{$dRow3}", !$esH && ($f->monto_igv ?? 0) > 0 ? (float) $f->monto_igv : null);
                    $su->setCellValue("J{$dRow3}", $rec > 0 ? $rec : null);
                    $su->setCellValue("K{$dRow3}", $f->fecha_recaudacion ? \Carbon\Carbon::parse($f->fecha_recaudacion)->format('d/m/Y') : '—');
                    $su->setCellValue("L{$dRow3}", (float) ($f->importe_total ?? 0));
                    $su->setCellValue("M{$dRow3}", $f->tipo_recaudacion ?? '—');
                    $su->setCellValue("N{$dRow3}", ($f->monto_abonado ?? 0) > 0 ? (float) $f->monto_abonado : null);
                    $su->setCellValue("O{$dRow3}", $pagosStr);
                    $su->setCellValue("P{$dRow3}", $esH ? null : (float) $pen);
                    $su->setCellValue("Q{$dRow3}", $f->estado . ($esH ? ' [NC SIN FACTURA]' : ''));

                    foreach (['H','I','J','L','N','P'] as $nc) {
                        $su->getStyle("{$nc}{$dRow3}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    $su->getStyle("O{$dRow3}")->getAlignment()->setWrapText(true);
                    $su->getStyle("Q{$dRow3}")->getFont()->getColor()->setARGB($stateColors[$f->estado] ?? 'FF374151');
                    if ($idxG % 2 === 0) {
                        $su->getStyle("A{$dRow3}:Q{$dRow3}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($C_ALT);
                    }
                    if ($esH) {
                        $su->getStyle("A{$dRow3}:Q{$dRow3}")->getFont()->setStrikethrough(true)->getColor()->setARGB('FF9CA3AF');
                    }
                    $dRow3++;
                }
            }
            // Totales globales
            $su->setCellValue("F{$dRow3}", 'TOTALES GENERALES');
            $su->setCellValue("H{$dRow3}", (float) $facturasParaTotales->sum('subtotal_gravado'));
            $su->setCellValue("I{$dRow3}", (float) $facturasParaTotales->sum('monto_igv'));
            $su->setCellValue("J{$dRow3}", (float) $facturasParaTotales->sum('monto_recaudacion'));
            $su->setCellValue("L{$dRow3}", (float) $facturasParaTotales->sum('importe_total'));
            $su->setCellValue("N{$dRow3}", (float) $facturasParaTotales->sum('monto_abonado'));
            $su->setCellValue("P{$dRow3}", (float) $facturasParaTotales->sum(fn($f) => $f->pendiente_display ?? $f->monto_pendiente ?? 0));
            $su->getStyle("A{$dRow3}:Q{$dRow3}")->applyFromArray($estiloTotal);
            foreach (['H','I','J','L','N','P'] as $nc) {
                $su->getStyle("{$nc}{$dRow3}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Anchos
            $uWidths = [4, 28, 12, 10, 10, 16, 28, 12, 11, 12, 10, 13, 14, 12, 28, 13, 22];
            foreach ($uWidths as $ci => $w) { $su->getColumnDimension($this->getColumn($ci + 1))->setWidth($w); }

        // ═════════════════════════════════════════════════════════════════
        // MODO: RESUMEN CLIENTES
        // ═════════════════════════════════════════════════════════════════
        } else {
            $sr = $spreadsheet->getActiveSheet();
            $sr->setTitle('Resumen Clientes');
            $cabeceraEmpresa($sr, 'RESUMEN POR CLIENTE', '', $periodoLabel, $estadoLabel, 11);

            $rHdrs = ['#', 'CLIENTE', 'RUC', 'N° FACTURAS', 'SUBTOTAL', 'IGV', 'RECAUDACIÓN', 'TOTAL', 'ABONADO', 'PENDIENTE', 'ESTADOS'];
            $hRow4 = 5;
            foreach ($rHdrs as $ci => $h) { $sr->setCellValue($this->getColumn($ci + 1) . $hRow4, $h); }
            $sr->getStyle("A{$hRow4}:K{$hRow4}")->applyFromArray($estiloHeader);
            $sr->getRowDimension($hRow4)->setRowHeight(18);
            $sr->freezePane("A" . ($hRow4 + 1));

            $dRow4 = $hRow4 + 1;
            $idxR  = 1;
            foreach ($facturasAgrupadas as $empresa => $grp) {
                $tot     = $facturasAgrupParaTotales[$empresa] ?? collect();
                $pendR   = (float) $tot->sum(fn($f) => $f->pendiente_display ?? $f->monto_pendiente ?? 0);
                $estados = $grp->pluck('estado')->unique()->values()->implode(', ');

                $sr->setCellValue("A{$dRow4}", $idxR++);
                $sr->setCellValue("B{$dRow4}", $empresa);
                $sr->setCellValue("C{$dRow4}", (string) ($grp->first()->ruc ?? ''));
                $sr->setCellValue("D{$dRow4}", $tot->count());
                $sr->setCellValue("E{$dRow4}", (float) $tot->sum('subtotal_gravado'));
                $sr->setCellValue("F{$dRow4}", (float) $tot->sum('monto_igv'));
                $sr->setCellValue("G{$dRow4}", (float) $tot->sum('monto_recaudacion'));
                $sr->setCellValue("H{$dRow4}", (float) $tot->sum('importe_total'));
                $sr->setCellValue("I{$dRow4}", (float) $tot->sum('monto_abonado'));
                $sr->setCellValue("J{$dRow4}", $pendR);
                $sr->setCellValue("K{$dRow4}", $estados);

                foreach (['E','F','G','H','I','J'] as $nc) {
                    $sr->getStyle("{$nc}{$dRow4}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
                if ($idxR % 2 === 0) {
                    $sr->getStyle("A{$dRow4}:K{$dRow4}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($C_ALT);
                }
                // Color estado en K
                $estadosPrimero = $grp->pluck('estado')->unique()->first() ?? '';
                $sr->getStyle("K{$dRow4}")->getFont()->getColor()->setARGB($stateColors[$estadosPrimero] ?? 'FF374151');
                $dRow4++;
            }
            // Totales
            $sr->setCellValue("B{$dRow4}", 'TOTALES');
            $sr->setCellValue("D{$dRow4}", $facturasParaTotales->count());
            $sr->setCellValue("E{$dRow4}", (float) $facturasParaTotales->sum('subtotal_gravado'));
            $sr->setCellValue("F{$dRow4}", (float) $facturasParaTotales->sum('monto_igv'));
            $sr->setCellValue("G{$dRow4}", (float) $facturasParaTotales->sum('monto_recaudacion'));
            $sr->setCellValue("H{$dRow4}", (float) $facturasParaTotales->sum('importe_total'));
            $sr->setCellValue("I{$dRow4}", (float) $facturasParaTotales->sum('monto_abonado'));
            $sr->setCellValue("J{$dRow4}", (float) $facturasParaTotales->sum(fn($f) => $f->pendiente_display ?? $f->monto_pendiente ?? 0));
            $sr->getStyle("A{$dRow4}:K{$dRow4}")->applyFromArray($estiloTotal);
            foreach (['E','F','G','H','I','J'] as $nc) {
                $sr->getStyle("{$nc}{$dRow4}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            // Anchos
            $rWidths = [4, 35, 14, 10, 14, 12, 14, 14, 14, 14, 35];
            foreach ($rWidths as $ci => $w) { $sr->getColumnDimension($this->getColumn($ci + 1))->setWidth($w); }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $empresaArchivo = 'TODOS';
        if ($idCliente) {
            $empresaArchivo = (string) (DB::table('cliente')->where('id_cliente', $idCliente)->value('razon_social') ?? 'TODOS');
        }

        $modoSufijo = ['por_cliente' => 'POR-CLIENTE', 'una_hoja' => 'UNA-HOJA', 'resumen' => 'RESUMEN'][$modo] ?? 'REPORTE';
        $filename   = $this->buildVariacionesFilename($empresaArchivo . '-' . $modoSufijo, $fechaDesde, $fechaHasta, 'xlsx');
        $writer     = new Xlsx($spreadsheet);
        $tempFile   = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function buildVariacionesFilename(string $empresa, ?string $fechaDesde, ?string $fechaHasta, string $extension = 'xlsx'): string
    {
        $empresaCode = $this->empresaCode($empresa);
        $periodo = $this->buildPeriodoArchivo($fechaDesde, $fechaHasta);

        $base = "V.{$empresaCode}-CRC {$periodo}";

        return $this->sanitizeFilename($base) . '.' . strtolower($extension);
    }

    private function buildPeriodoArchivo(?string $fechaDesde, ?string $fechaHasta): string
    {
        if ($fechaDesde && $fechaHasta) {
            return \Carbon\Carbon::parse($fechaDesde)->format('d.m.y')
                . ' AL '
                . \Carbon\Carbon::parse($fechaHasta)->format('d.m.y');
        }

        if ($fechaDesde) {
            return \Carbon\Carbon::parse($fechaDesde)->format('d.m.y');
        }

        if ($fechaHasta) {
            return \Carbon\Carbon::parse($fechaHasta)->format('d.m.y');
        }

        return now()->format('d.m.y');
    }

    private function empresaCode(string $razonSocial): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9\s]/', ' ', $razonSocial));
        $tokens = array_values(array_filter(preg_split('/\s+/', $clean)));
        $stopWords = ['SAC', 'SA', 'EIRL', 'SRL', 'CONSORCIO', 'EMPRESA', 'EMPRESAS', 'DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y'];

        foreach ($tokens as $token) {
            if (!in_array($token, $stopWords, true)) {
                return substr($token, 0, 12);
            }
        }

        return substr((string) ($tokens[0] ?? 'EMPRESA'), 0, 12);
    }

    private function sanitizeFilename(string $name): string
    {
        $name = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));

        return $name !== '' ? $name : 'archivo';
    }

    private function getColumn(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr($number % 26 + 65) . $letter;
            $number = intdiv($number, 26);
        }
        return $letter;
    }
}
