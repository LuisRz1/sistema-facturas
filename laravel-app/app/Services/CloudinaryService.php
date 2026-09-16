<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sube archivos a Cloudinary usando upload unsigned (upload_preset).
 *
 * Centraliza la configuración (config/services.php) para que funcione con
 * `php artisan config:cache`, donde `env()` devuelve null fuera de los archivos
 * de configuración.
 */
class CloudinaryService
{
    /**
     * Sube contenido binario (PDF, imagen, etc.) como recurso "raw".
     *
     * @return string|null URL pública con bandera de descarga, o null si falla.
     */
    public function subirRaw(string $content, string $publicId, ?string $folder = null, string $mime = 'application/pdf', string $extension = 'pdf'): ?string
    {
        $cloudName = (string) config('services.cloudinary.cloud_name');
        $uploadPreset = (string) config('services.cloudinary.upload_preset');
        $folder = $folder ?? (string) config('services.cloudinary.folder', 'reportes_financieros');
        $timeout = (int) config('services.cloudinary.timeout', 60);

        if ($cloudName === '' || $uploadPreset === '') {
            Log::error('Cloudinary: falta CLOUDINARY_CLOUD_NAME o CLOUDINARY_UPLOAD_PRESET');
            return null;
        }

        $filename = $publicId . '.' . $extension;

        try {
            $response = Http::timeout($timeout)
                ->attach('file', $content, $filename, ['Content-Type' => $mime])
                ->post("https://api.cloudinary.com/v1_1/{$cloudName}/raw/upload", [
                    'upload_preset' => $uploadPreset,
                    'folder'        => $folder,
                    'public_id'     => $publicId,
                    'resource_type' => 'raw',
                ]);

            if ($response->successful()) {
                $secureUrl = (string) $response->json('secure_url');
                if ($secureUrl === '') {
                    Log::error('Cloudinary: respuesta sin secure_url', ['status' => $response->status()]);
                    return null;
                }

                return str_replace('/raw/upload/', '/raw/upload/fl_attachment/', $secureUrl);
            }

            Log::error('Cloudinary: fallo al subir', [
                'status' => $response->status(),
                'body'   => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Cloudinary: excepción al subir', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Genera un public_id único y seguro para el archivo.
     */
    public function publicId(string $prefijo, ?string $sufijo = null): string
    {
        $base = preg_replace('/[^a-z0-9_\-]/', '_', strtolower($prefijo));
        $base = trim($base, '_') ?: 'archivo';

        return $base . '_' . now()->format('Ymd_His') . ($sufijo ? '_' . $sufijo : '');
    }
}
