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
                'pendientes'        => $facturas->whereIn('estado', ['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'POR VALIDAR DETRACCION'])->count(),
                'pagadas'           => $facturas->where('estado', 'PAGADA')->count(),
                'total_bruto'       => $facturas->sum('importe_total'),
                'total_recaudacion' => $facturas->sum('monto_recaudacion'),
                'total_neto'        => $facturas->sum('neto_caja'),
                'saldo_cobrar'      => $facturas->whereNotIn('estado', ['PAGADA'])->sum('monto_pendiente'),
            ],
        ]);
    }

    /** Vista PDF — se abre en nueva pestaña. */
    public function pdf(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        // Acepta estado simple (legacy) o array estados[]
        $estadosParam = $request->input('estados', []);
        $estadoSimple = $request->input('estado');

        if ($estadoSimple) {
            $estadosFiltro = [$estadoSimple];
        } elseif (!empty($estadosParam)) {
            $estadosFiltro = (array) $estadosParam;
        } else {
            $estadosFiltro = ['PENDIENTE','VENCIDO','PAGO PARCIAL','POR VALIDAR DETRACCION'];
        }

        // Acepta usuario_id simple (legacy) o array usuario_ids[]
        $usuarioIdsParam = $request->input('usuario_ids', []);
        $usuarioIdSimple = $request->input('usuario_id');
        $usuarioIds = $usuarioIdSimple
            ? [$usuarioIdSimple]
            : (array) $usuarioIdsParam;

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();

        $resumen = [
            'total_facturas'    => $facturas->count(),
            'pendientes'        => $facturas->count(),
            'pagadas'           => 0,
            'vencidas'          => $facturas->where('estado','VENCIDO')->count(),
            'total_bruto'       => $facturas->sum('importe_total'),
            'total_recaudacion' => $facturas->sum('monto_recaudacion'),
            'total_neto'        => $facturas->sum('neto_caja'),
            'saldo_cobrar'      => $facturas->sum('monto_pendiente'),
        ];

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

        // Usuarios destino pre-seleccionados (puede venir del modal legacy)
        $usuariosDestino = [];
        if (!empty($usuarioIds)) {
            $usuariosDestino = DB::table('usuario')
                ->whereIn('id_usuario', $usuarioIds)
                ->get()
                ->all();
        }

        // NUEVO: todos los usuarios con celular o correo para el selector inline
        $todosUsuarios = DB::table('usuario')
            ->where(function ($q) {
                $q->whereNotNull('celular')->orWhereNotNull('correo');
            })
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'celular', 'correo']);

        $estadoLabel  = count($estadosFiltro) === 4
            ? 'TODOS LOS PENDIENTES'
            : implode(' · ', $estadosFiltro);
        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);

        // Pasamos los estados como JSON para usarlos en el JS del selector inline
        $estadosFiltroJson = json_encode($estadosFiltro);

        return view('reportes.pdf', compact(
            'facturas','facturasAgrupadas','resumen',
            'clienteNombre','estadoLabel','idCliente','periodoLabel',
            'fechaDesde','fechaHasta',
            'clienteCelular','clienteCorreo',
            'usuariosDestino','todosUsuarios','estadosFiltroJson'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENVÍO POR WHATSAPP
    // ─────────────────────────────────────────────────────────────────────────

    public function enviarReporteWhatsApp(Request $request, WhatsAppGatewayService $gateway)
    {
        $idCliente  = $request->input('id_cliente');
        $usuarioId  = $request->input('usuario_id');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $celular    = null;
        $nombre     = null;

        if ($usuarioId) {
            $dest = DB::table('usuario')->where('id_usuario', $usuarioId)->first();
            if (!$dest || !$dest->celular) {
                return response()->json(['success'=>false,'error'=>'El usuario no tiene celular registrado.'], 422);
            }
            $celular = $dest->celular;
            $nombre  = $dest->nombre . ' ' . $dest->apellido;
        } elseif ($idCliente) {
            $cliente = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if (!$cliente || !$cliente->celular) {
                return response()->json(['success'=>false,'error'=>'El cliente no tiene celular registrado.'], 422);
            }
            $celular = $cliente->celular;
            $nombre  = $cliente->razon_social;
        } else {
            return response()->json(['success'=>false,'error'=>'Debes seleccionar un cliente o usuario destino.'], 422);
        }

        // Acepta estados[] o estado simple
        $estadosParam = $request->input('estados', []);
        $estadosFiltro = !empty($estadosParam)
            ? (array) $estadosParam
            : ($estado ? [$estado] : ['PENDIENTE','VENCIDO','PAGO PARCIAL','POR VALIDAR DETRACCION']);

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $periodoLabel  = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel   = count($estadosFiltro) === 4 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);
        $clienteNombre = $nombre ?? 'TODOS LOS CLIENTES';
        $facturasAgrupadas = null;

        $resumen = [
            'total_facturas'    => $facturas->count(),
            'pendientes'        => $facturas->count(),
            'pagadas'           => 0,
            'vencidas'          => $facturas->where('estado','VENCIDO')->count(),
            'total_bruto'       => $facturas->sum('importe_total'),
            'total_recaudacion' => $facturas->sum('monto_recaudacion'),
            'total_neto'        => $facturas->sum('neto_caja'),
            'saldo_cobrar'      => $facturas->sum('monto_pendiente'),
        ];

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.pdf_doc', compact(
                'facturas','facturasAgrupadas','resumen',
                'clienteNombre','estadoLabel','periodoLabel'
            ))->setPaper('a4','landscape');
            $pdfContent = $pdf->output();
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'error'=>'No se pudo generar el PDF: '.$e->getMessage()], 500);
        }

        $cloudUrl = $this->subirPdfACloudinary($pdfContent, $estadoLabel, $periodoLabel);
        if (!$cloudUrl) {
            return response()->json(['success'=>false,'error'=>'No se pudo subir el PDF a Cloudinary.'], 500);
        }

        $partes = ['Reporte'];
        $partes[] = preg_replace('/[^A-Za-z0-9]/', '_', $estadoLabel);
        if ($fechaDesde || $fechaHasta) {
            if ($fechaDesde) $partes[] = str_replace('-','', $fechaDesde);
            if ($fechaHasta) $partes[] = 'al_'.str_replace('-','', $fechaHasta);
        }
        $nombreArchivo = implode('_', $partes) . '.pdf';

        $caption = "*Reporte Financiero — CRC S.A.C.*\n{$periodoLabel}\n{$facturas->count()} facturas · Saldo: S/ ".number_format($resumen['saldo_cobrar'],2);

        $resultado = $gateway->enviarDocumento($celular, $cloudUrl, $nombreArchivo, $caption);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok']
                ? "PDF enviado por WhatsApp a {$nombre} ({$celular})"
                : 'No se pudo enviar: '.($resultado['error'] ?? 'Error desconocido'),
        ]);
    }

    private function subirPdfACloudinary(string $pdfContent, string $estadoLabel, string $periodo): ?string
    {
        $cloudName    = env('CLOUDINARY_CLOUD_NAME', 'dq3rban3m');
        $uploadPreset = env('CLOUDINARY_UPLOAD_PRESET', 'ml_default');

        $slug = preg_replace('/[^a-z0-9_\-]/', '_', strtolower($estadoLabel));
        $publicId = 'reporte_' . $slug . '_' . now()->format('Ymd_His');

        try {
            $response = \Illuminate\Support\Facades\Http::attach(
                'file', $pdfContent, $publicId . '.pdf'
            )->post("https://api.cloudinary.com/v1_1/{$cloudName}/raw/upload", [
                'upload_preset' => $uploadPreset,
                'folder'        => 'reportes_financieros',
                'public_id'     => $publicId,
                'resource_type' => 'raw',
            ]);

            if ($response->successful()) {
                $secureUrl = $response->json('secure_url');
                return str_replace('/raw/upload/', '/raw/upload/fl_attachment/', $secureUrl);
            }

            \Log::error('Cloudinary PDF upload error', ['response' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            \Log::error('Cloudinary PDF exception: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENVÍO POR CORREO
    // ─────────────────────────────────────────────────────────────────────────

    public function enviarReporteCorreo(Request $request)
    {
        $idCliente  = $request->input('id_cliente');
        $usuarioId  = $request->input('usuario_id');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $correo  = null;
        $nombre  = null;

        if ($usuarioId) {
            $dest = DB::table('usuario')->where('id_usuario', $usuarioId)->first();
            if (!$dest || !$dest->correo) {
                return response()->json(['success'=>false,'error'=>'El usuario no tiene correo registrado.'], 422);
            }
            $correo = $dest->correo;
            $nombre = $dest->nombre . ' ' . $dest->apellido;
        } elseif ($idCliente) {
            $cliente = DB::table('cliente')->where('id_cliente', $idCliente)->first();
            if (!$cliente || !$cliente->correo) {
                return response()->json(['success'=>false,'error'=>'El cliente no tiene correo registrado.'], 422);
            }
            $correo = $cliente->correo;
            $nombre = $cliente->razon_social;
        } else {
            return response()->json(['success'=>false,'error'=>'Debes seleccionar un cliente o usuario destino.'], 422);
        }

        // Acepta estados[] o estado simple
        $estadosParam = $request->input('estados', []);
        $estadosFiltro = !empty($estadosParam)
            ? (array) $estadosParam
            : ($estado ? [$estado] : ['PENDIENTE','VENCIDO','PAGO PARCIAL','POR VALIDAR DETRACCION']);

        $facturas = $this->queryFacturas($idCliente, null, $fechaDesde, $fechaHasta)
            ->whereIn('f.estado', $estadosFiltro)
            ->get();

        $facturas = $facturas->map(function ($f) {
            $f->neto_caja = $f->importe_total - ($f->monto_recaudacion ?? 0);
            return $f;
        });

        $facturasAgrupadas = $facturas->groupBy('razon_social')->sortKeys();
        $periodoLabel      = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);
        $estadoLabel       = count($estadosFiltro) === 4 ? 'TODOS LOS PENDIENTES' : implode(' · ', $estadosFiltro);
        $clienteNombre     = strtoupper($nombre ?? 'TODOS LOS CLIENTES');
        $idCliente         = $idCliente ?? null;
        $usuarioDestino    = null;

        $resumen = [
            'total_facturas'    => $facturas->count(),
            'pendientes'        => $facturas->count(),
            'pagadas'           => 0,
            'vencidas'          => $facturas->where('estado','VENCIDO')->count(),
            'total_bruto'       => $facturas->sum('importe_total'),
            'total_recaudacion' => $facturas->sum('monto_recaudacion'),
            'total_neto'        => $facturas->sum('neto_caja'),
            'saldo_cobrar'      => $facturas->sum('monto_pendiente'),
        ];

        $htmlReporte = view('reportes.pdf', compact(
            'facturas','facturasAgrupadas','resumen',
            'clienteNombre','estadoLabel','idCliente','periodoLabel',
            'usuarioDestino'
        ))->render();

        $htmlReporte = preg_replace('/<div class="no-print".*?<\/div>/s','',$htmlReporte);
        $htmlReporte = preg_replace('/<div class="send-bar.*?<\/div>/s','',$htmlReporte);
        $htmlReporte = preg_replace('/<script>[\s\S]*?window\.print[\s\S]*?<\/script>/','', $htmlReporte);

        $asunto = "Reporte Financiero — {$clienteNombre} — {$periodoLabel}";

        try {
            Mail::send([], [], function($mail) use ($correo, $asunto, $htmlReporte) {
                $mail->to($correo)->subject($asunto)->html($htmlReporte);
            });
            return response()->json(['success'=>true,'message'=>"Reporte enviado por correo a {$correo}"]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'No se pudo enviar el correo: '.$e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function queryFacturas($idCliente, $estado, $fechaDesde = null, $fechaHasta = null)
    {
        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
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
                'f.monto_abonado',
                'f.monto_pendiente',
                'f.tipo_recaudacion',
                'f.estado',
                'f.forma_pago',
                'c.id_cliente',
                'c.razon_social',
                'c.ruc',
                DB::raw('COALESCE(rec.total_recaudacion, 0) AS monto_recaudacion'),
                DB::raw('COALESCE(rec.porcentaje, 0) AS porcentaje_recaudacion'),
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

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE DEUDA GENERAL
    // ─────────────────────────────────────────────────────────────────────────

    public function deudaGeneral(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $estadosParam = $request->input('estados', []);
        $estadoSimple = $request->input('estado');

        if ($estadoSimple) {
            $estadosFiltro = [$estadoSimple];
        } elseif (!empty($estadosParam)) {
            $estadosFiltro = (array) $estadosParam;
        } else {
            $estadosFiltro = ['PENDIENTE','VENCIDO','PAGO PARCIAL','POR VALIDAR DETRACCION'];
        }

        // Usuarios destino pre-seleccionados (legacy desde modal)
        $usuarioIdsParam = $request->input('usuario_ids', []);
        $usuarioIdSimple = $request->input('usuario_id');
        $usuarioIds = $usuarioIdSimple
            ? [$usuarioIdSimple]
            : (array) $usuarioIdsParam;

        $query = DB::table('factura as f')
            ->join('cliente as c', 'c.id_cliente', '=', 'f.id_cliente')
            ->leftJoin('recaudacion as rec', 'rec.id_factura', '=', 'f.id_factura')
            ->whereIn('f.estado', $estadosFiltro)
            ->select([
                'c.id_cliente','c.razon_social','c.ruc',
                'f.moneda','f.estado','f.importe_total','f.monto_pendiente',
                DB::raw('COALESCE(rec.total_recaudacion, 0) AS monto_recaudacion'),
            ]);

        if ($fechaDesde) $query->where('f.fecha_emision', '>=', $fechaDesde);
        if ($fechaHasta) $query->where('f.fecha_emision', '<=', $fechaHasta);

        $facturas = $query->get();

        $clientes = [];
        foreach ($facturas as $f) {
            $id = $f->id_cliente;
            if (!isset($clientes[$id])) {
                $clientes[$id] = [
                    'razon_social'=>$f->razon_social,'ruc'=>$f->ruc,
                    'deuda_pen'=>0,'deuda_usd'=>0,
                    'recaudacion_pen'=>0,'recaudacion_usd'=>0,
                    'pendiente_pen'=>0,'pendiente_usd'=>0,
                    'facturas'=>0,'estados'=>[],
                ];
            }
            $clientes[$id]['facturas']++;
            if ($f->moneda === 'USD') {
                $clientes[$id]['deuda_usd']       += $f->importe_total;
                $clientes[$id]['recaudacion_usd'] += $f->monto_recaudacion;
                $clientes[$id]['pendiente_usd']   += $f->monto_pendiente;
            } else {
                $clientes[$id]['deuda_pen']       += $f->importe_total;
                $clientes[$id]['recaudacion_pen'] += $f->monto_recaudacion;
                $clientes[$id]['pendiente_pen']   += $f->monto_pendiente;
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

        $estadoLabel  = count($estadosFiltro) === 4
            ? 'TODOS LOS PENDIENTES'
            : implode(' · ', $estadosFiltro);
        $periodoLabel = $this->buildPeriodoLabel($fechaDesde, $fechaHasta);

        $usuariosDestino = [];
        if (!empty($usuarioIds)) {
            $usuariosDestino = DB::table('usuario')
                ->whereIn('id_usuario', $usuarioIds)
                ->get()
                ->all();
        }

        // NUEVO: todos los usuarios con celular o correo para el selector inline
        $todosUsuarios = DB::table('usuario')
            ->where(function ($q) {
                $q->whereNotNull('celular')->orWhereNotNull('correo');
            })
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'celular', 'correo']);

        $estadosFiltroJson = json_encode($estadosFiltro);

        return view('reportes.deuda_general', compact(
            'clientes','totalPen','totalUsd',
            'totalRecaudacionPen','totalRecaudacionUsd',
            'totalPendientePen','totalPendienteUsd',
            'periodoLabel','fechaDesde','fechaHasta',
            'estadoLabel','usuariosDestino',
            'todosUsuarios','estadosFiltroJson'
        ));
    }
}
