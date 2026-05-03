<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use App\Services\WhatsAppGatewayService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            ->get();

        $orphanIds = [];
        foreach ($creditos as $credito) {
            $existe = DB::table('factura')
                ->where('serie',  $credito->serie_doc_modificado)
                ->where('numero', $credito->numero_doc_modificado)
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
            ->get(['id_factura', 'serie_doc_modificado', 'numero_doc_modificado']);

        $creditosInversosQuery = DB::table('credito')
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
            $f->pendiente_display = $f->estado === 'DIFERENCIA PENDIENTE'
                ? max(0, $f->importe_total - ($f->monto_recaudacion ?? 0))
                : $f->monto_pendiente;
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
            $f->pendiente_display = $f->estado === 'DIFERENCIA PENDIENTE'
                ? $f->importe_total
                : $f->monto_pendiente;
            return $f;
        });
        $facturas = $this->enriquecerRelacionCredito($facturas);

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
            'orphanFacturaIds'   // ← nuevo: para la vista blade
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
            $f->pendiente_display = $f->estado === 'DIFERENCIA PENDIENTE'
                ? $f->importe_total
                : $f->monto_pendiente;
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
                Mail::send([], [], function ($mail) use ($correo, $asunto, $htmlReporte) {
                    $mail->to($correo)->subject($asunto)->html($htmlReporte);
                });
                return response()->json(['success' => true, 'message' => "Reporte enviado por correo a {$correo}"]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'No se pudo enviar el correo: ' . $e->getMessage()]);
            }
        }

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja         = $f->importe_total - ($f->monto_recaudacion ?? 0);
            $f->pendiente_display = $f->estado === 'DIFERENCIA PENDIENTE'
                ? $f->importe_total
                : $f->monto_pendiente;
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
            Mail::send([], [], function ($mail) use ($correo, $asunto, $htmlReporte) {
                $mail->to($correo)->subject($asunto)->html($htmlReporte);
            });
            return response()->json(['success' => true, 'message' => "Reporte enviado por correo a {$correo}"]);
        } catch (\Exception $e) {
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
                'f.fecha_emision', 'f.fecha_vencimiento', 'f.fecha_abono',
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
            ->where('f.activo', 1)
            ->whereIn('f.estado', $estadosFiltro)
            ->select([
                'f.id_factura', 'c.id_cliente', 'c.razon_social', 'c.ruc',
                'f.moneda', 'f.estado', 'f.importe_total', 'f.monto_pendiente', 'f.subtotal_gravado', 'f.monto_igv',
                DB::raw('COALESCE(rec.total_recaudacion, 0) AS monto_recaudacion'),
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
                    'razon_social'   => $f->razon_social,
                    'ruc'            => $f->ruc,
                    'deuda_pen'      => 0,
                    'deuda_usd'      => 0,
                    'subtotal_pen'   => 0,
                    'subtotal_usd'   => 0,
                    'igv_pen'        => 0,
                    'igv_usd'        => 0,
                    'recaudacion_pen'=> 0,
                    'recaudacion_usd'=> 0,
                    'pendiente_pen'  => 0,
                    'pendiente_usd'  => 0,
                    'facturas'       => 0,
                    'estados'        => [],
                ];
            }
            $clientes[$id]['facturas']++;
            $pendienteReal = $f->monto_pendiente;
            if ($f->moneda === 'USD') {
                $clientes[$id]['deuda_usd']        += $f->importe_total;
                $clientes[$id]['subtotal_usd']     += ($f->subtotal_gravado ?? 0);
                $clientes[$id]['igv_usd']          += ($f->monto_igv ?? 0);
                $clientes[$id]['recaudacion_usd']  += $f->monto_recaudacion;
                $clientes[$id]['pendiente_usd']    += $pendienteReal;
            } else {
                $clientes[$id]['deuda_pen']        += $f->importe_total;
                $clientes[$id]['subtotal_pen']     += ($f->subtotal_gravado ?? 0);
                $clientes[$id]['igv_pen']          += ($f->monto_igv ?? 0);
                $clientes[$id]['recaudacion_pen']  += $f->monto_recaudacion;
                $clientes[$id]['pendiente_pen']    += $pendienteReal;
            }
            if (!in_array($f->estado, $clientes[$id]['estados'])) {
                $clientes[$id]['estados'][] = $f->estado;
            }
        }
        uasort($clientes, fn($a, $b) => $b['pendiente_pen'] <=> $a['pendiente_pen']);

        $totalPen            = array_sum(array_column($clientes, 'deuda_pen'));
        $totalUsd            = array_sum(array_column($clientes, 'deuda_usd'));
        $totalSubtotalPen    = array_sum(array_column($clientes, 'subtotal_pen'));
        $totalSubtotalUsd    = array_sum(array_column($clientes, 'subtotal_usd'));
        $totalIgvPen         = array_sum(array_column($clientes, 'igv_pen'));
        $totalIgvUsd         = array_sum(array_column($clientes, 'igv_usd'));
        $totalRecaudacionPen = array_sum(array_column($clientes, 'recaudacion_pen'));
        $totalRecaudacionUsd = array_sum(array_column($clientes, 'recaudacion_usd'));
        $totalPendientePen   = array_sum(array_column($clientes, 'pendiente_pen'));
        $totalPendienteUsd   = array_sum(array_column($clientes, 'pendiente_usd'));

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
            'totalRecaudacionPen', 'totalRecaudacionUsd',
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
            $f->pendiente_display = $f->estado === 'DIFERENCIA PENDIENTE'
                ? max(0, ($f->importe_total ?? 0) - ($f->monto_recaudacion ?? 0))
                : $f->monto_pendiente;
            return $f;
        });

        $facturas = $this->enriquecerRelacionCredito($facturas);

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds    = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);

        // Agrupaciones
        $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();
        $facturasAgrupParaTotales = $facturasParaTotales->groupBy('razon_social')->sortKeys();

        $spreadsheet = new Spreadsheet();

        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel  = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);

        // ── Hoja 0: TODAS LAS FACTURAS (unificada con estilo) ─────────────
        $unifiedSheet = $spreadsheet->getActiveSheet();
        $unifiedSheet->setTitle('TODAS LAS FACTURAS');
        $this->buildUnifiedSheet($unifiedSheet, $facturas, $facturasParaTotales, $orphanFacturaIds, $periodoLabel, $estadoLabel);

        // ── Hoja 1: Resumen general ───────────────────────────────────────
        $sheet = $spreadsheet->createSheet(1);
        $sheet->setTitle('Resumen');

        // ── Hoja resumen general (con estilos) ────────────────────────────
        $FILL_SOLID  = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
        $BORDER_THIN = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $BORDER_MED  = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM;
        $H_CTR = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $H_RT  = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
        $V_CTR = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;
        $estadoColorsXl = [
            'VENCIDO'              => ['FEE2E2','991B1B'],
            'PAGO PARCIAL'         => ['E0E7FF','3730A3'],
            'DIFERENCIA PENDIENTE' => ['FCE7F3','9D174D'],
            'PAGADA'               => ['D1FAE5','065F46'],
            'PENDIENTE'            => ['FEF3C7','92400E'],
        ];

        foreach (['A'=>5,'B'=>34,'C'=>14,'D'=>13,'E'=>11,'F'=>14,'G'=>13,'H'=>13,'I'=>13,'J'=>7,'K'=>28] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        // Row 1: title block
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'CONSORCIO RODRIGUEZ CABALLERO S.A.C. — REPORTE FINANCIERO DE GESTIÓN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('0F172A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Row 2: period / state
        $sheet->mergeCells('A2:K2');
        $sheet->setCellValue('A2', 'PERÍODO: ' . $periodoLabel . '   |   ESTADO: ' . $estadoLabel . '   |   Generado: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('94A3B8');
        $sheet->getStyle('A2')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('0F172A');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR);
        $sheet->getRowDimension(2)->setRowHeight(16);

        $sheet->getRowDimension(3)->setRowHeight(6);

        // Row 4: column headers
        $headersResumen = ['#','EMPRESA','RUC','SUB TOTAL','IGV','RECAUDACIÓN','TOTAL','ABONADO','PENDIENTE','FACT.','ESTADOS'];
        foreach ($headersResumen as $ci => $hdr) {
            $sheet->setCellValue($this->getColumn($ci + 1) . '4', $hdr);
        }
        $sheet->getStyle('A4:K4')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:K4')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $sheet->getStyle('A4:K4')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR)->setWrapText(true);
        $sheet->getStyle('A4:K4')->getBorders()->getAllBorders()->setBorderStyle($BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $row = 5;
        $idxEmpresa = 1;
        foreach ($facturasAgrupadas as $empresa => $facturasPorEmpresa) {
            $facturasTot = $facturasAgrupParaTotales[$empresa] ?? collect();

            $totSub = (float) $facturasTot->sum('subtotal_gravado');
            $totIgv = (float) $facturasTot->sum('monto_igv');
            $totRec = (float) $facturasTot->sum('monto_recaudacion');
            $totAll = (float) $facturasTot->sum('importe_total');
            $totAbo = (float) $facturasTot->sum('monto_abonado');
            $totPen = (float) $facturasTot->sum(function ($f) {
                return $f->estado === 'DIFERENCIA PENDIENTE'
                    ? max(0, ($f->importe_total ?? 0) - ($f->monto_recaudacion ?? 0))
                    : ($f->pendiente_display ?? $f->monto_pendiente ?? 0);
            });

            $ruc    = (string) ($facturasPorEmpresa->first()->ruc ?? '');
            $estados = $facturasPorEmpresa->pluck('estado')->unique()->values()->implode(', ');
            $rowBg  = ($idxEmpresa % 2 === 0) ? 'F1F5F9' : 'FFFFFF';

            $sheet->setCellValue('A' . $row, $idxEmpresa);
            $sheet->setCellValue('B' . $row, $empresa);
            $sheet->setCellValue('C' . $row, $ruc);
            $sheet->setCellValue('D' . $row, $totSub);
            $sheet->setCellValue('E' . $row, $totIgv);
            $sheet->setCellValue('F' . $row, $totRec);
            $sheet->setCellValue('G' . $row, $totAll);
            $sheet->setCellValue('H' . $row, $totAbo);
            $sheet->setCellValue('I' . $row, $totPen);
            $sheet->setCellValue('J' . $row, $facturasTot->count());
            $sheet->setCellValue('K' . $row, $estados);

            foreach (['D','E','F','G','H','I'] as $nc) {
                $sheet->getStyle($nc . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($nc . $row)->getAlignment()->setHorizontal($H_RT);
            }
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal($H_CTR);
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal($H_CTR);
            $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB($rowBg);
            $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle($BORDER_THIN);

            // Estado cell: pick worst status color
            $estadosList = $facturasPorEmpresa->pluck('estado')->unique()->values()->toArray();
            foreach (['VENCIDO','DIFERENCIA PENDIENTE','PAGO PARCIAL','PENDIENTE','PAGADA'] as $ep) {
                if (in_array($ep, $estadosList) && isset($estadoColorsXl[$ep])) {
                    [$eBg, $eFg] = $estadoColorsXl[$ep];
                    $sheet->getStyle('K' . $row)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB($eBg);
                    $sheet->getStyle('K' . $row)->getFont()->setBold(true)->getColor()->setRGB($eFg);
                    break;
                }
            }
            $sheet->getRowDimension($row)->setRowHeight(15);
            $idxEmpresa++;
            $row++;
        }

        // Total row
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('A' . $row, 'TOTALES GENERALES — ' . $facturasParaTotales->count() . ' facturas');
        $sheet->setCellValue('D' . $row, (float) $facturasParaTotales->sum('subtotal_gravado'));
        $sheet->setCellValue('E' . $row, (float) $facturasParaTotales->sum('monto_igv'));
        $sheet->setCellValue('F' . $row, (float) $facturasParaTotales->sum('monto_recaudacion'));
        $sheet->setCellValue('G' . $row, (float) $facturasParaTotales->sum('importe_total'));
        $sheet->setCellValue('H' . $row, (float) $facturasParaTotales->sum('monto_abonado'));
        $sheet->setCellValue('I' . $row, (float) $facturasParaTotales->sum(function ($f) {
            return $f->estado === 'DIFERENCIA PENDIENTE'
                ? max(0, ($f->importe_total ?? 0) - ($f->monto_recaudacion ?? 0))
                : ($f->pendiente_display ?? $f->monto_pendiente ?? 0);
        }));
        $sheet->setCellValue('J' . $row, $facturasParaTotales->count());
        foreach (['D','E','F','G','H','I'] as $nc) {
            $sheet->getStyle($nc . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle($nc . $row)->getAlignment()->setHorizontal($H_RT);
        }
        $sheet->getStyle('A' . $row . ':K' . $row)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle($BORDER_MED);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $sheet->freezePane('A5');

        // ── Hojas por cliente/empresa con detalle de facturas (con estilos) ─
        $empresaSheetIndex = 2;
        foreach ($facturasAgrupadas as $empresa => $facturasPorEmpresa) {
            $detalleSheet = $spreadsheet->createSheet($empresaSheetIndex++);
            $sheetName = preg_replace('~[\\\\/*?:\[\]]~', '-', (string) $empresa);
            $sheetName = trim((string) $sheetName);
            if ($sheetName === '') { $sheetName = 'Cliente_' . $empresaSheetIndex; }
            $detalleSheet->setTitle(substr($sheetName, 0, 31));

            foreach (['A'=>5,'B'=>11,'C'=>13,'D'=>17,'E'=>38,'F'=>13,'G'=>11,'H'=>13,'I'=>13,'J'=>13,'K'=>15,'L'=>13,'M'=>11,'N'=>13,'O'=>22] as $c => $w) {
                $detalleSheet->getColumnDimension($c)->setWidth($w);
            }

            // Row 1: title block
            $detalleSheet->mergeCells('A1:O1');
            $detalleSheet->setCellValue('A1', 'CONSORCIO RODRIGUEZ CABALLERO S.A.C. — DETALLE DE FACTURAS POR EMPRESA');
            $detalleSheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
            $detalleSheet->getStyle('A1')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('0F172A');
            $detalleSheet->getStyle('A1')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR);
            $detalleSheet->getRowDimension(1)->setRowHeight(24);

            // Row 2: period / state
            $detalleSheet->mergeCells('A2:O2');
            $detalleSheet->setCellValue('A2', 'PERÍODO: ' . $periodoLabel . '   |   ESTADO: ' . $estadoLabel . '   |   Generado: ' . now()->format('d/m/Y H:i'));
            $detalleSheet->getStyle('A2')->getFont()->setSize(8)->getColor()->setRGB('94A3B8');
            $detalleSheet->getStyle('A2')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('0F172A');
            $detalleSheet->getStyle('A2')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR);
            $detalleSheet->getRowDimension(2)->setRowHeight(14);

            // Row 3: company name
            $detalleSheet->mergeCells('A3:O3');
            $detalleSheet->setCellValue('A3', strtoupper($empresa) . '  ·  RUC: ' . ($facturasPorEmpresa->first()->ruc ?? ''));
            $detalleSheet->getStyle('A3')->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E3A5F');
            $detalleSheet->getStyle('A3')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
            $detalleSheet->getStyle('A3')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR);
            $detalleSheet->getRowDimension(3)->setRowHeight(18);

            $detalleSheet->getRowDimension(4)->setRowHeight(6);

            // Row 5: column headers
            $headersDetalle = ['#','EMISIÓN','F.VENCIM.','FACTURA','GLOSA','SUBTOTAL','IGV','RECAUDACIÓN','F.RECAUDA.','TOTAL','TIPO RECAUD.','ABONADO','F.ABONO','PENDIENTE','ESTADO'];
            foreach ($headersDetalle as $ci => $hdr) {
                $detalleSheet->setCellValue($this->getColumn($ci + 1) . '5', $hdr);
            }
            $detalleSheet->getStyle('A5:O5')->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('FFFFFF');
            $detalleSheet->getStyle('A5:O5')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
            $detalleSheet->getStyle('A5:O5')->getAlignment()->setHorizontal($H_CTR)->setVertical($V_CTR)->setWrapText(true);
            $detalleSheet->getStyle('A5:O5')->getBorders()->getAllBorders()->setBorderStyle($BORDER_THIN);
            $detalleSheet->getRowDimension(5)->setRowHeight(28);

            // Data rows starting at 6
            $rowDetalle = 6;
            $dIdx = 1;
            foreach ($facturasPorEmpresa as $f) {
                $esHuerfana  = in_array((int) $f->id_factura, $orphanFacturaIds);
                $recaudacion = (float) ($f->monto_recaudacion ?? 0);
                $pendiente   = $esHuerfana ? 0.0
                    : ($f->estado === 'DIFERENCIA PENDIENTE'
                        ? max(0.0, (float)($f->importe_total ?? 0) - $recaudacion)
                        : (float)($f->pendiente_display ?? $f->monto_pendiente ?? 0));
                $estadoExcel = $f->estado . ($esHuerfana ? ' [NC]' : '');
                $rowBg = ($dIdx % 2 === 0) ? 'F1F5F9' : 'FFFFFF';

                $detalleSheet->setCellValue('A' . $rowDetalle, $dIdx);
                $detalleSheet->setCellValue('B' . $rowDetalle, $f->fecha_emision     ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y')     : '—');
                $detalleSheet->setCellValue('C' . $rowDetalle, $f->fecha_vencimiento ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—');
                $detalleSheet->setCellValue('D' . $rowDetalle, ($f->serie ?? '') . '-' . str_pad((string)($f->numero ?? ''), 8, '0', STR_PAD_LEFT));
                $detalleSheet->setCellValue('E' . $rowDetalle, $f->glosa ?? '—');
                $detalleSheet->setCellValue('F' . $rowDetalle, !$esHuerfana ? (float)($f->subtotal_gravado ?? 0) : 0.0);
                $detalleSheet->setCellValue('G' . $rowDetalle, !$esHuerfana ? (float)($f->monto_igv ?? 0) : 0.0);
                $detalleSheet->setCellValue('H' . $rowDetalle, $recaudacion);
                $detalleSheet->setCellValue('I' . $rowDetalle, !empty($f->fecha_recaudacion) ? \Carbon\Carbon::parse($f->fecha_recaudacion)->format('d/m/Y') : '—');
                $detalleSheet->setCellValue('J' . $rowDetalle, !$esHuerfana ? (float)($f->importe_total ?? 0) : 0.0);
                $detalleSheet->setCellValue('K' . $rowDetalle, $f->tipo_recaudacion ?? '—');
                $detalleSheet->setCellValue('L' . $rowDetalle, !$esHuerfana ? (float)($f->monto_abonado ?? 0) : 0.0);
                $detalleSheet->setCellValue('M' . $rowDetalle, !empty($f->fecha_abono) ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—');
                $detalleSheet->setCellValue('N' . $rowDetalle, $esHuerfana ? 0.0 : (float)$pendiente);
                $detalleSheet->setCellValue('O' . $rowDetalle, $estadoExcel);

                foreach (['F','G','H','J','L','N'] as $nc) {
                    $detalleSheet->getStyle($nc . $rowDetalle)->getNumberFormat()->setFormatCode('#,##0.00');
                    $detalleSheet->getStyle($nc . $rowDetalle)->getAlignment()->setHorizontal($H_RT);
                }
                $detalleSheet->getStyle('A' . $rowDetalle)->getAlignment()->setHorizontal($H_CTR);
                $detalleSheet->getStyle('A' . $rowDetalle . ':O' . $rowDetalle)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB($rowBg);
                $detalleSheet->getStyle('A' . $rowDetalle . ':O' . $rowDetalle)->getBorders()->getAllBorders()->setBorderStyle($BORDER_THIN);

                // Estado color
                [$eBg, $eFg] = $estadoColorsXl[$f->estado ?? 'PENDIENTE'] ?? ['FEF3C7','92400E'];
                $detalleSheet->getStyle('O' . $rowDetalle)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB($eBg);
                $detalleSheet->getStyle('O' . $rowDetalle)->getFont()->setBold(true)->getColor()->setRGB($eFg);

                if ($esHuerfana) {
                    $detalleSheet->getStyle('A' . $rowDetalle . ':N' . $rowDetalle)->getFont()->setStrikethrough(true)->getColor()->setRGB('9CA3AF');
                }
                $detalleSheet->getRowDimension($rowDetalle)->setRowHeight(15);
                $rowDetalle++;
                $dIdx++;
            }

            // Total row
            $detalleTot = $facturasAgrupParaTotales[$empresa] ?? collect();
            $detalleSheet->mergeCells('A' . $rowDetalle . ':E' . $rowDetalle);
            $detalleSheet->setCellValue('A' . $rowDetalle, 'TOTALES — ' . $detalleTot->count() . ' facturas');
            $detalleSheet->setCellValue('F' . $rowDetalle, (float)$detalleTot->sum('subtotal_gravado'));
            $detalleSheet->setCellValue('G' . $rowDetalle, (float)$detalleTot->sum('monto_igv'));
            $detalleSheet->setCellValue('H' . $rowDetalle, (float)$detalleTot->sum('monto_recaudacion'));
            $detalleSheet->setCellValue('J' . $rowDetalle, (float)$detalleTot->sum('importe_total'));
            $detalleSheet->setCellValue('L' . $rowDetalle, (float)$detalleTot->sum('monto_abonado'));
            $detalleSheet->setCellValue('N' . $rowDetalle, (float)$detalleTot->sum(function ($f) {
                return $f->estado === 'DIFERENCIA PENDIENTE'
                    ? max(0, ($f->importe_total ?? 0) - ($f->monto_recaudacion ?? 0))
                    : ($f->pendiente_display ?? $f->monto_pendiente ?? 0);
            }));
            foreach (['F','G','H','J','L','N'] as $nc) {
                $detalleSheet->getStyle($nc . $rowDetalle)->getNumberFormat()->setFormatCode('#,##0.00');
                $detalleSheet->getStyle($nc . $rowDetalle)->getAlignment()->setHorizontal($H_RT);
            }
            $detalleSheet->getStyle('A' . $rowDetalle . ':O' . $rowDetalle)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
            $detalleSheet->getStyle('A' . $rowDetalle . ':O' . $rowDetalle)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('1E293B');
            $detalleSheet->getStyle('A' . $rowDetalle . ':O' . $rowDetalle)->getBorders()->getAllBorders()->setBorderStyle($BORDER_MED);
            $detalleSheet->getRowDimension($rowDetalle)->setRowHeight(20);
            $detalleSheet->freezePane('A6');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Reporte_Financiero_Por_Empresa_' . now()->format('YmdHi') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    // ── Hoja unificada con todas las facturas y estilos de color ─────────
    private function buildUnifiedSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        \Illuminate\Support\Collection $facturas,
        \Illuminate\Support\Collection $facturasParaTotales,
        array $orphanFacturaIds,
        string $periodoLabel,
        string $estadoLabel
    ): void {
        $FILL_SOLID    = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
        $BORDER_THIN   = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $BORDER_MEDIUM = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM;
        $H_CENTER      = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $H_RIGHT       = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
        $V_CENTER      = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;

        // Column widths (A–Q = 17 columns)
        $colWidths = [
            'A'=>5,  'B'=>30, 'C'=>14, 'D'=>16, 'E'=>12, 'F'=>13,
            'G'=>38, 'H'=>13, 'I'=>11, 'J'=>13, 'K'=>14, 'L'=>14,
            'M'=>13, 'N'=>13, 'O'=>12, 'P'=>13, 'Q'=>22,
        ];
        foreach ($colWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Row 1: Company / report title ────────────────────────────────
        $sheet->mergeCells('A1:Q1');
        $sheet->setCellValue('A1', 'CONSORCIO RODRIGUEZ CABALLERO S.A.C. — REPORTE FINANCIERO DE GESTIÓN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('0F172A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal($H_CENTER)->setVertical($V_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Row 2: Period / state / generation date ───────────────────────
        $sheet->mergeCells('A2:Q2');
        $sheet->setCellValue('A2', 'PERÍODO: ' . $periodoLabel . '   |   ESTADO: ' . $estadoLabel . '   |   Generado: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('94A3B8');
        $sheet->getStyle('A2')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('0F172A');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal($H_CENTER)->setVertical($V_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Row 3: Column headers (colored) ──────────────────────────────
        $cols    = range('A', 'Q');
        $headers = [
            '#', 'EMPRESA', 'RUC', 'FACTURA', 'F. EMISIÓN', 'F. VENCIMIENTO', 'GLOSA',
            'SUBTOTAL', 'IGV', 'RECAUDACIÓN', 'F. RECAUDACIÓN', 'TIPO RECAUD.',
            'TOTAL', 'ABONADO', 'F. ABONO', 'PENDIENTE', 'ESTADO',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '3', $h);
        }
        $sheet->getStyle('A3:Q3')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A3:Q3')->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $sheet->getStyle('A3:Q3')->getAlignment()->setHorizontal($H_CENTER)->setVertical($V_CENTER)->setWrapText(true);
        $sheet->getStyle('A3:Q3')->getBorders()->getAllBorders()->setBorderStyle($BORDER_THIN);
        $sheet->getRowDimension(3)->setRowHeight(32);

        // ── Data rows starting at row 4 ───────────────────────────────────
        $estadoColors = [
            'VENCIDO'              => ['FEE2E2', '991B1B'],
            'PAGO PARCIAL'         => ['E0E7FF', '3730A3'],
            'DIFERENCIA PENDIENTE' => ['FCE7F3', '9D174D'],
            'PAGADA'               => ['D1FAE5', '065F46'],
            'PENDIENTE'            => ['FEF3C7', '92400E'],
        ];

        $uRow = 4;
        $uIdx = 1;
        $numCols = ['H', 'I', 'J', 'M', 'N', 'P'];

        $facturasOrdenadas = $facturas->sortBy([
            ['razon_social', 'asc'],
            ['fecha_emision', 'asc'],
        ]);

        foreach ($facturasOrdenadas as $f) {
            $esHuerfana  = in_array((int)($f->id_factura ?? 0), $orphanFacturaIds);
            $recaudacion = (float)($f->monto_recaudacion ?? 0);
            $pendiente   = $esHuerfana ? 0.0 : (
                $f->estado === 'DIFERENCIA PENDIENTE'
                    ? max(0.0, (float)($f->importe_total ?? 0) - $recaudacion)
                    : (float)($f->pendiente_display ?? $f->monto_pendiente ?? 0)
            );

            $rowBg = ($uIdx % 2 === 0) ? 'F1F5F9' : 'FFFFFF';

            $sheet->setCellValue('A' . $uRow, $uIdx);
            $sheet->setCellValue('B' . $uRow, $f->razon_social ?? '');
            $sheet->setCellValue('C' . $uRow, $f->ruc ?? '');
            $sheet->setCellValue('D' . $uRow, ($f->serie ?? '') . '-' . str_pad((string)($f->numero ?? ''), 8, '0', STR_PAD_LEFT));
            $sheet->setCellValue('E' . $uRow, !empty($f->fecha_emision)     ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y')     : '—');
            $sheet->setCellValue('F' . $uRow, !empty($f->fecha_vencimiento) ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—');
            $sheet->setCellValue('G' . $uRow, $f->glosa ?? '');
            $sheet->setCellValue('H' . $uRow, !$esHuerfana ? (float)($f->subtotal_gravado ?? 0) : 0.0);
            $sheet->setCellValue('I' . $uRow, !$esHuerfana ? (float)($f->monto_igv ?? 0) : 0.0);
            $sheet->setCellValue('J' . $uRow, $recaudacion);
            $sheet->setCellValue('K' . $uRow, !empty($f->fecha_recaudacion) ? \Carbon\Carbon::parse($f->fecha_recaudacion)->format('d/m/Y') : '—');
            $sheet->setCellValue('L' . $uRow, $f->tipo_recaudacion ?? '—');
            $sheet->setCellValue('M' . $uRow, !$esHuerfana ? (float)($f->importe_total ?? 0) : 0.0);
            $sheet->setCellValue('N' . $uRow, !$esHuerfana ? (float)($f->monto_abonado ?? 0) : 0.0);
            $sheet->setCellValue('O' . $uRow, !empty($f->fecha_abono) ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—');
            $sheet->setCellValue('P' . $uRow, $esHuerfana ? 0.0 : $pendiente);
            $sheet->setCellValue('Q' . $uRow, ($f->estado ?? '') . ($esHuerfana ? ' [NC]' : ''));

            // Number formats + right-align
            foreach ($numCols as $nc) {
                $sheet->getStyle($nc . $uRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($nc . $uRow)->getAlignment()->setHorizontal($H_RIGHT);
            }

            // Row background
            $sheet->getStyle('A' . $uRow . ':Q' . $uRow)->getFill()
                ->setFillType($FILL_SOLID)->getStartColor()->setRGB($rowBg);

            // Estado cell color
            [$eBg, $eFg] = $estadoColors[$f->estado ?? 'PENDIENTE'] ?? ['FEF3C7', '92400E'];
            $sheet->getStyle('Q' . $uRow)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB($eBg);
            $sheet->getStyle('Q' . $uRow)->getFont()->setBold(true)->getColor()->setRGB($eFg);

            // Thin border around entire row
            $sheet->getStyle('A' . $uRow . ':Q' . $uRow)->getBorders()->getAllBorders()->setBorderStyle($BORDER_THIN);

            // Strikethrough for orphan NC rows
            if ($esHuerfana) {
                $sheet->getStyle('A' . $uRow . ':P' . $uRow)->getFont()->setStrikethrough(true)->getColor()->setRGB('9CA3AF');
            }

            $sheet->getRowDimension($uRow)->setRowHeight(15);
            $uRow++;
            $uIdx++;
        }

        // ── Total row ─────────────────────────────────────────────────────
        $sheet->mergeCells('A' . $uRow . ':G' . $uRow);
        $sheet->setCellValue('A' . $uRow, 'TOTALES GENERALES  —  ' . $facturasParaTotales->count() . ' facturas');
        $sheet->setCellValue('H' . $uRow, (float)$facturasParaTotales->sum('subtotal_gravado'));
        $sheet->setCellValue('I' . $uRow, (float)$facturasParaTotales->sum('monto_igv'));
        $sheet->setCellValue('J' . $uRow, (float)$facturasParaTotales->sum('monto_recaudacion'));
        $sheet->setCellValue('M' . $uRow, (float)$facturasParaTotales->sum('importe_total'));
        $sheet->setCellValue('N' . $uRow, (float)$facturasParaTotales->sum('monto_abonado'));
        $sheet->setCellValue('P' . $uRow, (float)$facturasParaTotales->sum(function ($f) {
            return $f->estado === 'DIFERENCIA PENDIENTE'
                ? max(0.0, (float)($f->importe_total ?? 0) - (float)($f->monto_recaudacion ?? 0))
                : (float)($f->pendiente_display ?? $f->monto_pendiente ?? 0);
        }));
        foreach ($numCols as $nc) {
            $sheet->getStyle($nc . $uRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle($nc . $uRow)->getAlignment()->setHorizontal($H_RIGHT);
        }
        $sheet->getStyle('A' . $uRow . ':Q' . $uRow)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $uRow . ':Q' . $uRow)->getFill()->setFillType($FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $sheet->getStyle('A' . $uRow . ':Q' . $uRow)->getBorders()->getAllBorders()->setBorderStyle($BORDER_MEDIUM);
        $sheet->getRowDimension($uRow)->setRowHeight(22);

        // Freeze header rows
        $sheet->freezePane('A4');
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
