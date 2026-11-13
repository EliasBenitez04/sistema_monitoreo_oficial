<?php

use App\Support\InviernoDocumento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // PostgreSQL no permite construir índices concurrentes dentro de una transacción.
    public $withinTransaction = false;

    public function up(): void
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $numero = InviernoDocumento::numeroSql();
        $this->crearIndice('inv_rem_entrada_normalizada_idx',
            "ON ot_logistica_remisiones (cod_sucursal_salida, UPPER(TRIM(serie)), ($numero)) WHERE cod_sucursal_destino = 1",
            $driver);
        $this->crearIndice('maestro_codigo_normalizado_idx',
            'ON maestro_codigos (UPPER(TRIM(cod_articulo)))', $driver);

        // El planificador necesita estadísticas de las expresiones recién indexadas.
        if ($driver === 'pgsql') {
            DB::statement('ANALYZE ot_logistica_remisiones');
            DB::statement('ANALYZE maestro_codigos');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $concurrente = $driver === 'pgsql' ? 'CONCURRENTLY' : '';
        DB::statement("DROP INDEX $concurrente IF EXISTS maestro_codigo_normalizado_idx");
        DB::statement("DROP INDEX $concurrente IF EXISTS inv_rem_entrada_normalizada_idx");
    }

    private function crearIndice(string $nombre, string $definicion, string $driver): void
    {
        $concurrente = '';
        if ($driver === 'pgsql') {
            $concurrente = 'CONCURRENTLY';
            // Una construcción interrumpida puede dejar un índice inválido con ese nombre.
            $invalido = DB::table('pg_catalog.pg_index')->whereRaw('indexrelid = to_regclass(?)', [$nombre])
                ->where('indisvalid', false)->exists();
            if ($invalido) {
                DB::statement("DROP INDEX CONCURRENTLY $nombre");
            }
        }

        DB::statement("CREATE INDEX $concurrente IF NOT EXISTS $nombre $definicion");
    }
};
