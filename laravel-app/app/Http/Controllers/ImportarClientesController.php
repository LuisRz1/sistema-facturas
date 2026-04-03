<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarClientesController extends Controller
{
    public function index()
    {
        return view('clientes.importar');
    }

    public function importar(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'archivo.required' => 'Selecciona un archivo Excel.',
        ]);

        $archivo   = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return back()->with('error', 'El archivo debe ser .xlsx o .xls')->withInput();
        }

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el Excel: ' . $e->getMessage())->withInput();
        }

        $hoja  = $spreadsheet->getActiveSheet();
        $filas = $hoja->toArray(null, true, false, false);

        if (empty($filas)) {
            return back()->with('error', 'El archivo está vacío.')->withInput();
        }

        // Detectar fila de encabezados (primera fila)
        $encabezados = array_map(fn($h) => strtoupper(trim((string)($h ?? ''))), $filas[0]);

        // Mapear columnas por nombre
        $colTipo       = null; // TIPO DE DOCUMENTO
        $colNumero     = null; // NUMERO (RUC o DNI)
        $colDenom      = null; // DENOMINACION
        $colDireccion  = null; // DIRECCION
        $colEmail      = null; // EMAIL
        $colTelefono   = null; // TELEFONO MOVIL

        foreach ($encabezados as $i => $nombre) {
            // El encabezado de tipo tiene saltos de línea, simplificamos
            $nombreSimple = trim(preg_replace('/\s+/', ' ', $nombre));
            if (str_starts_with($nombreSimple, 'TIPO DE DOCUMENTO')) {
                $colTipo = $i;
            }
            if ($nombreSimple === 'NUMERO') {
                $colNumero = $i;
            }
            if ($nombreSimple === 'DENOMINACION') {
                $colDenom = $i;
            }
            if ($nombreSimple === 'DIRECCION') {
                $colDireccion = $i;
            }
            if ($nombreSimple === 'EMAIL') {
                $colEmail = $i;
            }
            if ($nombreSimple === 'TELEFONO MOVIL') {
                $colTelefono = $i;
            }
        }

        // Validar que existan columnas clave
        if ($colNumero === null || $colDenom === null) {
            return back()->with('error', 'El Excel no tiene las columnas requeridas (NUMERO, DENOMINACION).')->withInput();
        }

        // Quitar encabezado
        array_shift($filas);

        $insertados   = 0;
        $actualizados = 0;
        $omitidos     = 0;
        $errores      = [];

        DB::beginTransaction();

        try {
            foreach ($filas as $numFila => $fila) {
                $numero      = trim((string)($fila[$colNumero]    ?? ''));
                $denominacion= trim((string)($fila[$colDenom]     ?? ''));

                // Saltar filas vacías
                if (empty($numero) || empty($denominacion)) {
                    $omitidos++;
                    continue;
                }

                // Limpiar el número: quitar decimales si viene como float (919616820.0)
                $numero = preg_replace('/\.0+$/', '', $numero);
                $numero = preg_replace('/[^0-9A-Za-z]/', '', $numero);

                if (empty($numero)) {
                    $omitidos++;
                    continue;
                }

                // Determinar tipo de cliente
                $tipoCodigo = $colTipo !== null ? trim((string)($fila[$colTipo] ?? '')) : '';
                $tipoCliente = 'PERSONA JURIDICA'; // default
                if ($tipoCodigo === '1') {
                    $tipoCliente = 'PERSONA NATURAL';
                } elseif ($tipoCodigo === '6') {
                    $tipoCliente = 'PERSONA JURIDICA';
                } else {
                    // Inferir por longitud del número
                    if (strlen($numero) <= 8) {
                        $tipoCliente = 'PERSONA NATURAL';
                    }
                }

                // Regla especial: RUC de 11 dígitos que inicia con 10 es persona natural.
                if (preg_match('/^10\d{9}$/', $numero)) {
                    $tipoCliente = 'PERSONA NATURAL';
                }

                $direccion = $colDireccion !== null ? trim((string)($fila[$colDireccion] ?? '')) : null;
                $email     = $colEmail     !== null ? trim((string)($fila[$colEmail]     ?? '')) : null;
                $celular   = $colTelefono  !== null ? trim((string)($fila[$colTelefono]  ?? '')) : null;

                // Limpiar celular
                if ($celular) {
                    $celular = preg_replace('/\.0+$/', '', $celular);
                    $celular = preg_replace('/[^0-9+]/', '', $celular);
                    if (empty($celular)) $celular = null;
                    if ($celular && strlen($celular) > 15) $celular = substr($celular, 0, 15);
                }

                // Validar email
                if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $email = null;
                }

                if ($email && strlen($email) > 150) $email = null;
                if ($direccion && strlen($direccion) > 250) $direccion = substr($direccion, 0, 250);

                // Calcular estado_contado
                $tieneCelular   = !empty($celular);
                $tieneCorreo    = !empty($email);
                $tieneDireccion = !empty($direccion);
                if ($tieneCelular && $tieneCorreo && $tieneDireccion) {
                    $estadoContado = 'COMPLETO';
                } elseif ($tieneCelular || $tieneCorreo) {
                    $estadoContado = 'INCOMPLETO';
                } else {
                    $estadoContado = 'SIN_DATOS';
                }

                // Verificar si ya existe
                $existente = DB::table('cliente')->where('ruc', $numero)->first();

                if ($existente) {
                    DB::table('cliente')->where('ruc', $numero)->update([
                        'razon_social'        => $denominacion,
                        'tipo_cliente'        => $tipoCliente,
                        'direccion_fiscal'    => $direccion ?: $existente->direccion_fiscal,
                        'correo'              => $email ?: $existente->correo,
                        'celular'             => $celular ?: $existente->celular,
                        'estado_contado'      => $estadoContado,
                        'fecha_actualizacion' => now(),
                    ]);
                    $actualizados++;
                } else {
                    DB::table('cliente')->insert([
                        'ruc'              => $numero,
                        'razon_social'     => $denominacion,
                        'tipo_cliente'     => $tipoCliente,
                        'direccion_fiscal' => $direccion,
                        'correo'           => $email,
                        'celular'          => $celular,
                        'estado_contado'   => $estadoContado,
                        'activo'           => 1,
                        'fecha_creacion'   => now(),
                    ]);
                    $insertados++;
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error',
                'Error al procesar el archivo: ' . $e->getMessage()
            )->withInput();
        }

        return redirect()->route('clientes.index')->with('resumen_importacion', [
            'insertados'   => $insertados,
            'actualizados' => $actualizados,
            'omitidos'     => $omitidos,
            'errores'      => $errores,
        ]);
    }
}
