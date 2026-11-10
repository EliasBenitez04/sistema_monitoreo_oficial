<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulo_imagenes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo_imagen', 13)->unique();
            $table->string('imagen', 255);
            $table->string('nombre_archivo', 255);
            $table->string('valor_v', 64)->nullable();
            $table->string('valor_c', 64)->nullable();
            $table->string('ot', 30)->nullable();
            $table->string('descripcion_archivo', 255)->nullable();
            $table->date('fecha_archivo')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulo_imagenes');
    }
};
