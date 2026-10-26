<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesorios_articulos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 200);
            $table->string('linea', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('accesorios_compras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('articulo_id');
            $table->string('fco_nro', 50);
            $table->date('fecha_envio');
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('precio_venta', 15, 2);
            $table->integer('cantidad_compra');
            $table->timestamps();
            $table->foreign('articulo_id', 'acc_compras_articulo_fk')
                ->references('id')->on('accesorios_articulos');
            $table->unique(['articulo_id', 'fco_nro', 'fecha_envio'], 'acc_compras_lote_unique');
            $table->index('fecha_envio', 'acc_compras_fecha_idx');
        });

        // La remisión documenta el artículo, no identifica la compra/FCO de origen.
        Schema::table('ot_logistica_remisiones', function (Blueprint $table) {
            $table->unsignedBigInteger('accesorio_articulo_id')->nullable();
            $table->index('accesorio_articulo_id', 'ot_log_rem_accesorio_idx');
            $table->foreign('accesorio_articulo_id', 'ot_log_rem_accesorio_fk')
                ->references('id')->on('accesorios_articulos');
        });
    }

    public function down(): void
    {
        Schema::table('ot_logistica_remisiones', function (Blueprint $table) {
            $table->dropForeign('ot_log_rem_accesorio_fk');
            $table->dropIndex('ot_log_rem_accesorio_idx');
            $table->dropColumn('accesorio_articulo_id');
        });
        Schema::dropIfExists('accesorios_compras');
        Schema::dropIfExists('accesorios_articulos');
    }
};
