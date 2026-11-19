<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maria_pirelli_articulos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 200);
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('precio_venta', 15, 2);
            $table->date('fecha_envio');
            $table->string('linea', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maria_pirelli_articulos');
    }
};
