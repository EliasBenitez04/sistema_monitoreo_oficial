<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\StockVentasSucursal;
use App\Models\RedistribucionRemision;

class LoteRedistribucionExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithCustomStartCell,
    WithEvents
{
    protected $lote;

    protected $descripciones = [];

    public function __construct($lote)
    {
        $this->lote = $lote;

        /*
         * ============================================================
         * CARGAR DESCRIPCIONES
         * ============================================================
         */

        $codigos = $lote->detalles
            ->pluck('codigo')
            ->filter()
            ->unique()
            ->values();

        if ($codigos->isNotEmpty()) {

            $this->descripciones = StockVentasSucursal::whereIn(
                'codigo',
                $codigos
            )
                ->whereNotNull('grupo_plan')
                ->where('grupo_plan', '<>', '')
                ->get([
                    'codigo',
                    'grupo_plan'
                ])
                ->groupBy('codigo')
                ->map(function ($items) {

                    return $items->first()->grupo_plan;
                })
                ->toArray();
        }
    }

    /*
     * ================================================================
     * COLECCIÓN
     * ================================================================
     */

    public function collection()
    {
        return $this->lote->detalles;
    }

    /*
     * ================================================================
     * CELDA INICIAL
     * ================================================================
     */

    public function startCell(): string
    {
        return 'A4';
    }

    /*
     * ================================================================
     * ENCABEZADOS
     * ================================================================
     */

    public function headings(): array
    {
        return [
            'Sucursal Origen',
            'Sucursal Destino',
            'Código',
            'Descripción',
            'Cantidad Pedida',
            'Cantidad Transferida',
            'Diferencia',
            'Resultado',
            'Remisiones',
            'Estado',
        ];
    }

    /*
     * ================================================================
     * MAPEO
     * ================================================================
     */

    public function map($detalle): array
    {
        $codigo = $detalle->codigo ?? '';

        /*
         * ============================================================
         * DESCRIPCIÓN
         * ============================================================
         */

        $descripcion = $this->descripciones[$codigo]
            ?? 'SIN DESCRIPCIÓN';

        /*
         * ============================================================
         * CANTIDAD PEDIDA
         * ============================================================
         */

        $cantidadPedida = (int) ($detalle->cantidad ?? 0);

        /*
         * ============================================================
         * OBTENER REMISIONES
         * ============================================================
         */

        $remisiones = RedistribucionRemision::where(
            'detalle_id',
            $detalle->id
        )
            ->orderBy('fecha_remision')
            ->orderBy('id')
            ->get();

        /*
         * ============================================================
         * TOTAL TRANSFERIDO
         * ============================================================
         */

        $cantidadTransferida = (int) $remisiones->sum(
            'cantidad_transferida'
        );

        /*
         * ============================================================
         * DIFERENCIA
         *
         * Positivo = falta
         * 0        = completo
         * Negativo = excedente
         * ============================================================
         */

        $diferencia = $cantidadPedida - $cantidadTransferida;

        /*
         * ============================================================
         * RESULTADO
         * ============================================================
         */

        if ($cantidadTransferida > $cantidadPedida) {

            $resultado = 'EXCEDENTE';
        } elseif ($cantidadTransferida == $cantidadPedida) {

            $resultado = 'COMPLETO';
        } elseif ($cantidadTransferida > 0) {

            $resultado = 'PARCIAL';
        } else {

            $resultado = 'PENDIENTE';
        }

        /*
         * ============================================================
         * LISTA DE REMISIONES
         * ============================================================
         *
         * Ejemplo:
         *
         * A-2415 (6)
         * A-2416 (4)
         * ============================================================
         */

        $listaRemisiones = $remisiones
            ->map(function ($remision) {

                $serie = trim((string) $remision->serie);

                $numero = $remision->numero_remision;

                $cantidad = $remision->cantidad_transferida;

                return $serie .
                    '-' .
                    $numero .
                    ' (' .
                    $cantidad .
                    ')';
            })
            ->implode("\n");

        if ($listaRemisiones === '') {
            $listaRemisiones = 'SIN REMISIÓN';
        }

        /*
         * ============================================================
         * RESULTADO FINAL
         * ============================================================
         */

        return [

            $detalle->origen->suc_descri ?? '',

            $detalle->destino->suc_descri ?? '',

            $codigo,

            $descripcion,

            $cantidadPedida,

            $cantidadTransferida,

            $diferencia,

            $resultado,

            $listaRemisiones,

            $detalle->estado ?? '',
        ];
    }

    /*
     * ================================================================
     * DISEÑO DEL EXCEL
     * ================================================================
     */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /*
                 * ====================================================
                 * TÍTULO
                 * ====================================================
                 */

                $sheet->mergeCells('A1:J1');

                $sheet->setCellValue(
                    'A1',
                    'LOTE DE REDISTRIBUCIÓN'
                );

                /*
                 * ====================================================
                 * NÚMERO DE LOTE
                 * ====================================================
                 */

                $sheet->mergeCells('A2:J2');

                $sheet->setCellValue(
                    'A2',
                    'N° Lote: ' . $this->lote->numero_lote
                );

                /*
                 * ====================================================
                 * ESTILO TÍTULO
                 * ====================================================
                 */

                $sheet->getStyle('A1:J1')->applyFromArray([

                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],

                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                /*
                 * ====================================================
                 * ESTILO LOTE
                 * ====================================================
                 */

                $sheet->getStyle('A2:J2')->applyFromArray([

                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],

                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                /*
                 * ====================================================
                 * ENCABEZADOS
                 * ====================================================
                 */

                $sheet->getStyle('A4:J4')->applyFromArray([

                    'font' => [
                        'bold' => true,
                    ],

                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                        ],
                    ],
                ]);

                /*
                 * ====================================================
                 * ÚLTIMA FILA
                 * ====================================================
                 */

                $ultimaFila =
                    4 +
                    $this->lote->detalles->count();

                /*
                 * ====================================================
                 * BORDES
                 * ====================================================
                 */

                if ($ultimaFila >= 4) {

                    $sheet
                        ->getStyle(
                            'A4:J' . $ultimaFila
                        )
                        ->applyFromArray([

                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => 'thin',
                                ],
                            ],

                            'alignment' => [
                                'vertical' => 'center',
                            ],
                        ]);
                }

                /*
                 * ====================================================
                 * ALINEACIÓN
                 * ====================================================
                 */

                if ($ultimaFila >= 5) {

                    // Código

                    $sheet
                        ->getStyle(
                            'C5:C' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setHorizontal('center');

                    // Cantidad pedida

                    $sheet
                        ->getStyle(
                            'E5:E' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setHorizontal('center');

                    // Transferida

                    $sheet
                        ->getStyle(
                            'F5:F' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setHorizontal('center');

                    // Diferencia

                    $sheet
                        ->getStyle(
                            'G5:G' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setHorizontal('center');

                    // Resultado

                    $sheet
                        ->getStyle(
                            'H5:H' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setHorizontal('center');

                    // Estado

                    $sheet
                        ->getStyle(
                            'J5:J' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setHorizontal('center');

                    // Remisiones

                    $sheet
                        ->getStyle(
                            'I5:I' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setVertical('top');
                }

                /*
                 * ====================================================
                 * ANCHOS
                 * ====================================================
                 */

                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(55);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(14);
                $sheet->getColumnDimension('H')->setWidth(16);
                $sheet->getColumnDimension('I')->setWidth(30);
                $sheet->getColumnDimension('J')->setWidth(18);

                /*
                 * ====================================================
                 * ALTURA
                 * ====================================================
                 */

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(4)->setRowHeight(30);

                /*
                 * ====================================================
                 * FORMATO NUMÉRICO
                 * ====================================================
                 */

                if ($ultimaFila >= 5) {

                    $sheet
                        ->getStyle(
                            'E5:G' . $ultimaFila
                        )
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                /*
                 * ====================================================
                 * DESCRIPCIÓN
                 * ====================================================
                 */

                if ($ultimaFila >= 5) {

                    $sheet
                        ->getStyle(
                            'D5:D' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setWrapText(true);

                    /*
                     * REMISIONES
                     */

                    $sheet
                        ->getStyle(
                            'I5:I' . $ultimaFila
                        )
                        ->getAlignment()
                        ->setWrapText(true);
                }

                /*
                 * ====================================================
                 * FILTROS
                 * ====================================================
                 */

                $sheet->setAutoFilter(
                    'A4:J' . $ultimaFila
                );

                /*
                 * ====================================================
                 * CONGELAR ENCABEZADOS
                 * ====================================================
                 */

                $sheet->freezePane('A5');
            },
        ];
    }
}
