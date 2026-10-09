<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ventas')) {
            return;
        }

        /*
         * Índices pensados para las consultas reales del dashboard.
         * PostgreSQL 9.5 soporta IF NOT EXISTS en CREATE INDEX.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS ventas_fecha_id_idx
             ON ventas (fecha, id)'
        );

        DB::statement(
            'CREATE INDEX IF NOT EXISTS ventas_fecha_vendedor_idx
             ON ventas (fecha, vendedor)'
        );

        DB::statement(
            'CREATE INDEX IF NOT EXISTS ventas_fecha_tipo_idx
             ON ventas (fecha, tipo_comprobante)'
        );

        DB::statement(
            'CREATE INDEX IF NOT EXISTS ventas_fecha_comprobante_idx
             ON ventas (fecha, comprobante)'
        );

        DB::statement(
            'CREATE INDEX IF NOT EXISTS ventas_fecha_local_vendedor_idx
             ON ventas (fecha, local, vendedor)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS ventas_fecha_local_vendedor_idx'
        );
        DB::statement(
            'DROP INDEX IF EXISTS ventas_fecha_comprobante_idx'
        );
        DB::statement(
            'DROP INDEX IF EXISTS ventas_fecha_tipo_idx'
        );
        DB::statement(
            'DROP INDEX IF EXISTS ventas_fecha_vendedor_idx'
        );
        DB::statement(
            'DROP INDEX IF EXISTS ventas_fecha_id_idx'
        );
    }
};
