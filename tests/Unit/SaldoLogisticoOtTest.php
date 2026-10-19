<?php

namespace Tests\Unit;

use App\Support\SaldoLogisticoOt;
use PHPUnit\Framework\TestCase;

class SaldoLogisticoOtTest extends TestCase
{
    public function test_ot_30443_no_cuenta_redistribuciones_como_salidas_nuevas(): void
    {
        // Originales: 359, devoluciones recibidas: 3;
        // redistribuciones: 40 (no participan de la fórmula neta).
        $saldo = SaldoLogisticoOt::calcular(360, 359, 359, 3);

        $this->assertSame(356, $saldo['remitido_neto']);
        $this->assertSame(356, $saldo['confirmado_neto']);
        $this->assertSame(356, $saldo['remitido_efectivo']);
        $this->assertSame(356, $saldo['recibido_efectivo']);
        $this->assertSame(4, $saldo['pendiente_remitir']);
        $this->assertSame(0, $saldo['en_transito']);
    }

    public function test_reenvio_original_desde_central_puede_reponer_saldo(): void
    {
        // Cuando salen nuevamente por remisión original, sí recuperan el saldo.
        $saldo = SaldoLogisticoOt::calcular(360, 363, 363, 3);
        $this->assertSame(360, $saldo['remitido_efectivo']);
        $this->assertSame(360, $saldo['recibido_efectivo']);
        $this->assertSame(0, $saldo['pendiente_remitir']);
    }

    public function test_remision_emitida_sin_recepcion_no_se_confirma(): void
    {
        $saldo = SaldoLogisticoOt::calcular(360, 359, 349, 3);
        $this->assertSame(356, $saldo['remitido_efectivo']);
        $this->assertSame(346, $saldo['recibido_efectivo']);
        $this->assertSame(10, $saldo['en_transito']);
    }

    public function test_no_acepta_salida_superior_a_producto_terminado(): void
    {
        $saldo = SaldoLogisticoOt::calcular(360, 400, 400, 0);
        $this->assertSame(360, $saldo['remitido_efectivo']);
        $this->assertSame(360, $saldo['recibido_efectivo']);
        $this->assertSame(400, $saldo['remitido_neto']);
    }

    public function test_redistribucion_sola_no_crea_salida_original(): void
    {
        // Incluso si existen remisiones internas a TUPI, sin una salida
        // original identificada no se declara una prenda nueva remitida.
        $saldo = SaldoLogisticoOt::calcular(360, 0, 0, 0);
        $this->assertSame(0, $saldo['remitido_efectivo']);
        $this->assertSame(0, $saldo['recibido_efectivo']);
    }
}
