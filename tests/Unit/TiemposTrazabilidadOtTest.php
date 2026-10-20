<?php

namespace Tests\Unit;

use App\Support\TiemposTrazabilidadOt;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TiemposTrazabilidadOtTest extends TestCase
{
    private function traza(int $id, string $fecha, string $proceso): object
    {
        return (object) [
            'id_trazabilidad' => $id,
            'fecha_proceso' => $fecha,
            'proceso' => $proceso,
            'resultado' => 360,
        ];
    }

    public function test_ordena_cronologicamente_incluso_si_el_flujo_de_produccion_esta_fuera_de_orden(): void
    {
        $ahora = Carbon::parse('2026-09-12 00:00:00');
        // Ejemplo de OT 30824: TIZADAS 31/08 se importó antes de
        // MOLDERIA/GRÁFICO 02/09, aunque su paso de flujo sea posterior.
        $intervalos = TiemposTrazabilidadOt::calcular(collect([
            $this->traza(11, '2026-09-02', 'DISEÑO - MOLDERIA'),
            $this->traza(15, '2026-09-09', 'PRODUCCION - CORTE'),
            $this->traza(14, '2026-09-02', 'PRODUCCION - TIZADAS'),
            $this->traza(12, '2026-09-02', 'DISEÑO - DISEÑO GRAFICO'),
            $this->traza(13, '2026-08-31', 'PRODUCCION - TIZADAS'),
        ]), $ahora);

        $this->assertSame([13, 11, 12, 14, 15], $intervalos->pluck('traza')
            ->map(function ($t) { return $t->id_trazabilidad; })->all());
        $this->assertSame(48.0, $intervalos[0]['duracion_horas']);
        $this->assertSame(0.0, $intervalos[1]['duracion_horas']);
        $this->assertSame(168.0, $intervalos[3]['duracion_horas']);
        $this->assertSame(168.0, $intervalos[4]['desde_anterior_horas']);
        $this->assertSame(72.0, $intervalos[4]['duracion_horas']);
        $this->assertTrue($intervalos[4]['es_ultimo']);
    }

    public function test_ultimo_registro_logistica_se_calcula_hasta_hoy(): void
    {
        $result = TiemposTrazabilidadOt::calcular(collect([
            $this->traza(1, '2026-10-05', 'TERMINACION - TERMINACION'),
            $this->traza(2, '2026-10-10', 'LOGISTICA - LOGISTICA Y DISTRIBUCION'),
        ]), Carbon::parse('2026-10-20 00:00:00'));

        $this->assertSame(120.0, $result[0]['duracion_horas']);
        $this->assertSame(240.0, $result[1]['duracion_horas']);
        $this->assertSame(120.0, $result[1]['desde_anterior_horas']);
        $this->assertTrue($result[1]['es_ultimo']);
    }

    public function test_si_hay_reproceso_posterior_a_logistica_se_corta_logistica_y_ultimo_llega_a_hoy(): void
    {
        $result = TiemposTrazabilidadOt::calcular(collect([
            $this->traza(1, '2026-10-05', 'TERMINACION - TERMINACION'),
            $this->traza(2, '2026-10-10', 'LOGISTICA - LOGISTICA Y DISTRIBUCION'),
            $this->traza(3, '2026-10-15', 'REVISION - REVISION'),
        ]), Carbon::parse('2026-10-20 00:00:00'));

        $this->assertSame(120.0, $result[1]['duracion_horas']);
        $this->assertFalse($result[1]['es_ultimo']);
        $this->assertSame(120.0, $result[2]['duracion_horas']);
        $this->assertTrue($result[2]['es_ultimo']);
    }

    public function test_borrar_un_movimiento_recalcula_automaticamente_al_preparar_el_reporte(): void
    {
        $ahora = Carbon::parse('2026-10-20 00:00:00');
        $a = $this->traza(1, '2026-10-01', 'TIZADAS');
        $b = $this->traza(2, '2026-10-05', 'CORTE');
        $c = $this->traza(3, '2026-10-10', 'LOTEO');
        $inicial = TiemposTrazabilidadOt::calcular(collect([$a, $b, $c]), $ahora);
        $corregido = TiemposTrazabilidadOt::calcular(collect([$a, $c]), $ahora);

        $this->assertSame(96.0, $inicial[0]['duracion_horas']);
        $this->assertSame(216.0, $corregido[0]['duracion_horas']);
        $this->assertSame(240.0, $corregido[1]['duracion_horas']);
        $this->assertCount(2, $corregido);
    }

    public function test_un_solo_registro_mide_desde_el_inicio_hasta_hoy(): void
    {
        $result = TiemposTrazabilidadOt::calcular(collect([
            $this->traza(3, '2026-10-19 12:00:00', 'TERMINACION'),
        ]), Carbon::parse('2026-10-20 12:00:00'));

        $this->assertCount(1, $result);
        $this->assertSame(24.0, $result[0]['duracion_horas']);
        $this->assertSame(0.0, $result[0]['desde_anterior_horas']);
    }

    public function test_fechas_futuras_no_producen_duracion_negativa(): void
    {
        $result = TiemposTrazabilidadOt::calcular(collect([
            $this->traza(1, '2026-10-21', 'TERMINACION'),
        ]), Carbon::parse('2026-10-20 12:00:00'));
        $this->assertSame(0.0, $result[0]['duracion_horas']);
        $this->assertTrue($result[0]['fecha_futura']);
    }
}
