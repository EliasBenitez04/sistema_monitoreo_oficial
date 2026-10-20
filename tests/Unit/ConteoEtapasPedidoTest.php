<?php

namespace Tests\Unit;

use App\Support\ConteoEtapasPedido;
use App\Support\UltimoProcesoOt;
use PHPUnit\Framework\TestCase;

class ConteoEtapasPedidoTest extends TestCase
{
    private function pendiente(int $numero, string $etapa, int $prendas=360): object
    {
        return (object)[
            'nro_ot'=>$numero,
            'etapa'=>$etapa,
            'cantidad_pendiente'=>$prendas
        ];
    }

    private function total(string $etapa, int $cantidad): object
    {
        return (object)['proceso'=>$etapa,'ots'=>$cantidad];
    }

    public function test_costura_once_totales_diez_de_pedido_uno_fuera_de_p(): void
    {
        $pendientes=collect();
        foreach(range(1,10) as $n) {
            $pendientes->push($this->pendiente(30900+$n,'PRODUCCION - COSTURA INTERNA'));
        }
        $resultado=ConteoEtapasPedido::comparar(
            $pendientes,
            collect([
                $this->total('PRODUCCION - COSTURA INTERNA',7),
                $this->total('COSTURA INTERNA',4)
            ])
        );

        $this->assertCount(1,$resultado);
        $this->assertSame(11,$resultado[0]->ots_en_area);
        $this->assertSame(10,$resultado[0]->ots);
        $this->assertSame(1,$resultado[0]->ots_fuera_p);
        $this->assertSame(3600,$resultado[0]->prendas);
        $this->assertFalse($resultado[0]->conteo_inconsistente);
    }

    public function test_todas_son_del_pedido_sin_ot_fuera(): void
    {
        $resultado=ConteoEtapasPedido::comparar(
            collect([
                $this->pendiente(1,'PRODUCCION - REVELADO',360),
                $this->pendiente(2,'PRODUCCION - REVELADO',270),
                $this->pendiente(3,'PRODUCCION - REVELADO',360),
            ]),
            collect([$this->total('PRODUCCION - REVELADO',3)])
        );

        $this->assertSame(3,$resultado[0]->ots_en_area);
        $this->assertSame(0,$resultado[0]->ots_fuera_p);
        $this->assertSame(990,$resultado[0]->prendas);
    }

    public function test_no_inventa_total_si_documentos_no_cubren_las_ot_de_pedido(): void
    {
        $resultado=ConteoEtapasPedido::comparar(
            collect([
                $this->pendiente(1,'PRODUCCION - ATRAQUES'),
                $this->pendiente(2,'PRODUCCION - ATRAQUES'),
            ]),
            collect([$this->total('PRODUCCION - ATRAQUES',1)])
        );

        $this->assertNull($resultado[0]->ots_en_area);
        $this->assertNull($resultado[0]->ots_fuera_p);
        $this->assertTrue($resultado[0]->conteo_inconsistente);
    }

    public function test_sin_trazabilidad_no_simula_un_area_fisica(): void
    {
        $resultado=ConteoEtapasPedido::comparar(
            collect([$this->pendiente(11,'SIN MOVIMIENTO REGISTRADO')]),
            collect()
        );

        $this->assertNull($resultado[0]->ots_en_area);
        $this->assertFalse($resultado[0]->conteo_inconsistente);
    }

    public function test_la_jerarquia_sql_sigue_el_mismo_catalogo_del_dashboard(): void
    {
        $sql=UltimoProcesoOt::prioridadSql('t.proceso');
        $this->assertStringContainsString("WHEN 'COSTURA INTERNA' THEN 70",$sql);
        $this->assertStringContainsString("WHEN 'SERIGRAFIA' THEN 55",$sql);
        $this->assertStringContainsString("WHEN 'LOGISTICA Y DISTRIBUCION' THEN 110",$sql);
        $this->assertStringContainsString('SPLIT_PART(t.proceso', $sql);
        $this->assertSame(70,UltimoProcesoOt::prioridad('PRODUCCION - COSTURA INTERNA'));
        $this->assertSame(55,UltimoProcesoOt::prioridad('PRODUCCION - SERIGRAFIA'));
    }

    public function test_no_duplica_ot_de_pedido_y_no_suma_prendas_de_ot_ajenas(): void
    {
        $resultado=ConteoEtapasPedido::comparar(
            collect([
                $this->pendiente(101,'PRODUCCION - BORDADO',270),
                $this->pendiente(101,'PRODUCCION - BORDADO',270)
            ]),
            collect([$this->total('PRODUCCION - BORDADO',2)])
        );

        $this->assertSame(1,$resultado[0]->ots);
        $this->assertSame(2,$resultado[0]->ots_en_area);
        $this->assertSame(1,$resultado[0]->ots_fuera_p);
        $this->assertSame(270,$resultado[0]->prendas);
    }
}
