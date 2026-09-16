<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas por pares (serie, numero) usando IN de tuplas, que aprovecha el
 * índice uq_factura_serie_numero y evita cadenas de OR.
 */
class DocumentoPares
{
    /**
     * Filas (serie, numero) de facturas activas que existen para los pares.
     *
     * @param array<int, array{0:string,1:int}> $pares
     * @return array<int, object>
     */
    public static function existentes(array $pares): array
    {
        if (empty($pares)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($pares), '(?,?)'));

        return DB::select(
            "select serie, numero from factura where activo = 1 and (serie, numero) in ({$placeholders})",
            self::bindings($pares)
        );
    }

    /**
     * Créditos activos cuyo documento modificado coincide con los pares.
     *
     * @param array<int, array{0:string,1:int}> $pares
     */
    public static function creditos(array $pares): Collection
    {
        if (empty($pares)) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($pares), '(?,?)'));

        return DB::table('credito')
            ->where('activo', 1)
            ->whereRaw("(serie_doc_modificado, numero_doc_modificado) in ({$placeholders})", self::bindings($pares))
            ->get(['id_factura', 'serie_doc_modificado', 'numero_doc_modificado']);
    }

    /**
     * @param array<int, array{0:string,1:int}> $pares
     * @return array<int, mixed>
     */
    public static function bindings(array $pares): array
    {
        $bindings = [];
        foreach ($pares as $par) {
            $bindings[] = (string) $par[0];
            $bindings[] = (int) $par[1];
        }

        return $bindings;
    }
}
