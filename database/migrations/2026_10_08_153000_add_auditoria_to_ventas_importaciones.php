<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ventas_importaciones')) {
            Schema::table('ventas_importaciones', function (Blueprint $table) {
                if (!Schema::hasColumn('ventas_importaciones', 'filas_duplicadas')) {
                    $table->integer('filas_duplicadas')
                        ->default(0)
                        ->after('filas_omitidas');
                }

                if (!Schema::hasColumn('ventas_importaciones', 'filas_invalidas')) {
                    $table->integer('filas_invalidas')
                        ->default(0)
                        ->after('filas_duplicadas');
                }
            });
        }

        if (!Schema::hasTable('ventas_importacion_omitidas')) {
            Schema::create('ventas_importacion_omitidas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('importacion_id');
                $table->integer('fila')->nullable();
                $table->string('tipo', 20);
                $table->string('codigo', 80)->nullable();
                $table->string('comprobante', 120)->nullable();
                $table->string('local', 120)->nullable();
                $table->text('motivo');
                $table->timestamps();

                $table->index(
                    ['importacion_id', 'tipo'],
                    'ventas_omitidas_import_tipo_idx'
                );
                $table->index(
                    'codigo',
                    'ventas_omitidas_codigo_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_importacion_omitidas');

        if (Schema::hasTable('ventas_importaciones')) {
            Schema::table('ventas_importaciones', function (Blueprint $table) {
                if (Schema::hasColumn('ventas_importaciones', 'filas_invalidas')) {
                    $table->dropColumn('filas_invalidas');
                }

                if (Schema::hasColumn('ventas_importaciones', 'filas_duplicadas')) {
                    $table->dropColumn('filas_duplicadas');
                }
            });
        }
    }
};
