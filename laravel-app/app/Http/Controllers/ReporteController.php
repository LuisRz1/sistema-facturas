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
                ? $f->importe_total
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
            ->whereIn('f.estado', $estadosFiltro)
            ->select([
                'f.id_factura', 'c.id_cliente', 'c.razon_social', 'c.ruc',
                'f.moneda', 'f.estado', 'f.importe_total', 'f.monto_pendiente',
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
                $clientes[$id]['recaudacion_usd']  += $f->monto_recaudacion;
                $clientes[$id]['pendiente_usd']    += $pendienteReal;
            } else {
                $clientes[$id]['deuda_pen']        += $f->importe_total;
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
            'clientes', 'totalPen', 'totalUsd', 'totalRecaudacionPen', 'totalRecaudacionUsd',
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
                ? $f->importe_total
                : $f->monto_pendiente;
            return $f;
        });

        // ── Lógica unificada de huérfanas ──────────────────────────────────
        $orphanFacturaIds    = $this->getOrphanFacturaIds($facturas);
        $facturasParaTotales = $this->filtrarParaTotales($facturas, $orphanFacturaIds);

        // Agrupa todas las facturas para mostrar (incluyendo huérfanas tachadas)
        $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        $clienteNombre = $idCliente
            ? DB::table('cliente')->where('id_cliente', $idCliente)->value('razon_social') ?? 'CLIENTE'
            : '';
        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel  = count($estadosFiltro) >= 5 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);

        $sheet->setCellValue('A1', 'REPORTE DE FACTURAS');
        $sheet->setCellValue('A2', $clienteNombre);
        $sheet->setCellValue('A3', $periodoLabel . ' - ' . $estadoLabel);
        $sheet->setCellValue('A4', 'Generado: ' . now()->format('d/m/Y H:i'));

        $row = 6;
        foreach ($facturasAgrupadas as $empresa => $facturasPorEmpresa) {
            $sheet->setCellValue('A' . $row, strtoupper($empresa));
            $sheet->mergeCells('A' . $row . ':M' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            $headers = ['#', 'Emisión', 'Vcto', 'Factura', 'Glosa', 'Importe', 'Detrac.', 'F.Detrac', 'Tipo', 'Abonado', 'F.Abono', 'Pendiente', 'Estado'];
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($this->getColumn($col + 1) . $row, $header);
            }
            $row++;

            foreach ($facturasPorEmpresa as $idx => $f) {
                // Marcar huérfanas con nota visual en la columna Estado
                $esHuerfana  = in_array((int) $f->id_factura, $orphanFacturaIds);
                $estadoCelda = $f->estado . ($esHuerfana ? ' [NC SIN FACTURA]' : '');

                $sheet->setCellValue('A' . $row, $idx + 1);
                $sheet->setCellValue('B' . $row, $f->fecha_emision ?? '—');
                $sheet->setCellValue('C' . $row, $f->fecha_vencimiento ?? '—');
                $sheet->setCellValue('D' . $row, $f->serie . '-' . str_pad($f->numero, 8, '0', STR_PAD_LEFT));
                $sheet->setCellValue('E' . $row, $f->glosa ?? '—');
                $sheet->setCellValue('F' . $row, $f->importe_total);
                $sheet->setCellValue('G' . $row, ($f->monto_recaudacion ?? 0) > 0 ? $f->monto_recaudacion : '—');
                $sheet->setCellValue('H' . $row, $f->fecha_recaudacion ?? '—');
                $sheet->setCellValue('I' . $row, $f->tipo_recaudacion ?? '—');
                $sheet->setCellValue('J' . $row, ($f->monto_abonado ?? 0) > 0 ? $f->monto_abonado : '—');
                $sheet->setCellValue('K' . $row, $f->fecha_abono ?? '—');
                $sheet->setCellValue('L' . $row, $esHuerfana ? '—' : $f->pendiente_display);
                $sheet->setCellValue('M' . $row, $estadoCelda);

                // Tachar visualmente las filas huérfanas en Excel
                if ($esHuerfana) {
                    $sheet->getStyle('A' . $row . ':M' . $row)
                        ->getFont()
                        ->setStrikethrough(true)
                        ->getColor()
                        ->setRGB('999999');
                }

                $row++;
            }

            // Totales por empresa: solo facturas que cuentan
            $facturasPorEmpresaParaTotales = $facturasParaTotales
                ->where('razon_social', $empresa);

            $totEmpresa     = $facturasPorEmpresaParaTotales->sum('importe_total');
            $totRecEmpresa  = $facturasPorEmpresaParaTotales->sum('monto_recaudacion');
            $totAbono       = $facturasPorEmpresaParaTotales->sum('monto_abonado');
            $totPendEmpresa = $facturasPorEmpresaParaTotales->sum('pendiente_display');

            $sheet->setCellValue('E' . $row, 'SUBTOTAL');
            $sheet->setCellValue('F' . $row, $totEmpresa);
            $sheet->setCellValue('G' . $row, $totRecEmpresa > 0 ? $totRecEmpresa : '—');
            $sheet->setCellValue('J' . $row, $totAbono > 0 ? $totAbono : '—');
            $sheet->setCellValue('L' . $row, $totPendEmpresa);
            $sheet->getStyle('E' . $row . ':M' . $row)->getFont()->setBold(true);
            $row += 2;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Reporte-Facturas-' . now()->format('YmdHi') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
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
