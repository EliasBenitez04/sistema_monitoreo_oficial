<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invierno_remisiones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('cod_sucursal_origen');
            $table->string('local', 100);
            $table->string('serie', 20);
            $table->string('numero_remision', 30);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
            $table->unique(['cod_sucursal_origen', 'serie', 'numero_remision'], 'inv_rem_documento_unique');
        });

        Schema::create('invierno_asignaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invierno_remision_id');
            $table->unsignedBigInteger('remision_entrada_id');
            $table->unsignedBigInteger('remision_salida_id');
            $table->integer('cantidad');
            // Identidad documental al asociar: permite detectar cambios posteriores.
            $table->string('codigo', 50);
            $table->string('serie_salida', 20);
            $table->string('numero_salida', 30);
            $table->integer('cod_sucursal_destino');
            $table->string('sucursal_destino', 100)->nullable();
            $table->string('observacion', 300)->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('anulada_at')->nullable();
            $table->unsignedBigInteger('anulada_por')->nullable();
            $table->string('motivo_anulacion', 300)->nullable();
            $table->timestamps();

            $table->foreign('invierno_remision_id', 'inv_asig_documento_fk')->references('id')->on('invierno_remisiones');
            $table->foreign('remision_entrada_id', 'inv_asig_entrada_fk')->references('id')->on('ot_logistica_remisiones');
            $table->foreign('remision_salida_id', 'inv_asig_salida_fk')->references('id')->on('ot_logistica_remisiones');
            $table->index(['invierno_remision_id', 'anulada_at'], 'inv_asig_documento_idx');
            $table->index(['remision_entrada_id', 'anulada_at'], 'inv_asig_entrada_idx');
            $table->index(['remision_salida_id', 'anulada_at'], 'inv_asig_salida_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invierno_asignaciones');
        Schema::dropIfExists('invierno_remisiones');
    }
};
