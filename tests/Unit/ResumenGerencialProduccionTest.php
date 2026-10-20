<?php

namespace Tests\Unit;

use App\Support\ResumenGerencialProduccion;
use PHPUnit\Framework\TestCase;

class ResumenGerencialProduccionTest extends TestCase
{
    public function test_ultimo_dia_de_2058_prendas_vs_promedio_de_2506_6_es_inferior_17_9_por_ciento(): void
    {
        $this->assertSame(-17.9, ResumenGerencialProduccion::desviacionDiaria(2058, 2506.6));
    }

    public function test_sin_promedio_no_inventa_variacion_de_rendimiento(): void
    {
        $this->assertNull(ResumenGerencialProduccion::desviacionDiaria(null, 2500.0));
        $this->assertNull(ResumenGerencialProduccion::desviacionDiaria(1300, null));
        $this->assertNull(ResumenGerencialProduccion::desviacionDiaria(1300, 0.0));
    }

    public function test_ot_de_pedido_p_en_it_es_un_subconjunto_de_ot_asociadas(): void
    {
        $avance = ResumenGerencialProduccion::avanceOtP(50, 15);
        $this->assertSame(35, $avance['con_hito']);
        $this->assertSame(70.0, $avance['porcentaje']);
    }

    public function test_no_divide_por_cero_ni_muestra_porcentaje_ficticio(): void
    {
        $avance = ResumenGerencialProduccion::avanceOtP(0, 0);
        $this->assertSame(0, $avance['con_hito']);
        $this->assertNull($avance['porcentaje']);
    }

    public function test_si_los_pendientes_exceden_vinculadas_requiere_revision(): void
    {
        $avance = ResumenGerencialProduccion::avanceOtP(4, 5);
        $this->assertNull($avance['con_hito']);
        $this->assertNull($avance['porcentaje']);
    }

    public function test_un_pedido_totalmente_pendiente_no_tiene_ingresos_it(): void
    {
        $avance = ResumenGerencialProduccion::avanceOtP(29, 29);
        $this->assertSame(0, $avance['con_hito']);
        $this->assertSame(0.0, $avance['porcentaje']);
    }
}
