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

    public function test_conserva_el_orden_original_de_ot_30824_y_recalcula_dias_sin_inventar_fechas(): void
    {
        // El controlador entrega en el orden MOLDERIA, GRÁFICO,
        // TIZADAS repetida, CORTE, LOTEO, REVELADO. NO se reordena.
        $filas = collect([
            $this->traza(11,'2026-09-02','DISEÑO - MOLDERIA'),
            $this->traza(12,'2026-09-02','DISEÑO - DISEÑO GRAFICO'),
            $this->traza(13,'2026-08-31','PRODUCCION - TIZADAS'),
            $this->traza(14,'2026-09-02','PRODUCCION - TIZADAS'),
            $this->traza(15,'2026-09-09','PRODUCCION - CORTE'),
            $this->traza(16,'2026-09-10','PRODUCCION - LOTEO Y DISTRIBUCION'),
            $this->traza(17,'2026-09-10','PRODUCCION - REVELADO'),
        ]);
        $resultado=TiemposTrazabilidadOt::calcular($filas,Carbon::parse('2026-10-20 00:00:00'));

        $this->assertSame([11,12,13,14,15,16,17],$resultado->pluck('traza')
            ->map(function($t){return $t->id_trazabilidad;})->all());
        $this->assertSame(0.0,$resultado[0]['duracion_horas']);
        $this->assertTrue($resultado[1]['fecha_invertida']); // 02/09 -> 31/08
        $this->assertSame(0.0,$resultado[1]['duracion_horas']); // NO 48 horas ficticias
        $this->assertSame(48.0,$resultado[2]['duracion_horas']);
        $this->assertSame(168.0,$resultado[3]['duracion_horas']);
        $this->assertSame(24.0,$resultado[4]['duracion_horas']);
        $this->assertSame(0.0,$resultado[5]['duracion_horas']);
        $this->assertSame(960.0,$resultado[6]['duracion_horas']);
        $this->assertTrue($resultado[6]['es_ultimo']);
    }

    public function test_ultima_etapa_logistica_mide_hasta_hoy_y_si_hay_otra_mide_hasta_ella(): void
    {
        $fecha=Carbon::parse('2026-10-20 00:00:00');
        $terminacion=$this->traza(1,'2026-10-05','TERMINACION');
        $logistica=$this->traza(2,'2026-10-10','LOGISTICA - LOGISTICA Y DISTRIBUCION');
        $revision=$this->traza(3,'2026-10-15','REVISION');
        $dos=TiemposTrazabilidadOt::calcular(collect([$terminacion,$logistica]),$fecha);
        $tres=TiemposTrazabilidadOt::calcular(collect([$terminacion,$logistica,$revision]),$fecha);

        $this->assertSame(240.0,$dos[1]['duracion_horas']);
        $this->assertTrue($dos[1]['es_ultimo']);
        $this->assertSame(120.0,$tres[1]['duracion_horas']);
        $this->assertSame(120.0,$tres[2]['duracion_horas']);
        $this->assertTrue($tres[2]['es_ultimo']);
    }

    public function test_borrar_un_proceso_intermedio_recalcula_el_anterior(): void
    {
        $corte=Carbon::parse('2026-10-20 00:00:00');
        $a=$this->traza(1,'2026-10-01','TIZADAS');
        $b=$this->traza(2,'2026-10-05','CORTE');
        $c=$this->traza(3,'2026-10-10','LOTEO');
        $original=TiemposTrazabilidadOt::calcular(collect([$a,$b,$c]),$corte);
        $sinIntermedio=TiemposTrazabilidadOt::calcular(collect([$a,$c]),$corte);
        $this->assertSame(96.0,$original[0]['duracion_horas']);
        $this->assertSame(216.0,$sinIntermedio[0]['duracion_horas']);
        $this->assertSame(240.0,$sinIntermedio[1]['duracion_horas']);
    }

    public function test_un_solo_registro_se_mide_hasta_hoy(): void
    {
        $r=TiemposTrazabilidadOt::calcular(collect([
            $this->traza(1,'2026-10-19 12:00:00','TERMINACION')
        ]),Carbon::parse('2026-10-20 12:00:00'));
        $this->assertSame(24.0,$r[0]['duracion_horas']);
    }

    public function test_fecha_futura_no_se_convierte_en_duracion_positiva(): void
    {
        $r=TiemposTrazabilidadOt::calcular(collect([
            $this->traza(1,'2026-10-21','TERMINACION')
        ]),Carbon::parse('2026-10-20 00:00:00'));
        $this->assertTrue($r[0]['fecha_futura']);
        $this->assertTrue($r[0]['fecha_invertida']);
        $this->assertSame(0.0,$r[0]['duracion_horas']);
    }
}
