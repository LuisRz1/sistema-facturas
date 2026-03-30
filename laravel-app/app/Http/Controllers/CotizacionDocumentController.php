<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Genera y envía documentos combinados de una Cotización:
 *  - PDF con todas las imágenes de Parte Diario
 *  - PDF fusionado con todos los GRR
 */
class CotizacionDocumentController extends Controller
{
    private function mergePdfsWithNode(array $pdfPaths, string $outputPath): bool
    {
        $nodePath = trim((string) @shell_exec('where node 2>NUL'));
        if ($nodePath === '') {
            Log::warning('GRR merge Node fallback unavailable: node not found in PATH');
            return false;
        }

        $scriptPath = base_path('../whatsapp-worker/merge-pdfs.js');
        if (!is_file($scriptPath)) {
            Log::warning('GRR merge Node fallback unavailable: script not found', ['script' => $scriptPath]);
            return false;
        }

        $escapedOutput = escapeshellarg($outputPath);
        $escapedInputs = implode(' ', array_map('escapeshellarg', $pdfPaths));
        $cmd = 'node ' . escapeshellarg($scriptPath) . ' ' . $escapedOutput . ' ' . $escapedInputs . ' 2>&1';

        $out = [];
        $code = 1;
        @exec($cmd, $out, $code);

        if ($code !== 0) {
            Log::warning('GRR merge Node fallback failed', [
                'code' => $code,
                'output' => implode("\n", $out),
            ]);
        }

        return $code === 0 && is_file($outputPath) && filesize($outputPath) > 0;
    }

