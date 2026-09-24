<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('seguimiento_pedido') && !Schema::hasColumn('seguimiento_pedido', 'fecha_pedido')) {
            Schema::table('seguimiento_pedido', function (Blueprint $table) {
                $table->date('fecha_pedido')->nullable()->after('nro_pedido')->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('seguimiento_pedido') && Schema::hasColumn('seguimiento_pedido', 'fecha_pedido')) {
            Schema::table('seguimiento_pedido', function (Blueprint $table) {
                $table->dropColumn('fecha_pedido');
            });
        }
    }
};
