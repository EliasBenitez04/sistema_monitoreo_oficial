<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seguimiento_pedido', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nro_pedido', 50)->unique();
            $table->timestamps();
        });

        Schema::create('seguimiento_pedido_detalle', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('seguimiento_pedido_id');
            $table->unsignedBigInteger('id_ot');
            $table->timestamps();

            $table->unique(['seguimiento_pedido_id', 'id_ot'], 'seg_pedido_ot_unique');
            $table->index('id_ot', 'seg_pedido_id_ot_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seguimiento_pedido_detalle');
        Schema::dropIfExists('seguimiento_pedido');
    }
};
