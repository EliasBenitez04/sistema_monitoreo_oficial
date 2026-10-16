<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ot_sin_destino_clasificaciones')) {
            return;
        }

        Schema::create('ot_sin_destino_clasificaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_ot')->unique();
            $table->unsignedInteger('cantidad_ayala')->default(0);
            $table->unsignedInteger('cantidad_modelo')->default(0);
            $table->string('referencia', 500);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_sin_destino_clasificaciones');
    }
};
