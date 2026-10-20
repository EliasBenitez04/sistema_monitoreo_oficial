<?php

namespace Tests\Unit;

use App\Support\AntiguedadOt;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AntiguedadOtTest extends TestCase
{
    public function test_ot_30924_corte_antiguo_25_dias_y_hoy_33_58_dias(): void
    {
        $fecha='2026-09-17';
        $pedido='2026-09-01';
        $corteImportado=Carbon::parse('2026-10-12 00:00:00');
        $hoy=Carbon::parse('2026-10-20 13:55:00');

        $historico=AntiguedadOt::calcular($fecha,$pedido,$corteImportado);
        $vigente=AntiguedadOt::calcular($fecha,$pedido,$hoy);

        $this->assertSame(25.0,$historico['dias_sin_movimiento']);
        $this->assertSame(33.58,$vigente['dias_sin_movimiento']);
        $this->assertSame(33.58,$vigente['dias_espera_pedido']);
    }

    public function test_mismo_dia_los_procesos_no_reinician_reloj_al_asignar_etapa_correcta(): void
    {
        $corte=Carbon::parse('2026-10-20 13:55:00');
        $serigrafia=AntiguedadOt::calcular('2026-09-17','2026-09-01',$corte);
        $costura=AntiguedadOt::calcular('2026-09-17','2026-09-01',$corte);
        $this->assertSame($serigrafia['dias_sin_movimiento'],$costura['dias_sin_movimiento']);
    }

    public function test_fecha_pedido_posterior_a_movimiento_separa_antiguedad_y_espera_p(): void
    {
        $corte=Carbon::parse('2026-10-20 00:00:00');
        $medidas=AntiguedadOt::calcular('2026-10-01','2026-10-15',$corte);
        $this->assertSame(19.0,$medidas['dias_sin_movimiento']);
        $this->assertSame(5.0,$medidas['dias_espera_pedido']);
        $this->assertSame('2026-10-15',$medidas['inicio_espera']->format('Y-m-d'));
    }

    public function test_sin_movimiento_no_se_muestra_cero_dias_de_actividad(): void
    {
        $medidas=AntiguedadOt::calcular(null,'2026-10-15',Carbon::parse('2026-10-20'));
        $this->assertNull($medidas['dias_sin_movimiento']);
        $this->assertSame(5.0,$medidas['dias_espera_pedido']);
        $this->assertFalse($medidas['tiene_movimiento']);
    }

    public function test_fecha_futura_no_produce_dias_negativos(): void
    {
        $medidas=AntiguedadOt::calcular('2026-10-22','2026-10-01',Carbon::parse('2026-10-20'));
        $this->assertSame(0.0,$medidas['dias_sin_movimiento']);
        $this->assertSame(0.0,$medidas['dias_espera_pedido']);
    }

    public function test_corte_mes_actual_se_calcula_hasta_ahora_y_no_al_ultimo_hito(): void
    {
        $ahora=Carbon::parse('2026-10-20 13:55:00');
        $corte=AntiguedadOt::cortePendientesDelMes(
            Carbon::parse('2026-10-01'),$ahora
        );
        $this->assertSame('2026-10-20 13:55:00',$corte->format('Y-m-d H:i:s'));
    }

    public function test_corte_mes_pasado_cierra_fin_de_mes_y_futuro_no_tiene_pendientes(): void
    {
        $ahora=Carbon::parse('2026-10-20 13:55:00');
        $septiembre=AntiguedadOt::cortePendientesDelMes(
            Carbon::parse('2026-09-01'),$ahora
        );
        $this->assertSame('2026-09-30 23:59:59',
            $septiembre->format('Y-m-d H:i:s'));
        $this->assertNull(AntiguedadOt::cortePendientesDelMes(
            Carbon::parse('2026-11-01'),$ahora
        ));
    }

    public function test_precision_horas_y_dias_misma_que_detalle_ot(): void
    {
        $desde=Carbon::parse('2026-09-17');
        $hasta=Carbon::parse('2026-10-20 13:55:00');
        $horas=round($desde->diffInMinutes($hasta) / 60,2);
        $dias=AntiguedadOt::dias($desde,$hasta);
        $this->assertSame(805.92,$horas);
        $this->assertSame(33.58,$dias);
    }
}