    private function normalizarStorageKey(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $raw)) {
            $parsedPath = (string) (parse_url($raw, PHP_URL_PATH) ?? '');
            $key = ltrim($parsedPath, '/');

            $bucket = (string) config('filesystems.disks.s3.bucket');
            if ($bucket !== '' && str_starts_with($key, $bucket . '/')) {
                $key = substr($key, strlen($bucket) + 1);
            }

            return $key !== '' ? $key : null;
        }

        return ltrim($raw, '/');
    }

    private function readGrrBinary($disk, string $storedPath): ?string
    {
        $key = $this->normalizarStorageKey($storedPath);
        $publicDisk = Storage::disk('public');

        try {
            if ($key && $disk->exists($key)) {
                $bytes = $disk->get($key);
                return is_string($bytes) && $bytes !== '' ? $bytes : null;
            }
        } catch (\Throwable $e) {
            // Continue with URL fallback.
        }

        // Legacy local storage support (historical GRR uploads used disk "public").
        try {
            if ($key) {
                $legacyKey = str_starts_with($key, 'storage/') ? substr($key, 8) : $key;
                if ($publicDisk->exists($legacyKey)) {
                    $bytes = $publicDisk->get($legacyKey);
                    return is_string($bytes) && $bytes !== '' ? $bytes : null;
                }

                $fullPath = storage_path('app/public/' . ltrim($legacyKey, '/'));
                if (is_file($fullPath)) {
                    $bytes = @file_get_contents($fullPath);
                    return is_string($bytes) && $bytes !== '' ? $bytes : null;
                }
            }
        } catch (\Throwable $e) {
            // Continue with URL fallback.
        }

        if (preg_match('/^https?:\/\//i', $storedPath)) {
            try {
                $res = Http::timeout(25)->get($storedPath);
                if ($res->successful()) {
                    $bytes = $res->body();
                    return is_string($bytes) && $bytes !== '' ? $bytes : null;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function isPdfBinary(string $content): bool
    {
        $head = ltrim(substr($content, 0, 20));
        return str_starts_with($head, '%PDF-');
    }

    private function mergePdfsWithFpdi(array $pdfPaths, string $outputPath): bool
    {
        if (!class_exists('setasign\\Fpdi\\Fpdi')) {
            return false;
        }

        try {
            $pdf = new \setasign\Fpdi\Fpdi();

            foreach ($pdfPaths as $path) {
                $pageCount = $pdf->setSourceFile($path);

                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            $pdf->Output('F', $outputPath);

            return file_exists($outputPath) && filesize($outputPath) > 0;
        } catch (\Throwable $e) {
            Log::warning('GRR merge FPDI failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function getParteDiarioColumn(string $table): ?string
    {
        if (Schema::hasColumn($table, 'ruta_parte_diario')) {
            return 'ruta_parte_diario';
        }

        if (Schema::hasColumn($table, 'ruta_n_parte_diario')) {
            return 'ruta_n_parte_diario';
        }

        return null;
    }

    private function buildPartesDiariosPdfHtml(object $cotizacion, \Illuminate\Support\Collection $filas, $disk, string $title): ?string
    {
        $cards = [];

        foreach ($filas as $f) {
            if (!$disk->exists($f->ruta_parte_diario)) {
                continue;
            }

            $ext = strtolower(pathinfo($f->ruta_parte_diario, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $mime = $mimeMap[$ext] ?? null;

            if (!$mime) {
                continue;
            }

            $b64   = base64_encode($disk->get($f->ruta_parte_diario));
            $label = $f->n_parte_diario ? "N° Parte: {$f->n_parte_diario}" : 'N° Parte: —';
            $fecha = $f->fecha ? \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') : '—';
            $placa = $f->placa ? "Placa: {$f->placa}" : 'Placa: —';

            $cards[] = [
                'label' => $label,
                'fecha' => $fecha,
                'placa' => $placa,
                'mime'  => $mime,
                'b64'   => $b64,
            ];
        }

        if (empty($cards)) {
            return null;
        }

        $chunks = array_chunk($cards, 2);
        $pagesHtml = '';
        $totalPages = count($chunks);

        foreach ($chunks as $index => $pair) {
            $itemsHtml = '';
            foreach ($pair as $item) {
                $itemsHtml .= "
                <div class='item'>
                    <div class='lbl'>{$item['label']} &nbsp;|&nbsp; {$item['fecha']} &nbsp;|&nbsp; {$item['placa']}</div>
                    <div class='img-wrap'>
                        <img src='data:{$item['mime']};base64,{$item['b64']}'>
                    </div>
                </div>";
            }

            if (count($pair) === 1) {
                $itemsHtml .= "
                <div class='item empty'>
                    <div class='lbl'>Sin segundo parte diario en esta hoja</div>
                    <div class='img-wrap'></div>
                </div>";
            }

            $break = $index < ($totalPages - 1) ? "page-break-after: always;" : '';
            $pagesHtml .= "<div class='page' style='{$break}'>{$itemsHtml}</div>";
        }

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
        <style>
            *{box-sizing:border-box;margin:0;padding:0;}
            body{font-family:Arial,sans-serif;background:#fff;padding:10px;}
            h2{font-size:13px;font-weight:bold;color:#0f172a;margin:0 0 10px;border-bottom:2px solid #e2e8f0;padding-bottom:6px;}
            .page{height:260mm;}
            .item{height:124mm;border:1px solid #e2e8f0;border-radius:6px;padding:7px;margin-bottom:6px;background:#fff;}
            .item:last-child{margin-bottom:0;}
            .item.empty{background:#fafafa;border-style:dashed;}
            .lbl{font-size:10px;font-weight:bold;color:#374151;background:#f8fafc;padding:5px 8px;border-radius:4px;margin-bottom:6px;}
            .img-wrap{height:108mm;display:flex;align-items:center;justify-content:center;border:1px solid #edf2f7;border-radius:4px;background:#fff;overflow:hidden;}
            img{max-width:100%;max-height:106mm;}
        </style></head><body>
        <h2>{$title}</h2>
        {$pagesHtml}
        </body></html>";
    }

    // ── PDF PARTES DIARIOS ────────────────────────────────────────────────

    /**
     * Descarga un PDF con todas las imágenes de Parte Diario de la cotización.
     */
    public function downloadPartesDiarios(int $id)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $id)->first();
        if (!$cotizacion) abort(404);
        $disk = Storage::disk('s3');

        $table = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $parteDiarioColumn = $this->getParteDiarioColumn($table);

        if (!$parteDiarioColumn) {
            abort(422, 'La tabla no tiene columna de ruta de Parte Diario (ruta_parte_diario/ruta_n_parte_diario).');
        }

        $filas = DB::table($table)
            ->where('id_cotizacion', $id)
            ->where('activo', 1)
            ->whereNotNull($parteDiarioColumn)
            ->orderBy('fecha')
            ->get([$parteDiarioColumn . ' as ruta_parte_diario', 'n_parte_diario', 'fecha', 'placa']);

        if ($filas->isEmpty()) {
            abort(404, 'No hay imágenes de Parte Diario para esta cotización.');
        }

        $title = "Partes Diarios — Valorización {$cotizacion->numero_valorizacion} — {$cotizacion->obra}";
        $html = $this->buildPartesDiariosPdfHtml($cotizacion, $filas, $disk, $title);

        if (!$html) {
            abort(404, 'Los archivos no se encuentran en el servidor.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $filename = 'PartesDiarios_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
        return $pdf->download($filename);
    }

    // ── PDF GRRs ──────────────────────────────────────────────────────────

    /**
     * Descarga un PDF fusionado con todos los GRRs de la cotización (solo AGREGADO).
     * Intenta qpdf primero; si no está disponible, genera un índice con DomPDF.
     */
    public function downloadGRRs(int $id)
    {
        $cotizacion = DB::table('cotizacion')->where('id_cotizacion', $id)->first();
        if (!$cotizacion) abort(404);
        $disk = Storage::disk('s3');

        $filas = DB::table('agregado_cotizacion')
            ->where('id_cotizacion', $id)
            ->where('activo', 1)
            ->whereNotNull('ruta_grr')
            ->orderBy('fecha')
            ->get(['ruta_grr', 'grr', 'fecha', 'placa', 'n_parte_diario']);

        if ($filas->isEmpty()) {
            abort(404, 'No hay PDFs de GRR para esta cotización.');
        }

        // Recopilar PDFs válidos desde S3 y guardarlos temporalmente para qpdf
        $pdfPaths = [];
        $tempDir  = storage_path('app/temp');
        @mkdir($tempDir, 0755, true);

        foreach ($filas as $f) {
            $ruta = (string) ($f->ruta_grr ?? '');
            if ($ruta === '') {
                continue;
            }

            $bytes = $this->readGrrBinary($disk, $ruta);
            if (!$bytes || !$this->isPdfBinary($bytes)) {
                continue;
            }

            $tmpPath = $tempDir . '/grr_' . $id . '_' . md5($ruta) . '.pdf';
            file_put_contents($tmpPath, $bytes);
            $pdfPaths[] = $tmpPath;
        }

        // Intentar fusionar con qpdf
        $outputPath = $tempDir . '/GRR_' . $id . '_' . time() . '.pdf';
        if (!empty($pdfPaths)) {
            if (count($pdfPaths) === 1) {
                $filename = 'GRR_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
                return response()->download($pdfPaths[0], $filename)->deleteFileAfterSend(true);
            }

            if ($this->mergePdfsWithFpdi($pdfPaths, $outputPath)) {
                foreach ($pdfPaths as $tmpPdfPath) {
                    @unlink($tmpPdfPath);
                }

                $filename = 'GRR_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
                return response()->download($outputPath, $filename)->deleteFileAfterSend(true);
            }

            if ($this->mergePdfsWithNode($pdfPaths, $outputPath)) {
                foreach ($pdfPaths as $tmpPdfPath) {
                    @unlink($tmpPdfPath);
                }

                $filename = 'GRR_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
                return response()->download($outputPath, $filename)->deleteFileAfterSend(true);
            }

            $escapedPaths = implode(' ', array_map('escapeshellarg', $pdfPaths));

            $qpdfPath = trim((string) @shell_exec('where qpdf 2>NUL'));
            if ($qpdfPath === '') {
                $qpdfPath = trim((string) @shell_exec('which qpdf 2>/dev/null'));
            }

            if ($qpdfPath !== '') {
                exec("qpdf --empty --pages {$escapedPaths} -- " . escapeshellarg($outputPath) . ' 2>&1', $out, $code);
                foreach ($pdfPaths as $tmpPdfPath) {
                    @unlink($tmpPdfPath);
                }

                if ($code === 0 && file_exists($outputPath)) {
                    $filename = 'GRR_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
                    return response()->download($outputPath, $filename)->deleteFileAfterSend(true);
                }
            }

            foreach ($pdfPaths as $tmpPdfPath) {
                @unlink($tmpPdfPath);
            }

            Log::warning('GRR merge failed - using index fallback', [
                'cotizacion_id' => $id,
                'pdf_paths_count' => count($pdfPaths),
            ]);
        }

        // Fallback: índice DomPDF con detalles de cada GRR
        $rows = '';
        foreach ($filas as $i => $f) {
            $fecha = $f->fecha ? \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') : '—';
            $rows .= "<tr>
                <td>" . ($i+1) . "</td>
                <td><strong>{$f->grr}</strong></td>
                <td>{$f->n_parte_diario}</td>
                <td>{$fecha}</td>
                <td>{$f->placa}</td>
            </tr>";
        }

        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
            body{font-family:Arial;font-size:11px;margin:20px;}
            h2{font-size:14px;color:#0f172a;margin-bottom:16px;}
            table{width:100%;border-collapse:collapse;}
            th,td{border:1px solid #e2e8f0;padding:8px 10px;text-align:left;}
            th{background:#0f172a;color:#fff;font-size:9px;text-transform:uppercase;letter-spacing:.5px;}
            tr:nth-child(even){background:#f8fafc;}
            .nota{font-size:10px;color:#64748b;margin-top:16px;border:1px solid #e2e8f0;padding:8px;border-radius:4px;}
        </style></head><body>
        <h2>GRRs — Valorización {$cotizacion->numero_valorizacion} — {$cotizacion->obra}</h2>
        <table>
            <tr><th>#</th><th>N° GRR</th><th>N° Parte Diario</th><th>Fecha</th><th>Placa</th></tr>
            {$rows}
        </table>
        <p class='nota'>No se pudo combinar automáticamente los GRR en este servidor.<br>
        Verifique dependencias de despliegue: <strong>setasign/fpdi</strong> (Composer), <strong>Node + pdf-lib + merge-pdfs.js</strong> (fallback), o <strong>qpdf</strong> en PATH.<br>
        Los GRR originales siguen disponibles individualmente en el sistema (botón PDF por fila).</p>
        </body></html>";

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');
        $filename = 'GRR_indice_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
        return $pdf->download($filename);
    }

    // ── ENVIAR PARTES DIARIOS POR WHATSAPP ────────────────────────────────

    /**
     * Genera el PDF de Partes Diarios, lo sube a Cloudinary y lo envía por WhatsApp al cliente.
     */
    public function enviarPartesDiariosWA(int $id)
    {
        $cotizacion = DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->where('c.id_cotizacion', $id)
            ->select('c.*', 'cl.celular', 'cl.correo', 'cl.razon_social')
            ->first();
        $disk = Storage::disk('s3');

        if (!$cotizacion) {
            return response()->json(['success' => false, 'error' => 'Cotización no encontrada.'], 404);
        }

        if (!$cotizacion->celular) {
            $extra = empty($cotizacion->correo)
                ? ' El cliente tampoco tiene correo registrado.'
                : '';
            return response()->json(['success' => false, 'error' => 'El cliente no tiene celular registrado.' . $extra], 422);
        }

        $table = $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $parteDiarioColumn = $this->getParteDiarioColumn($table);

        if (!$parteDiarioColumn) {
            return response()->json(['success' => false, 'error' => 'La tabla no tiene columna de ruta de Parte Diario (ruta_parte_diario/ruta_n_parte_diario).'], 422);
        }

        $filas = DB::table($table)
            ->where('id_cotizacion', $id)
            ->where('activo', 1)
            ->whereNotNull($parteDiarioColumn)
            ->orderBy('fecha')
            ->get([$parteDiarioColumn . ' as ruta_parte_diario', 'n_parte_diario', 'fecha', 'placa']);

        if ($filas->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No hay Partes Diarios con imagen en esta cotización.'], 422);
        }

        $title = "Partes Diarios · Val. {$cotizacion->numero_valorizacion} · {$cotizacion->obra}";
        $html = $this->buildPartesDiariosPdfHtml($cotizacion, $filas, $disk, $title);

        if (!$html) {
            return response()->json(['success' => false, 'error' => 'Los archivos de imagen no se encuentran en el servidor.'], 422);
        }

        try {
            $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'No se pudo generar el PDF: ' . $e->getMessage()], 500);
        }

        // Subir a Cloudinary
        $cloudUrl = $this->subirPdfACloudinary($pdfContent, 'partes_' . $id);
        if (!$cloudUrl) {
            return response()->json(['success' => false, 'error' => 'No se pudo subir el PDF a la nube.'], 500);
        }

        $gateway  = app(\App\Services\WhatsAppGatewayService::class);
        $caption  = "*Partes Diarios*\nVal. {$cotizacion->numero_valorizacion} — {$cotizacion->obra}";
        $filename = 'PartesDiarios_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
        $resultado = $gateway->enviarDocumento($cotizacion->celular, $cloudUrl, $filename, $caption);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok']
                ? "✓ PDF enviado a {$cotizacion->razon_social} ({$cotizacion->celular})"
                : '✗ Error: ' . ($resultado['error'] ?? 'Sin respuesta del gateway'),
        ]);
    }

    /**
     * Genera el PDF combinado de GRRs y lo envía por WhatsApp al cliente.
     */
    public function enviarGRRsWA(int $id)
    {
        $cotizacion = DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->where('c.id_cotizacion', $id)
            ->select('c.*', 'cl.celular', 'cl.correo', 'cl.razon_social')
            ->first();

        if (!$cotizacion) {
            return response()->json(['success' => false, 'error' => 'Cotización no encontrada.'], 404);
        }

        if (!$cotizacion->celular) {
            $extra = empty($cotizacion->correo)
                ? ' El cliente tampoco tiene correo registrado.'
                : '';
            return response()->json(['success' => false, 'error' => 'El cliente no tiene celular registrado.' . $extra], 422);
        }

        $disk = Storage::disk('s3');

        $filas = DB::table('agregado_cotizacion')
            ->where('id_cotizacion', $id)
            ->where('activo', 1)
            ->whereNotNull('ruta_grr')
            ->orderBy('fecha')
            ->get(['ruta_grr']);

        if ($filas->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No hay GRRs adjuntos para esta cotización.'], 422);
        }

        $tempDir = storage_path('app/temp');
        @mkdir($tempDir, 0755, true);

        $pdfPaths = [];
        foreach ($filas as $f) {
            $ruta = (string) ($f->ruta_grr ?? '');
            if ($ruta === '') {
                continue;
            }

            $bytes = $this->readGrrBinary($disk, $ruta);
            if (!$bytes || !$this->isPdfBinary($bytes)) {
                continue;
            }

            $tmpPath = $tempDir . '/grr_wa_' . $id . '_' . md5($ruta) . '.pdf';
            file_put_contents($tmpPath, $bytes);
            $pdfPaths[] = $tmpPath;
        }

        if (empty($pdfPaths)) {
            return response()->json(['success' => false, 'error' => 'No se encontraron PDFs GRR válidos para combinar.'], 422);
        }

        $finalPath = null;
        $cleanupPaths = $pdfPaths;

        if (count($pdfPaths) === 1) {
            $finalPath = $pdfPaths[0];
        } else {
            $outputPath = $tempDir . '/GRR_WA_' . $id . '_' . time() . '.pdf';

            $merged = $this->mergePdfsWithFpdi($pdfPaths, $outputPath)
                || $this->mergePdfsWithNode($pdfPaths, $outputPath);

            if (!$merged) {
                $escapedPaths = implode(' ', array_map('escapeshellarg', $pdfPaths));
                $qpdfPath = trim((string) @shell_exec('where qpdf 2>NUL'));
                if ($qpdfPath === '') {
                    $qpdfPath = trim((string) @shell_exec('which qpdf 2>/dev/null'));
                }

                if ($qpdfPath !== '') {
                    @exec("qpdf --empty --pages {$escapedPaths} -- " . escapeshellarg($outputPath) . ' 2>&1', $out, $code);
                    $merged = ($code === 0 && file_exists($outputPath));
                }
            }

            foreach ($pdfPaths as $tmpPdfPath) {
                @unlink($tmpPdfPath);
            }

            if (!$merged || !file_exists($outputPath)) {
                return response()->json(['success' => false, 'error' => 'No se pudo combinar los PDFs de GRR.'], 500);
            }

            $finalPath = $outputPath;
            $cleanupPaths = [$outputPath];
        }

        $pdfContent = @file_get_contents($finalPath);
        foreach ($cleanupPaths as $p) {
            @unlink($p);
        }

        if (!is_string($pdfContent) || $pdfContent === '') {
            return response()->json(['success' => false, 'error' => 'No se pudo leer el PDF de GRR generado.'], 500);
        }

        $cloudUrl = $this->subirPdfACloudinary($pdfContent, 'grrs_' . $id);
        if (!$cloudUrl) {
            return response()->json(['success' => false, 'error' => 'No se pudo subir el PDF de GRR a la nube.'], 500);
        }

        $gateway  = app(\App\Services\WhatsAppGatewayService::class);
        $caption  = "*GRRs Combinados*\nVal. {$cotizacion->numero_valorizacion} — {$cotizacion->obra}";
        $filename = 'GRR_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cotizacion->numero_valorizacion) . '.pdf';
        $resultado = $gateway->enviarDocumento($cotizacion->celular, $cloudUrl, $filename, $caption);

        return response()->json([
            'success' => $resultado['ok'],
            'message' => $resultado['ok']
                ? "✓ PDF GRR enviado a {$cotizacion->razon_social} ({$cotizacion->celular})"
                : '✗ Error: ' . ($resultado['error'] ?? 'Sin respuesta del gateway'),
        ]);
    }

    // ── HELPER CLOUDINARY ────────────────────────────────────────────────

    private function subirPdfACloudinary(string $pdfContent, string $slug): ?string
    {
        $cloudName    = env('CLOUDINARY_CLOUD_NAME', 'dq3rban3m');
        $uploadPreset = env('CLOUDINARY_UPLOAD_PRESET', 'ml_default');
        $publicId     = $slug . '_' . now()->format('Ymd_His');

        try {
            $response = Http::attach('file', $pdfContent, $publicId . '.pdf')
                ->post("https://api.cloudinary.com/v1_1/{$cloudName}/raw/upload", [
                    'upload_preset' => $uploadPreset,
                    'folder'        => 'cotizaciones_docs',
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
}
