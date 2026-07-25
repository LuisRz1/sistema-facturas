<?php

namespace App\Services\Conciliacion;

class MovimientoEstandar
{
    public string $banco = '';
    public string $moneda = 'PEN';
    public string $cuenta_bancaria = '';
    public ?string $fecha_operacion = null;
    public ?string $fecha_proceso = null;
    public ?string $hora = null;
    public string $numero_operacion = '';
    public string $descripcion = '';
    public string $referencia = '';
    public float $importe = 0.0;
    public string $tipo_movimiento = 'ABONO';
    public string $tipo_movimiento_raw = '';
    public float $abono = 0.0;
    public float $cargo = 0.0;
    public float $saldo = 0.0;
    public string $codigo_interno_banco = '';
    public bool $es_ignorable = false;
    public bool $es_transferencia_tercero = false;

    public function toArray(): array
    {
        return [
            'banco' => $this->banco,
            'moneda' => $this->moneda,
            'cuenta_bancaria' => $this->cuenta_bancaria,
            'fecha_operacion' => $this->fecha_operacion,
            'fecha_proceso' => $this->fecha_proceso,
            'hora' => $this->hora,
            'numero_operacion' => $this->numero_operacion,
            'descripcion' => $this->descripcion,
            'referencia' => $this->referencia,
            'importe' => $this->importe,
            'tipo_movimiento' => $this->tipo_movimiento,
            'abono' => $this->abono,
            'cargo' => $this->cargo,
            'saldo' => $this->saldo,
            'codigo_interno_banco' => $this->codigo_interno_banco,
        ];
    }
}
