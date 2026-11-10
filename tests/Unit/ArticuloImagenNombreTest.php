<?php

namespace Tests\Unit;

use App\Support\ArticuloImagenNombre;
use PHPUnit\Framework\TestCase;

class ArticuloImagenNombreTest extends TestCase
{
    private function interpretar(string $nombre): ?array
    {
        return ArticuloImagenNombre::leer($nombre);
    }

    public function test_reconoce_los_nombres_reales_de_las_fotografias(): void
    {
        $nombres = [
            '060615677 V39 C18727 OT28696 25-07-25 NENA REMERA MC COLECCION.jpg',
            '060615816 V39 C20494 OT28835 10-09-25 NENA REMERA MC COLECCION.jpg',
            '060615817 V69 C34958 OT28836 02-10-25 NENA REMERA MC COLECCION.jpg',
            '060615874 V42 C19725 OT28893 11-08-25 NENA REMERA MC COLECCION.jpg',
            '060615875 V39900 C18096 08-09-25 NENA REMERA MC.jpg',
            '060616028 V45 C20019 OT29047 02-10-25 NENA REMERA MC JERSEY.jpg',
            '060616029 V42 C19256 OT29048 14-10-25 NENA REMERA MC JERSEY.jpg',
            '060616030 V75 C47337 OT29049 27-10-25 NENA REMERA MC JERSEY.jpg',
            '060616031 V65 C22264 OT29050 19-11-25.jpeg',
            '060616168 V49 C23142 11-11-25.jpeg',
            '060616169 V49 C22331 OT29188 21-10-25 NENA REMERA MC JERSEY.jpg',
            '060616217 V47 C19674 OT29236 06-11-25.jpeg',
            '060616218 V 89 C40524 OT29237 17-11-25.jpeg',
            '060616219 V65 C19375 OT29238 02-12-25.jpeg',
            '060616270 V47 C19248 OT29289 19-11-25.jpeg',
            '060616275 V89 C39599 OT29294 16-12-25.jpeg',
            '060616344 V39 C16929 OT29363 14-11-25.jpeg',
            '060616345 V59 C26515 OT29364 03-12-25.jpeg',
            '060616346 V55 C22630 OT29365 21-11-25.jpeg',
            '060616347 V75 C26278 OT29366 10-12-25.jpeg',
            '060616385 V69 C26058 OT29404 11-12-25.jpeg',
            '060616386 79 C28062 OT29405 15-12-25.jpeg',
            '060616398 V49 C20387 OT29417 05-12-25.jpeg',
            '060616485 V75 C26140 OT29504 28-11-25.jpeg',
            '060616642 V45 C19828 OT29661 15-12-25.jpeg',
            '060616680 V39 C19959 OT29699 NENA REMERA MC COL.jpg',
            '100613678CV V35 C13.388 OT26697 BERMUDA ECO VARÓN 08-10-26.jpg',
            '060615677.png',
            '100613678CV.png',
            '060615677GR02.jpg',
        ];
        foreach ($nombres as $nombre) {
            $datos = $this->interpretar($nombre);
            $this->assertNotNull($datos, 'No reconocido: ' . $nombre);
        }
    }

    public function test_extrae_datos_con_fecha_antes_de_la_descripcion(): void
    {
        $datos = $this->interpretar('060615677 V39 C18727 OT28696 25-07-25 NENA REMERA MC COLECCION.jpg');
        $this->assertSame('060615677', $datos['codigo_base']);
        $this->assertSame('V39', $datos['valor_v']);
        $this->assertSame('C18727', $datos['valor_c']);
        $this->assertSame('28696', $datos['ot']);
        $this->assertSame('2025-07-25', $datos['fecha_archivo']);
        $this->assertSame('NENA REMERA MC COLECCION', $datos['descripcion_archivo']);
    }

    public function test_permite_faltantes_y_valores_variantes(): void
    {
        $sinOt = $this->interpretar('060616168 V49 C23142 11-11-25.jpeg');
        $this->assertNull($sinOt['ot']);
        $this->assertNull($sinOt['descripcion_archivo']);
        $this->assertSame('2025-11-11', $sinOt['fecha_archivo']);

        $separado = $this->interpretar('060616218 V 89 C40524 OT29237 17-11-25.jpeg');
        $this->assertSame('V89', $separado['valor_v']);

        $sinPrefijo = $this->interpretar('060616386 79 C28062 OT29405 15-12-25.jpeg');
        $this->assertSame('V79', $sinPrefijo['valor_v']);

        $sinFecha = $this->interpretar('060616680 V39 C19959 OT29699 NENA REMERA MC COL.jpg');
        $this->assertNull($sinFecha['fecha_archivo']);
        $this->assertSame('NENA REMERA MC COL', $sinFecha['descripcion_archivo']);

        $conColor = $this->interpretar('100613678CV V35 C13.388 OT26697 BERMUDA ECO VARÓN 08-10-26.jpg');
        $this->assertSame('100613678CV', $conColor['codigo_base']);
        $this->assertSame('C13.388', $conColor['valor_c']);
        $this->assertSame('BERMUDA ECO VARÓN', $conColor['descripcion_archivo']);
        $this->assertSame('2026-10-08', $conColor['fecha_archivo']);

        $conTalle = $this->interpretar('060615677GR02.jpg');
        $this->assertSame('060615677GR', $conTalle['codigo_base']);
    }

    public function test_no_interpreta_codigo_incorrecto_o_fecha_imposible(): void
    {
        $this->assertNull($this->interpretar('foto-sin-codigo.jpg'));
        $this->assertNull($this->interpretar('060615677 V39 C18727 31-02-25.jpg'));
    }
}

