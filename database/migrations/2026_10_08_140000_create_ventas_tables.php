<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ventas_importaciones')) {
            Schema::create('ventas_importaciones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('nombre_archivo', 255);
                $table->string('archivo_hash', 64)->nullable()->index();
                $table->date('fecha_desde')->nullable();
                $table->date('fecha_hasta')->nullable();
                $table->integer('filas_procesadas')->default(0);
                $table->integer('filas_insertadas')->default(0);
                $table->integer('filas_omitidas')->default(0);
                $table->integer('usuario_id')->nullable();
                $table->string('estado', 30)->default('PROCESANDO');
                $table->text('mensaje')->nullable();
                $table->timestamps();

                $table->index('created_at', 'ventas_importaciones_created_idx');
            });
        }

        if (!Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('importacion_id')->nullable();

                $table->string('local', 120);
                $table->string('codigo', 80);
                $table->string('descripcion', 255)->nullable();

                $table->string('cli_cod', 50)->nullable();
                $table->string('cliente', 180)->nullable();
                $table->string('vendedor', 180)->nullable();

                $table->decimal('p_lista', 18, 2)->default(0);
                $table->decimal('descuento', 18, 2)->default(0);
                $table->decimal('p_venta', 18, 2)->default(0);

                $table->date('fecha');
                $table->integer('cantidad');
                $table->string('comprobante', 120);
                $table->string('tipo_comprobante', 20)->nullable();

                /*
                 * Huella idempotente de la línea exportada.
                 * Evita duplicar ventas si se importa nuevamente el mismo
                 * archivo o un archivo con período solapado.
                 */
                $table->string('hash_linea', 40)->unique();

                $table->timestamps();

                $table->index('importacion_id', 'ventas_importacion_idx');
                $table->index('fecha', 'ventas_fecha_idx');
                $table->index('local', 'ventas_local_idx');
                $table->index('codigo', 'ventas_codigo_idx');
                $table->index('vendedor', 'ventas_vendedor_idx');
                $table->index('comprobante', 'ventas_comprobante_idx');
                $table->index('tipo_comprobante', 'ventas_tipo_comp_idx');
                $table->index(
                    ['fecha', 'local'],
                    'ventas_fecha_local_idx'
                );
                $table->index(
                    ['fecha', 'codigo'],
                    'ventas_fecha_codigo_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('ventas_importaciones');
    }
};
