<?php

namespace Tests\Unit;

use App\Support\MayoristaCruceDocumento;
use App\Support\MayoristaConciliacion;
use PHPUnit\Framework\TestCase;

class MayoristaCruceDocumentoTest extends TestCase
{
    private function fila($id, $serie, $numero, $codigo, $cantidad, $usadas=0): object
    {
        return (object)[
            'id'=>$id,'serie'=>$serie,'numero_remision'=>$numero,
            'cod_sucursal_salida'=>1,'cod_sucursal_destino'=>25,
            'fecha_remision'=>'2026-10-10','sucursal_salida'=>'CASA CENTRAL',
            'sucursal_destino'=>'COMERCIAL MATRIZ','sucursal_logistica'=>'COMERCIAL MATRIZ',
            'codigo'=>$codigo,'cantidad'=>$cantidad,'usadas'=>$usadas,'es_modelo'=>0,
        ];
    }

    public function test_cuatro_talles_en_una_remision_ocho_por_talle_dos_facturados_dejan_seis(): void
    {
        $codigos=['AZ01','AZ02','AZ03','AZ04'];
        $registros=collect();
        $necesidades=[];
        $porCodigo=collect();
        foreach ($codigos as $i=>$codigo) {
            $registros->push($this->fila($i+1,'1','43836',$codigo,8,2));
            $necesidades[$codigo]=2;
            $porCodigo[$codigo]=(object)['pendiente'=>0];
        }
        foreach ($registros as $registro) {
            $registro->id_ot = 30924;
        }
        $docs=MayoristaCruceDocumento::agrupar($registros,$necesidades,$porCodigo);
        $this->assertCount(1,$docs);
        $this->assertTrue($docs[0]->completo);
        $this->assertCount(1,$docs[0]->grupos_de_ot);
        $this->assertSame(4,$docs[0]->grupos_de_ot[0]->codigos);
        $this->assertSame(4,$docs[0]->coincidencias);
        $this->assertSame(32,$docs[0]->remitidas);
        $this->assertSame(8,$docs[0]->usadas);
        $this->assertSame(24,$docs[0]->disponibles);
        $this->assertSame([6,6,6,6],$docs[0]->lineas->pluck('disponible')->all());
    }

    public function test_no_junta_dos_remisiones_con_dos_talles_cada_una(): void
    {
        $filas=collect([
            $this->fila(1,'1','43836','AZ01',8),
            $this->fila(2,'1','43836','AZ02',8),
            $this->fila(3,'1','43837','AZ03',8),
            $this->fila(4,'1','43837','AZ04',8),
        ]);
        $necesidad=array_fill_keys(['AZ01','AZ02','AZ03','AZ04'],2);
        $pendiente=collect(array_fill_keys(array_keys($necesidad),(object)['pendiente'=>2]));
        $docs=MayoristaCruceDocumento::agrupar($filas,$necesidad,$pendiente);
        $this->assertCount(2,$docs);
        $this->assertSame(0,$docs->where('completo',true)->count());
        $this->assertSame(2,$docs[0]->coincidencias);
        $this->assertSame(2,$docs[1]->coincidencias);
    }

    public function test_saldo_no_se_vuelve_negativo_si_hay_diferencia_documental(): void
    {
        $pendiente=collect(['AZ01'=>(object)['pendiente'=>3]]);
        $docs=MayoristaCruceDocumento::agrupar(
            collect([$this->fila(1,'1','43836','AZ01',8,9)]),
            ['AZ01'=>3],$pendiente
        );
        $this->assertSame(0,$docs[0]->disponibles);
        $this->assertSame(0,$docs[0]->lineas[0]->maximo);
    }

    public function test_capacidad_de_la_asignacion_respetara_factura_y_remision(): void
    {
        $this->assertSame(2,MayoristaConciliacion::capacidad(2,6));
        $this->assertSame(6,MayoristaConciliacion::capacidad(7,6));
        $this->assertSame(0,MayoristaConciliacion::capacidad(0,6));
    }

    public function test_mismo_numero_con_otra_serie_no_une_documentos(): void
    {
        $docs=MayoristaCruceDocumento::agrupar(
            collect([
                $this->fila(1,'1','43836','AZ01',8),
                $this->fila(2,'2','43836','AZ02',8),
            ]),
            ['AZ01'=>2,'AZ02'=>2],
            collect([
                'AZ01'=>(object)['pendiente'=>2],
                'AZ02'=>(object)['pendiente'=>2],
            ])
        );
        $this->assertCount(2,$docs);
    }
}
