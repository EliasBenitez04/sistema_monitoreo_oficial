<?php

namespace Tests\Unit;

use App\Support\MayoristaOrigenRemision as Origen;
use App\Support\MayoristaCruceDocumento;
use PHPUnit\Framework\TestCase;

class MayoristaOrigenRemisionTest extends TestCase
{
    private function fila($codOrigen, $origen, $destino = 25, $alias = 'MATRIZ'): object
    {
        return (object) [
            'cod_sucursal_salida' => $codOrigen,
            'sucursal_salida' => $origen,
            'cod_sucursal_destino' => $destino,
            'sucursal_destino' => $alias,
            'sucursal_logistica' => 'MATRIZ',
            'es_modelo' => 0,
        ];
    }

    public function test_multiplaza_codigo_nueve_a_matriz_veinticinco_es_local_no_central(): void
    {
        $r = $this->fila(9, 'MULTIPLAZA');
        $this->assertSame('MULTIPLAZA', Origen::nombreOrigen($r));
        $this->assertSame('LOCAL', Origen::tipoOrigen($r));
        $this->assertSame('LOCAL → MATRIZ', Origen::clasificar($r));
    }

    public function test_casa_central_usa_el_codigo_de_origen_uno(): void
    {
        $r = $this->fila(1, 'CASA CENTRAL');
        $this->assertSame('CASA CENTRAL', Origen::nombreOrigen($r));
        $this->assertSame('CENTRAL', Origen::tipoOrigen($r));
        $this->assertSame('CASA CENTRAL → MATRIZ', Origen::clasificar($r));
    }

    public function test_no_confunde_el_destino_matriz_con_un_origen_casa_central(): void
    {
        $r = $this->fila(9, 'CASA CENTRAL');
        // Un nombre erróneo en la columna salida no debe ganar al código 9.
        $this->assertSame('MULTIPLAZA', Origen::nombreOrigen($r));
        $this->assertSame('LOCAL', Origen::tipoOrigen($r));
        $this->assertStringContainsString('cod_sucursal_destino,0) = 25',
            Origen::expresionDestinoMatriz());
        $this->assertStringContainsString("'MATRIZ','COMERCIAL MATRIZ'",
            Origen::expresionDestinoMatriz());
    }

    public function test_otro_local_luque_tambien_es_asignacion_a_matriz(): void
    {
        $r = $this->fila(3, 'LUQUE');
        $this->assertSame('LUQUE', Origen::nombreOrigen($r));
        $this->assertSame('LOCAL → MATRIZ', Origen::clasificar($r));
    }

    public function test_sin_origen_no_se_atribuye_automaticamente_a_central(): void
    {
        $r = $this->fila(null, null);
        $this->assertSame('NO IDENTIFICADO', Origen::tipoOrigen($r));
        $this->assertSame('ORIGEN NO IDENTIFICADO', Origen::nombreOrigen($r));
        $this->assertStringContainsString('COALESCE(r.cod_sucursal_salida,0)',
            Origen::esOrigenMatrizSql());
    }

    public function test_modelo_muestra_con_origen_local_se_distingue_de_central(): void
    {
        $r = $this->fila(9, 'MULTIPLAZA');
        $r->es_modelo = 1;
        $this->assertSame('MODELO MUESTRA · LOCAL', Origen::clasificar($r));
    }

    public function test_la_remision_agrupada_y_sus_talles_conservan_el_origen_local(): void
    {
        $r = (object) array_merge((array) $this->fila(9,'MULTIPLAZA'), [
            'id' => 101,'id_ot' => 76,'serie' => '1','numero_remision' => '50099',
            'fecha_remision' => '2026-10-10','codigo' => '050617448AZ01',
            'cantidad' => 8,'usadas' => 2,
        ]);
        $docs = MayoristaCruceDocumento::agrupar(collect([$r]),
            ['050617448AZ01'=>2],
            collect(['050617448AZ01'=>(object)['pendiente'=>0]]));
        $this->assertCount(1,$docs);
        $this->assertSame('MULTIPLAZA',$docs[0]->origen);
        $this->assertSame('LOCAL',$docs[0]->tipo_origen);
        $this->assertSame('LOCAL → MATRIZ',$docs[0]->canal);
        $this->assertSame(6,$docs[0]->disponibles);
        $this->assertSame('MULTIPLAZA',$docs[0]->lineas[0]->origen_real);
    }
}
