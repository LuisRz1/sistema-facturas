<?php

namespace App\Services;

final class SaldoFacturaService
{
    /**
     * Calcula el saldo de una factura sin mezclar una recaudación pendiente con
     * la deuda. La recaudación solo representa un pago cuando fue confirmada.
     */
    public function calcular(
        float $importeTotal,
        float $montoAbonado = 0,
        float $totalRecaudacion = 0,
        ?string $fechaRecaudacion = null,
        string $moneda = 'PEN',
        ?float $montoCambio = null,
        ?string $tipoRecaudacion = null,
        ?string $estado = null,
        bool $recaudacionActiva = true,
    ): float {
        if ($estado === 'ANULADO') {
            return 0.0;
        }

        // Las notas de crédito se almacenan con importe negativo y no tienen
        // pagos/recaudaciones que deban convertirlas en una deuda positiva.
        if ($importeTotal < 0) {
            return round($importeTotal, 2);
        }

        $recaudacionConfirmada = $this->recaudacionConfirmadaEnMoneda(
            totalRecaudacion: $totalRecaudacion,
            fechaRecaudacion: $fechaRecaudacion,
            moneda: $moneda,
            montoCambio: $montoCambio,
            tipoRecaudacion: $tipoRecaudacion,
            recaudacionActiva: $recaudacionActiva,
        );

        return round(max(0, $importeTotal - $montoAbonado - $recaudacionConfirmada), 2);
    }

    public function recaudacionConfirmadaEnMoneda(
        float $totalRecaudacion,
        ?string $fechaRecaudacion,
        string $moneda,
        ?float $montoCambio,
        ?string $tipoRecaudacion,
        bool $recaudacionActiva = true,
    ): float {
        if (
            !$recaudacionActiva
            || $totalRecaudacion <= 0
            || empty($fechaRecaudacion)
            || $tipoRecaudacion === 'AUTODETRACCION'
        ) {
            return 0.0;
        }

        if (strtoupper($moneda) === 'USD') {
            $tipoCambio = round((float) $montoCambio, 4);

            // total_recaudacion se guarda en PEN; sin TC no se puede restar de
            // una factura USD de forma segura.
            return $tipoCambio > 0
                ? round($totalRecaudacion / $tipoCambio, 2)
                : 0.0;
        }

        return round($totalRecaudacion, 2);
    }
}
