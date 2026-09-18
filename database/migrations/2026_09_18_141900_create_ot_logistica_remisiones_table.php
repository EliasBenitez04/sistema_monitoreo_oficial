<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ot_logistica_remisiones', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('id_ot')->nullable();
            $table->bigInteger('id_trazabilidad')->nullable();
            $table->bigInteger('id_logistica_detalle')->nullable();

            $table->date('fecha_remision')->nullable();
            $table->date('fecha_creacion')->nullable();
            $table->date('fecha_recepcion')->nullable();

            $table->integer('cod_sucursal_salida')->nullable();
            $table->string('sucursal_salida', 100)->nullable();

            $table->integer('cod_sucursal_destino')->nullable();
            $table->string('sucursal_destino', 100)->nullable();
            $table->string('sucursal_logistica', 60)->nullable();

            $table->string('serie', 20);
            $table->string('numero_remision', 30);

            $table->string('codigo', 50);
            $table->string('descripcion', 200)->nullable();

            $table->integer('cantidad');
            $table->decimal('precio_venta', 15, 2)->nullable();
            $table->decimal('costo_unitario', 15, 2)->nullable();

            $table->string('estado', 25)->default('EN_TRANSITO');

            $table->timestamps();

            $table->unique(
                [
                    'serie',
                    'numero_remision',
                    'codigo',
                    'cod_sucursal_salida',
                    'cod_sucursal_destino',
                ],
                'ot_log_rem_unique'
            );

            $table->index('id_ot', 'ot_log_rem_id_ot_idx');
            $table->index('id_trazabilidad', 'ot_log_rem_traz_idx');
            $table->index('id_logistica_detalle', 'ot_log_rem_detalle_idx');
            $table->index('codigo', 'ot_log_rem_codigo_idx');
            $table->index('fecha_remision', 'ot_log_rem_fecha_idx');
            $table->index('fecha_recepcion', 'ot_log_rem_recep_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ot_logistica_remisiones');
    }
};
