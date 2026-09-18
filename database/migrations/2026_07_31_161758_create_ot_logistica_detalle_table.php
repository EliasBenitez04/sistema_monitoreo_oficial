<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ot_logistica_detalle', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('id_ot');

            $table->unsignedBigInteger('id_trazabilidad');

            $table->string('sucursal');

            $table->integer('cantidad');

            $table->timestamps();

        });
    }


    public function down()
    {
        Schema::dropIfExists('ot_logistica_detalle');
    }
};