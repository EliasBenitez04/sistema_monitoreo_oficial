<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mayorista_vinculos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('factura_clave', 40);
            $table->string('comprobante', 120);
            $table->date('fecha_factura');
            $table->string('codigo', 80);
            $table->unsignedBigInteger('remision_id');
            $table->integer('cantidad');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->unique(['factura_clave', 'remision_id'], 'may_vinculo_fact_rem_unique');
            $table->index(['factura_clave', 'codigo'], 'may_vinculo_fact_codigo_idx');
            $table->index('remision_id', 'may_vinculo_remision_idx');
        });

        Schema::create('mayorista_separaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('logistica_detalle_id');
            $table->unsignedBigInteger('id_ot');
            $table->date('fecha_separacion');
            $table->integer('cantidad');
            $table->string('referencia', 255);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
            $table->index('fecha_separacion', 'may_sep_fecha_idx');
            $table->index('logistica_detalle_id', 'may_sep_detalle_idx');
        });

        Schema::create('mayorista_saldos_iniciales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('mes');
            $table->string('codigo', 80);
            $table->integer('cantidad');
            $table->string('referencia', 255);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
            $table->unique(['mes', 'codigo'], 'may_saldo_mes_codigo_unique');
        });

        Schema::create('mayorista_auditoria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('accion', 50);
            $table->string('factura_clave', 40)->nullable();
            $table->unsignedBigInteger('remision_id')->nullable();
            $table->unsignedBigInteger('separacion_id')->nullable();
            $table->string('codigo', 80)->nullable();
            $table->integer('cantidad')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('detalle')->nullable();
            $table->timestamps();
            $table->index('factura_clave', 'may_audit_fact_idx');
            $table->index('created_at', 'may_audit_fecha_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mayorista_auditoria');
        Schema::dropIfExists('mayorista_saldos_iniciales');
        Schema::dropIfExists('mayorista_separaciones');
        Schema::dropIfExists('mayorista_vinculos');
    }
};
