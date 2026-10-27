<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MayoristaFacturaSoloConsultaTest extends TestCase
{
    private function plantilla(): string
    {
        $contenido = file_get_contents(__DIR__ . '/../../resources/views/ventas/mayoristas_matriz_factura.blade.php');
        $this->assertNotFalse($contenido);

        return $contenido;
    }

    public function test_factura_conserva_codigos_descripciones_cantidades_y_remisiones_en_consulta(): void
    {
        $vista = $this->plantilla();

        $this->assertStringContainsString('Detalle de factura mayorista', $vista);
        $this->assertStringContainsString('Códigos y cantidades facturadas', $vista);
        $this->assertStringContainsString('{{ $item->codigo }}', $vista);
        $this->assertStringContainsString('{{ $item->descripcion', $vista);
        $this->assertStringContainsString('Cantidad facturada', $vista);
        $this->assertStringContainsString('Remisiones para consulta por código completo', $vista);
        $this->assertStringContainsString('Cantidad remitida', $vista);
        $this->assertStringContainsString('Ventas detalladas de esta factura', $vista);
    }

    public function test_la_vista_no_muestra_opciones_de_vinculacion_asignacion_ni_reversion(): void
    {
        $vista = $this->plantilla();

        foreach ([
            "route('ventas.mayoristas.vincular'",
            "route('ventas.mayoristas.desvincular'",
            'name="remision_id"',
            'Asignar a esta factura',
            'Faltan vincular',
            'Vinculadas con evidencia',
            'Remisiones asignadas a esta factura',
            'Faltan asignar',
            'Ya vinculado',
            '$item->validado',
            '$item->pendiente',
            'maym-form',
        ] as $textoProhibido) {
            $this->assertStringNotContainsString($textoProhibido, $vista);
        }
    }
}
