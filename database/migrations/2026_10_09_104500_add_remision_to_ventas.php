<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('ventas')
            && !Schema::hasColumn('ventas', 'remision')
        ) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->string('remision', 120)
                    ->nullable()
                    ->after('comprobante');

                $table->index(
                    'remision',
                    'ventas_remision_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('ventas')
            && Schema::hasColumn('ventas', 'remision')
        ) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropIndex('ventas_remision_idx');
                $table->dropColumn('remision');
            });
        }
    }
};
