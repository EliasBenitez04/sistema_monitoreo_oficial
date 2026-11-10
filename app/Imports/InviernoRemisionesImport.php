<?php

namespace App\Imports;

use App\Services\InviernoTrazabilidadService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InviernoRemisionesImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public int $nuevas = 0;
    public int $existentes = 0;
    public int $rechazadas = 0;
    public array $errores = [];
    private InviernoTrazabilidadService $service;
    private ?int $usuario;
    private string $delimitador;

    public function __construct(InviernoTrazabilidadService $service, ?int $usuario = null, string $delimitador = ',')
    {
        $this->service = $service;
        $this->usuario = $usuario;
        $this->delimitador = $delimitador;
    }

    public function getCsvSettings(): array
    {
        return ['delimiter' => $this->delimitador, 'input_encoding' => 'UTF-8'];
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty() || array_diff(['local', 'serie', 'nro'], $rows->first()->keys()->all())) {
            throw ValidationException::withMessages(['archivo' => 'El archivo debe tener encabezados LOCAL, SERIE y NRO en la primera fila y al menos un documento.']);
        }
        if ($rows->count() > 20000) {
            throw ValidationException::withMessages(['archivo' => 'Importá hasta 20.000 documentos por archivo.']);
        }
        $mapa = $this->service->mapaLocales();
        $locales = $this->service->locales();
        foreach ($rows->values() as $indice => $row) {
            if ($row->filter(fn ($valor) => trim((string) $valor) !== '')->isEmpty()) continue;
            try {
                $nueva = $this->service->registrar($row->all(), $this->usuario, $mapa, $locales);
                $nueva ? $this->nuevas++ : $this->existentes++;
            } catch (ValidationException $e) {
                $this->rechazadas++;
                if (count($this->errores) < 50) {
                    $this->errores[] = 'Fila '.($indice + 2).': '.implode(' ', array_merge(...array_values($e->errors())));
                }
            }
        }
        if ($this->nuevas + $this->existentes + $this->rechazadas === 0) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene documentos.']);
        }
    }
}
