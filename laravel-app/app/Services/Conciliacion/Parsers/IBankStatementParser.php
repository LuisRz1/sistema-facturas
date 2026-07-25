<?php

namespace App\Services\Conciliacion\Parsers;

interface IBankStatementParser
{
    /**
     * Detecta si un archivo corresponde a este banco.
     * Retorna ['ok' => bool, 'banco' => string, 'moneda' => string] o ['ok' => false, 'error' => string]
     */
    public function detectar(string $filePath): array;

    /**
     * Parsea el archivo y retorna un array de MovimientoEstandar.
     * @return \App\Services\Conciliacion\MovimientoEstandar[]
     */
    public function parse(string $filePath): array;

    /**
     * Valida la estructura del archivo (columnas obligatorias, formato).
     * Retorna ['ok' => bool] o ['ok' => false, 'error' => string]
     */
    public function validarEstructura(string $filePath): array;
}
