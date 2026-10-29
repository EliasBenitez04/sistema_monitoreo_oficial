<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesorios_stock_ajustes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('accesorio_articulo_id');
            $table->string('sucursal_key', 130);
            $table->string('sucursal', 150);
            $table->bigInteger('saldo_anterior');
            $table->bigInteger('saldo_nuevo');
            $table->bigInteger('diferencia');
            $table->string('motivo', 250)->nullable();
            $table->unsignedBigInteger('usuario_id');
            $table->date('fecha_ajuste');
            $table->timestamps();
            $table->index(
                ['accesorio_articulo_id', 'sucursal_key', 'fecha_ajuste'],
                'acc_ajustes_stock_busqueda'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesorios_stock_ajustes');
    }
};
