<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maestro_codigos')) {
            return;
        }

        /*
         * El archivo histórico contiene valores no convencionales en Año
         * (ej. 273514, ?, etc.). Se conserva el dato original como texto.
         */
        DB::statement(
            "ALTER TABLE maestro_codigos
             ALTER COLUMN anio TYPE varchar(30)
             USING anio::varchar"
        );

        /*
         * Algunos artículos históricos traen costos/precios muy grandes.
         * NUMERIC(30,6) evita overflow y mantiene precisión decimal.
         */
        DB::statement(
            "ALTER TABLE maestro_codigos
             ALTER COLUMN precio_venta TYPE numeric(30,6)
             USING precio_venta::numeric"
        );

        DB::statement(
            "ALTER TABLE maestro_codigos
             ALTER COLUMN costo_unitario TYPE numeric(30,6)
             USING costo_unitario::numeric"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('maestro_codigos')) {
            return;
        }

        /*
         * No reducimos automáticamente los tipos porque los datos históricos
         * podrían no caber nuevamente en smallint/numeric(15,2).
         */
    }
};
