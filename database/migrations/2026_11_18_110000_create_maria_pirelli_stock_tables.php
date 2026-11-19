<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maria_pirelli_stock_cortes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('fecha_corte')->unique();
            $table->timestamps();
        });

        Schema::create('maria_pirelli_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('corte_id')->constrained('maria_pirelli_stock_cortes')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('maria_pirelli_articulos')->restrictOnDelete();
            $table->string('sucursal_clave', 122);
            $table->string('sucursal', 120);
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('precio_venta', 15, 2);
            $table->unique(['corte_id', 'sucursal_clave', 'articulo_id'], 'maria_pirelli_stock_sucursal_articulo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maria_pirelli_stock');
        Schema::dropIfExists('maria_pirelli_stock_cortes');
    }
};
