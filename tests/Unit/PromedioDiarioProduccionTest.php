<?php

namespace Tests\Unit;

use App\Support\PromedioDiarioProduccion;
use PHPUnit\Framework\TestCase;

class PromedioDiarioProduccionTest extends TestCase
{
    public function test_ejemplo_del_usuario_resultado_por_21_y_promedio_por_21(): void
    {
        // Los resultados de cada día son 100, 200 y 300 prendas.
        // Día 1: 100 * 21 = 2.100
        // Día 2: (100 + 200) / 2 * 21 = 3.150
        // Día 3: (100 + 200 + 300) / 3 * 21 = 4.200.
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-01', 'prendas' => 100, 'habil' => true],
            ['fecha' => '2026-09-02', 'prendas' => 200, 'habil' => true],
            ['fecha' => '2026-09-03', 'prendas' => 300, 'habil' => true],
        ], '2026-09-03', 21);

        $this->assertSame(2100, $dias['2026-09-01']['proyeccion']);
        $this->assertSame(3150, $dias['2026-09-02']['proyeccion']);
        $this->assertSame(4200, $dias['2026-09-03']['proyeccion']);
        $this->assertSame(1050, $dias['2026-09-02']['variacion_proyeccion']);
        $this->assertSame(1050, $dias['2026-09-03']['variacion_proyeccion']);
        $this->assertSame(2, $dias['2026-09-02']['dias_transcurridos']);
        $this->assertSame(150.0, $dias['2026-09-02']['promedio']);
    }

    public function test_dia_habil_sin_produccion_reduce_proyeccion(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-01', 'prendas' => 120, 'habil' => true],
            ['fecha' => '2026-09-02', 'prendas' => 0, 'habil' => true],
        ], '2026-09-02', 21);

        $this->assertSame(2520, $dias['2026-09-01']['proyeccion']);
        $this->assertSame(1260, $dias['2026-09-02']['proyeccion']);
        $this->assertSame(-1260, $dias['2026-09-02']['variacion_proyeccion']);
        $this->assertSame(-50.0, $dias['2026-09-02']['variacion_proyeccion_porcentaje']);
    }

    public function test_fines_de_semana_no_aumentan_divisor_ni_cambian_proyeccion_sin_movimiento(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-04', 'prendas' => 100, 'habil' => true],
            ['fecha' => '2026-09-05', 'prendas' => 0, 'habil' => false],
            ['fecha' => '2026-09-06', 'prendas' => 0, 'habil' => false],
            ['fecha' => '2026-09-07', 'prendas' => 200, 'habil' => true],
        ], '2026-09-07', 21);

        $this->assertSame(2100, $dias['2026-09-04']['proyeccion']);
        $this->assertSame(2100, $dias['2026-09-05']['proyeccion']);
        $this->assertSame(2100, $dias['2026-09-06']['proyeccion']);
        $this->assertSame(3150, $dias['2026-09-07']['proyeccion']);
        $this->assertSame(2, $dias['2026-09-07']['dias_transcurridos']);
    }

    public function test_produccion_extra_en_fin_de_semana_se_suma_sin_duplicar_dias_habiles(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-04', 'prendas' => 100, 'habil' => true],
            ['fecha' => '2026-09-05', 'prendas' => 50, 'habil' => false],
        ], '2026-09-05', 21);

        $this->assertSame(3150, $dias['2026-09-05']['proyeccion']);
        $this->assertSame(1, $dias['2026-09-05']['dias_transcurridos']);
    }

    public function test_no_proyecta_fechas_posteriores_al_ultimo_dato(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-10-01', 'prendas' => 100, 'habil' => true],
            ['fecha' => '2026-10-02', 'prendas' => 0, 'habil' => true],
            ['fecha' => '2026-10-03', 'prendas' => 0, 'habil' => false],
        ], '2026-10-02', 22);

        $this->assertSame(1100, $dias['2026-10-02']['proyeccion']);
        $this->assertFalse($dias['2026-10-03']['registrado']);
        $this->assertNull($dias['2026-10-03']['proyeccion']);
    }

    public function test_no_inventa_valor_antes_del_primer_habil_ni_con_corte_nulo(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-11-01', 'prendas' => 20, 'habil' => false],
            ['fecha' => '2026-11-02', 'prendas' => 0, 'habil' => true],
        ], '2026-11-02', 21);

        $this->assertNull($dias['2026-11-01']['proyeccion']);
        $this->assertSame(420, $dias['2026-11-02']['proyeccion']);

        $futuro = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-12-01', 'prendas' => 0, 'habil' => true],
        ], null, 21);
        $this->assertNull($futuro['2026-12-01']['proyeccion']);
    }

    public function test_sin_base_para_porcentaje_no_inventa_variacion_porcentual(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-01', 'prendas' => 0, 'habil' => true],
            ['fecha' => '2026-09-02', 'prendas' => 100, 'habil' => true],
        ], '2026-09-02', 21);

        $this->assertSame(1050, $dias['2026-09-02']['proyeccion']);
        $this->assertNull($dias['2026-09-02']['variacion_proyeccion_porcentaje']);
    }
}
