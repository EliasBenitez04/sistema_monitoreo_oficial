<?php

namespace Tests\Unit;

use App\Support\ConteoEtapasPedido;
use App\Support\UltimoProcesoOt;
use PHPUnit\Framework\TestCase;

class ConteoEtapasPedidoTest extends TestCase
{
    private function p(int $n, string $etapa, int $cantidad, bool $conResultado = true): object
    {
        return (object)[
            'nro_ot' => $n,
            'etapa' => $etapa,
            'cantidad_pendiente' => $cantidad,
            'cantidad_etapa_disponible' => $conResultado,
        ];
    }

    private function total(
        string $etapa,
        int $ots,
        int $prendas,
        int $otPedido,
        int $prendasPedido,
        int $otSinPedido,
        int $prendasSinPedido,
        int $sinCantidad = 0,
        int $sinCantidadPedido = 0,
        int $sinCantidadSinPedido = 0
    ): object {
        return (object)[
            'proceso' => $etapa,
            'ots' => $ots,
            'prendas_registradas' => $prendas,
            'sin_cantidad' => $sinCantidad,
            'ots_con_pedido' => $otPedido,
            'prendas_con_pedido' => $prendasPedido,
            'con_pedido_sin_cantidad' => $sinCantidadPedido,
            'ots_sin_pedido' => $otSinPedido,
            'prendas_sin_pedido' => $prendasSinPedido,
            'sin_pedido_sin_cantidad' => $sinCantidadSinPedido,
        ];
    }

    public function test_costura_con_63_ot_clasifica_11_p_5_otras_y_47_sin_pedido(): void
    {
        $pendientes = collect();
        for ($n=1; $n<=11; $n++) {
            $pendientes->push($this->p(30900+$n, 'PRODUCCION - COSTURA INTERNA', 320));
        }
        $totales = collect([
            $this->total('PRODUCCION - COSTURA INTERNA', 60, 19000, 14, 4480, 46, 14520),
            $this->total('COSTURA INTERNA', 3, 900, 2, 600, 1, 300)
        ]);
        $costura = ConteoEtapasPedido::comparar($pendientes,$totales)->first();

        $this->assertSame(63, $costura->ots_en_area);
        $this->assertSame(19900, $costura->prendas_en_area);
        $this->assertSame(11, $costura->ots);
        $this->assertSame(3520, $costura->prendas);
        $this->assertSame(5, $costura->ots_con_otro_pedido);
        $this->assertSame(1560, $costura->prendas_con_otro_pedido);
        $this->assertSame(47, $costura->ots_sin_pedido);
        $this->assertSame(14820, $costura->prendas_sin_pedido);
        $this->assertFalse($costura->conteo_inconsistente);
        $this->assertSame($costura->ots_en_area,
            $costura->ots+$costura->ots_con_otro_pedido+$costura->ots_sin_pedido);
        $this->assertSame($costura->prendas_en_area,
            $costura->prendas+$costura->prendas_con_otro_pedido+$costura->prendas_sin_pedido);
    }

    public function test_el_total_de_ot_sin_pedido_no_se_infiere_restandole_solo_las_p(): void
    {
        $r = ConteoEtapasPedido::comparar(
            collect([$this->p(3,'PRODUCCION - REVELADO',360)]),
            collect([$this->total('PRODUCCION - REVELADO',5,1620,3,900,2,720)])
        )->first();
        $this->assertSame(5,$r->ots_en_area);
        $this->assertSame(1,$r->ots);
        $this->assertSame(2,$r->ots_con_otro_pedido);
        $this->assertSame(2,$r->ots_sin_pedido);
        $this->assertSame(540,$r->prendas_con_otro_pedido);
        $this->assertSame(720,$r->prendas_sin_pedido);
    }

    public function test_si_falta_resultado_no_inventa_cantidad_de_prendas(): void
    {
        $r = ConteoEtapasPedido::comparar(
            collect([$this->p(11,'PRODUCCION - BORDADO',360,true)]),
            collect([$this->total('PRODUCCION - BORDADO',3,630,2,360,1,270,1,0,1)])
        )->first();
        $this->assertSame(3,$r->ots_en_area);
        $this->assertNull($r->prendas_en_area);
        $this->assertSame(1,$r->ots_sin_pedido);
        $this->assertNull($r->prendas_sin_pedido);
    }

    public function test_no_presenta_una_conciliacion_de_ot_inconsistente_como_confirmada(): void
    {
        $r = ConteoEtapasPedido::comparar(
            collect([$this->p(1,'PRODUCCION - ATRAQUES',360),$this->p(2,'PRODUCCION - ATRAQUES',360)]),
            collect([$this->total('PRODUCCION - ATRAQUES',1,360,1,360,0,0)])
        )->first();
        $this->assertNull($r->ots_en_area);
        $this->assertNull($r->ots_sin_pedido);
        $this->assertTrue($r->conteo_inconsistente);
    }

    public function test_una_ot_duplicada_no_duplica_cantidad_pendiente(): void
    {
        $r = ConteoEtapasPedido::comparar(
            collect([$this->p(9,'PRODUCCION - SERIGRAFIA',280),$this->p(9,'PRODUCCION - SERIGRAFIA',280)]),
            collect([$this->total('PRODUCCION - SERIGRAFIA',2,550,1,280,1,270)])
        )->first();
        $this->assertSame(1,$r->ots);
        $this->assertSame(280,$r->prendas);
        $this->assertSame(1,$r->ots_sin_pedido);
    }

    public function test_areas_sin_p_pendiente_aparecen_y_etapas_finales_no_saturan_el_cuadro(): void
    {
        $r = ConteoEtapasPedido::comparar(
            collect(),
            collect([
                $this->total('PRODUCCION - LAVANDERIA',2,540,0,0,2,540),
                $this->total('LOGISTICA - LOGISTICA Y DISTRIBUCION',800,200000,0,0,800,200000),
            ])
        );
        $this->assertCount(1,$r);
        $this->assertSame('PRODUCCION - LAVANDERIA',$r[0]->etapa);
        $this->assertSame(2,$r[0]->ots_en_area);
        $this->assertSame(0,$r[0]->ots);
    }

    public function test_el_catalogo_de_procesos_se_comparte_con_el_sql_de_ultima_etapa(): void
    {
        $sql=UltimoProcesoOt::prioridadSql('t.proceso');
        $this->assertStringContainsString("WHEN 'COSTURA INTERNA' THEN 70",$sql);
        $this->assertStringContainsString("WHEN 'LOGISTICA Y DISTRIBUCION' THEN 110",$sql);
        $this->assertSame(70,UltimoProcesoOt::prioridad('PRODUCCION - COSTURA INTERNA'));
    }
}
