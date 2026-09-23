<?php

namespace App\Imports;

use App\Models\Ot;
use App\Models\SeguimientoPedido;
use App\Models\SeguimientoPedidoDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

                if ($nroOt === '' || $nroPedido === '') {
                    continue;
                }

                $this->procesadas++;
                $ot = Ot::where('nro_ot', $nroOt)->first();

                if (!$ot) {
                    $this->noEncontradas[] = $nroOt;
                    continue;
                }

                $pedido = SeguimientoPedido::firstOrCreate(['nro_pedido' => $nroPedido]);

                SeguimientoPedidoDetalle::firstOrCreate([
                    'seguimiento_pedido_id' => $pedido->id,
                    'id_ot' => $ot->id_ot,
                ]);

                $this->vinculadas++;
            }
        });
    }
}
