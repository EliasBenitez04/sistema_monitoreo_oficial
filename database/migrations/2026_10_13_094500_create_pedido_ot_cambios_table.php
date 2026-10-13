<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedido_ot_cambios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pedido_id');
            $table->string('pedido_anterior', 100)->nullable();
            $table->string('pedido_nuevo', 100)->nullable();
            $table->string('ot_anterior', 100)->nullable();
            $table->string('ot_nueva', 100)->nullable();
            $table->string('accion', 30);
            $table->text('motivo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
            $table->index(['pedido_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pedido_ot_cambios');
    }
};
