<?php

namespace App\Imports;

use App\Models\Ot;
use App\Models\SeguimientoPedido;
use App\Models\SeguimientoPedidoDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SeguimientoPedidoImport implements ToCollection, WithHeadingRow
{
    public $procesadas = 0;
    public $vinculadas = 0;
    public $noEncontradas = [];

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $nroOt = trim((string) ($row['nro_ot'] ?? $row['ot'] ?? ''));
                $nroPedido = strtoupper(trim((string) ($row['nro_pedido'] ?? $row['pedido'] ?? '')));
                $fechaPedidoRaw = $row['fecha_pedido'] ?? $row['fecha'] ?? null;
                $fechaPedido = $this->normalizarFecha($fechaPedidoRaw);

                if ($nroOt === '' || $nroPedido === '') {
                    continue;
                }

                $this->procesadas++;
                $ot = Ot::where('nro_ot', $nroOt)->first();

                if (!$ot) {
                    $this->noEncontradas[] = $nroOt;
                    continue;
                }

                $pedido = SeguimientoPedido::firstOrCreate(
                    ['nro_pedido' => $nroPedido],
                    ['fecha_pedido' => $fechaPedido]
                );

                // Si el pedido ya existía sin fecha, completar la fecha real al reimportar.
                if ($fechaPedido && !$pedido->fecha_pedido) {
                    $pedido->fecha_pedido = $fechaPedido;
                    $pedido->save();
                }

                SeguimientoPedidoDetalle::firstOrCreate([
                    'seguimiento_pedido_id' => $pedido->id,
                    'id_ot' => $ot->id_ot,
                ]);

                $this->vinculadas++;
            }
        });
    }

    private function normalizarFecha($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($valor))->format('Y-m-d');
            }

            $texto = trim((string) $valor);
            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $formato) {
                try {
                    return Carbon::createFromFormat($formato, $texto)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // probar siguiente formato
                }
            }

            return Carbon::parse($texto)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
