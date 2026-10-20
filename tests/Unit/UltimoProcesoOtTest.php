<?php

namespace Tests\Unit;

use App\Support\UltimoProcesoOt;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class UltimoProcesoOtTest extends TestCase
{
    private function evento(int $id, string $proceso, string $fecha, int $resultado = 360): object
    {
        return (object) [
            'id_trazabilidad' => $id,
            'proceso' => $proceso,
            'fecha_proceso' => $fecha,
            'resultado' => $resultado,
        ];
    }

    public function test_ot_30924_misma_fecha_escoge_costura_aunque_serigrafia_se_cargue_despues(): void
    {
        $historial = collect([
            $this->evento(950, 'PRODUCCION - COSTURA INTERNA', '2026-09-17 08:00:00'),
            $this->evento(999, 'PRODUCCION - SERIGRAFIA', '2026-09-17 17:00:00'),
            $this->evento(902, 'PRODUCCION - REVELADO', '2026-09-15'),
        ]);

        $seleccion = UltimoProcesoOt::ultimo($historial);

        $this->assertSame('PRODUCCION - COSTURA INTERNA', $seleccion->proceso);
        $this->assertSame(70, UltimoProcesoOt::prioridad($seleccion->proceso));
        $this->assertSame(360, $seleccion->resultado);
        $this->assertSame('2026-09-17', substr($seleccion->fecha_proceso, 0, 10));
    }

    public function test_fecha_posterior_tiene_prioridad_aunque_etapa_sea_anterior(): void
    {
        $historico = collect([
            $this->evento(2, 'PRODUCCION - COSTURA INTERNA', '2026-09-17'),
            $this->evento(3, 'PRODUCCION - SERIGRAFIA', '2026-09-18'),
        ]);
        $this->assertSame(3, UltimoProcesoOt::ultimo($historico)->id_trazabilidad);
    }

    public function test_desempata_registros_de_la_misma_etapa_por_hora_e_id(): void
    {
        $historial = collect([
            $this->evento(6, 'PRODUCCION - COSTURA INTERNA', '2026-09-17 09:00:00'),
            $this->evento(2, 'PRODUCCION - COSTURA INTERNA', '2026-09-17 11:00:00'),
            $this->evento(9, 'PRODUCCION - COSTURA INTERNA', '2026-09-17 11:00:00'),
        ]);
        $this->assertSame(9, UltimoProcesoOt::ultimo($historial)->id_trazabilidad);
    }

    public function test_progresion_completa_del_flujo_en_misma_fecha(): void
    {
        $orden = [
            'DISEÑO - ORDEN DE TRABAJO' => 10,
            'DISEÑO - MOLDERIA' => 15,
            'DISEÑO - PROTOTIPO' => 20,
            'DISEÑO - DISEÑO GRAFICO' => 25,
            'PRODUCCION - TIZADAS' => 30,
            'PRODUCCION - CORTE' => 40,
            'PRODUCCION - LOTEO Y DISTRIBUCION' => 45,
            'PRODUCCION - REVELADO' => 50,
            'PRODUCCION - SERIGRAFIA' => 55,
            'PRODUCCION - BORDADO' => 60,
            'PRODUCCION - COSTURA INTERNA' => 70,
            'PRODUCCION - ATRAQUES' => 75,
            'PRODUCCION - LAVANDERIA' => 80,
            'PRODUCCION - PRETERMINACION' => 85,
            'TERMINACION - INGRESO TERMINACION' => 90,
            'TERMINACION - TERMINACION' => 95,
            'TERMINACION - PRODUCTO TERMINADO' => 100,
            'LOGISTICA - LOGISTICA Y DISTRIBUCION' => 110,
        ];
        $historial = collect();
        $id=1;
        foreach ($orden as $nombre => $prioridad) {
            $this->assertSame($prioridad, UltimoProcesoOt::prioridad($nombre));
            $historial->push($this->evento($id++, $nombre, '2026-09-17'));
        }
        $historial = $historial->reverse()->values();
        $this->assertSame(
            'LOGISTICA - LOGISTICA Y DISTRIBUCION',
            UltimoProcesoOt::ultimo($historial)->proceso
        );
    }

    public function test_soporta_proceso_sin_prefijo_y_espacios_extra(): void
    {
        $this->assertSame(70, UltimoProcesoOt::prioridad('  costura interna  '));
        $this->assertSame(55, UltimoProcesoOt::prioridad('PRODUCCION - SERIGRAFIA '));
    }

    public function test_al_borrar_proceso_se_recalcula_sin_persistir_estado(): void
    {
        $serigrafia=$this->evento(1, 'PRODUCCION - SERIGRAFIA','2026-09-17');
        $costura=$this->evento(2, 'PRODUCCION - COSTURA INTERNA','2026-09-17');
        $this->assertSame(2, UltimoProcesoOt::ultimo(collect([$serigrafia,$costura]))->id_trazabilidad);
        $this->assertSame(1, UltimoProcesoOt::ultimo(collect([$serigrafia]))->id_trazabilidad);
        $this->assertNull(UltimoProcesoOt::ultimo(collect()));
    }
}
