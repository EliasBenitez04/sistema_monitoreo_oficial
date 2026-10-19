<?php

namespace Tests\Unit;

use App\Support\PromedioDiarioProduccion;
use PHPUnit\Framework\TestCase;

class PromedioDiarioProduccionTest extends TestCase
{
    public function test_historico_compara_promedio_acumulado_con_el_dia_anterior(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-01', 'prendas' => 100],
            ['fecha' => '2026-09-02', 'prendas' => 200],
            ['fecha' => '2026-09-03', 'prendas' => 300],
        ], '2026-09-03');

        $this->assertSame(100.0, $dias['2026-09-01']['promedio']);
        $this->assertNull($dias['2026-09-01']['promedio_anterior']);
        $this->assertSame(150.0, $dias['2026-09-02']['promedio']);
        $this->assertSame(100.0, $dias['2026-09-02']['promedio_anterior']);
        $this->assertSame(50.0, $dias['2026-09-02']['variacion']);
        $this->assertSame(50.0, $dias['2026-09-02']['variacion_porcentaje']);
        $this->assertSame(200.0, $dias['2026-09-03']['promedio']);
        $this->assertSame(50.0, $dias['2026-09-03']['variacion']);
    }

    public function test_un_dia_sin_movimientos_cuenta_como_cero_y_baja_el_promedio(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-01', 'prendas' => 120],
            ['fecha' => '2026-09-02', 'prendas' => 0],
        ], '2026-09-02');

        $this->assertSame(60.0, $dias['2026-09-02']['promedio']);
        $this->assertSame(-60.0, $dias['2026-09-02']['variacion']);
        $this->assertSame(-50.0, $dias['2026-09-02']['variacion_porcentaje']);
        $this->assertSame(-120, $dias['2026-09-02']['variacion_prendas']);
    }

    public function test_dias_posteriores_al_corte_no_se_contabilizan_como_cero(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-10-01', 'prendas' => 100],
            ['fecha' => '2026-10-02', 'prendas' => 200],
            ['fecha' => '2026-10-03', 'prendas' => 0],
        ], '2026-10-02');

        $this->assertSame(150.0, $dias['2026-10-02']['promedio']);
        $this->assertFalse($dias['2026-10-03']['registrado']);
        $this->assertNull($dias['2026-10-03']['promedio']);
    }

    public function test_no_muestra_variacion_porcentual_con_base_cero(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-09-01', 'prendas' => 0],
            ['fecha' => '2026-09-02', 'prendas' => 100],
        ], '2026-09-02');

        $this->assertSame(50.0, $dias['2026-09-02']['promedio']);
        $this->assertSame(50.0, $dias['2026-09-02']['variacion']);
        $this->assertNull($dias['2026-09-02']['variacion_porcentaje']);
    }

    public function test_mes_sin_fecha_de_corte_no_inventa_promedios(): void
    {
        $dias = PromedioDiarioProduccion::calcular([
            ['fecha' => '2026-11-01', 'prendas' => 0],
        ], null);

        $this->assertFalse($dias['2026-11-01']['registrado']);
        $this->assertNull($dias['2026-11-01']['promedio']);
    }
}
