<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maestro_codigos')) {
            return;
        }

        Schema::create('maestro_codigos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('cod_proveedor', 30)->nullable();
            $table->string('proveedor', 120)->nullable();

            // El archivo trae "Cod Articulo" y "Cod.Articulo".
            $table->string('cod_articulo_origen', 80)->nullable();
            $table->string('cod_articulo', 80);

            $table->string('cod_base', 80)->nullable();
            $table->string('cod_imagen', 80)->nullable();
            $table->string('color', 40)->nullable();
            $table->string('talle', 40)->nullable();

            $table->string('articulo', 255)->nullable();
            $table->string('grupo_precio', 100)->nullable();
            $table->string('familia', 100)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('motivo', 255)->nullable();
            $table->string('linea', 100)->nullable();
            $table->string('grupo', 180)->nullable();
            $table->string('grupo_plan', 180)->nullable();
            $table->string('generico', 140)->nullable();
            $table->string('tejido', 100)->nullable();

            // El Excel trae TEMPORADA, Temporada y TEMP como campos distintos.
            $table->string('temporada', 80)->nullable();
            $table->string('estado', 80)->nullable();
            $table->date('fecha_creacion')->nullable();
            $table->string('temporada_codigo', 80)->nullable();
            $table->string('anio', 30)->nullable();

            $table->string('tipo_stock', 80)->nullable();
            $table->decimal('precio_venta', 30, 6)->nullable();
            $table->decimal('costo_unitario', 30, 6)->nullable();

            $table->string('temp', 80)->nullable();
            $table->string('complejidad', 80)->nullable();
            $table->string('tipo_codigo', 100)->nullable();

            $table->timestamps();

            // Clave de UPSERT. Cada variante/código se actualiza, no se duplica.
            $table->unique('cod_articulo', 'maestro_codigos_cod_articulo_uq');

            // Índices de consulta/filtro. Se mantienen pocos para no frenar el import.
            $table->index('cod_base', 'maestro_codigos_cod_base_idx');
            $table->index('cod_imagen', 'maestro_codigos_cod_imagen_idx');
            $table->index('grupo_plan', 'maestro_codigos_grupo_plan_idx');
            $table->index('linea', 'maestro_codigos_linea_idx');
            $table->index('temporada', 'maestro_codigos_temporada_idx');
            $table->index(['anio', 'temporada'], 'maestro_codigos_anio_temp_idx');
            $table->index('tipo_codigo', 'maestro_codigos_tipo_codigo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maestro_codigos');
    }
};
