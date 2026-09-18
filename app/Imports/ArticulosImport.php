<?php

namespace App\Imports;

use App\Models\Articulo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ArticulosImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $totalRows;

    public function __construct()
    {
        $this->totalRows = Cache::get('import_total', 1);
    }

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {

            // progreso
            $processed = Cache::increment('import_progress_count');

            // saltar vacíos
            if (empty($row['art_codigo'])) {
                $this->updatePercent($processed);
                continue;
            }

            $codigo = trim($row['art_codigo']);

            // evitar duplicados en memoria
            $data[$codigo] = [
                'art_codigo' => $codigo,
                'art_descripcion' => substr($row['art_descripcion'], 0, 45),
                'art_precio' => $row['art_precio'],
                'art_iva' => $row['art_iva'],
                'prec_vent' => $row['prec_vent'],
            ];

            $this->updatePercent($processed);
        }

        // 🔥 UPSERT MASIVO (NO TOCADO)
        if (!empty($data)) {

            DB::table('articulos')->upsert(
                array_values($data),
                ['art_codigo'],
                ['art_descripcion', 'art_precio', 'art_iva', 'prec_vent']
            );

            // 🔥 AGREGADO: CREAR STOCK (SIN TOCAR TU LOGICA)
            $codigos = array_keys($data);

            $articulos = DB::table('articulos')
                ->whereIn('art_codigo', $codigos)
                ->get(['id_articulo', 'art_codigo']);

            $map = [];

            foreach ($articulos as $a) {
                $map[$a->art_codigo] = $a->id_articulo;
            }

            foreach ($data as $item) {

                if (!isset($map[$item['art_codigo']])) continue;

                $idArticulo = $map[$item['art_codigo']];

                $existeStock = DB::table('stock')
                    ->where('id_articulo', $idArticulo)
                    ->where('cod_suc', 1)
                    ->exists();

                if (!$existeStock) {
                    DB::table('stock')->insert([
                        'id_articulo' => $idArticulo,
                        'cod_suc' => 1,
                        'cantidad' => 1
                    ]);
                }
            }
        }
    }

    private function updatePercent($processed)
    {
        $percent = intval(($processed / $this->totalRows) * 100);

        Cache::put('import_progress', min($percent, 100), 600);
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
