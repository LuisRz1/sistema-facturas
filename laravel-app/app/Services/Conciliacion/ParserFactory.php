<?php

namespace App\Services\Conciliacion;

use App\Services\Conciliacion\Parsers\IBankStatementParser;
use App\Services\Conciliacion\Parsers\BCPParser;
use App\Services\Conciliacion\Parsers\InterbankParser;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ParserFactory
{
    private array $parsers = [];

    public function __construct()
    {
        $this->parsers = [
            new BCPParser(),
            new InterbankParser(),
        ];
    }

    /**
     * Detecta automaticamente el banco y moneda del archivo.
     */
    public function detectar(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['ok' => false, 'error' => 'ERR-002: Archivo no encontrado o corrupto.'];
        }

        foreach ($this->parsers as $parser) {
            try {
                $resultado = $parser->detectar($filePath);
                if ($resultado['ok']) {
                    return $resultado;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return ['ok' => false, 'error' => 'ERR-007: No se pudo detectar el banco. Verifique que el archivo corresponda a BCP o Interbank.'];
    }

    /**
     * Lista de bancos soportados para mostrar en la UI.
     */
    public function bancosSoportados(): array
    {
        return [
            ['codigo' => 'BCP', 'nombre' => 'BCP — Banco de Credito del Peru', 'monedas' => ['PEN', 'USD'], 'formatos' => ['.xlsx', '.xls', '.csv']],
            ['codigo' => 'IBK', 'nombre' => 'Interbank', 'monedas' => ['PEN'], 'formatos' => ['.xlsx', '.xls', '.csv']],
        ];
    }

    /**
     * Crea el parser para un banco especifico.
     */
    public function crear(string $banco): IBankStatementParser
    {
        return match (strtoupper($banco)) {
            'BCP' => new BCPParser(),
            'IBK', 'INTERBANK' => new InterbankParser(),
            default => throw new \InvalidArgumentException("Banco no soportado: {$banco}"),
        };
    }
}
