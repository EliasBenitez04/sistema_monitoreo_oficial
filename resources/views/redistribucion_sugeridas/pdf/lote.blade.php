<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Lote {{ $lote->numero_lote }}
    </title>

    <style>
        @page {
            margin: 30px 30px 40px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #334155;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* =====================================================
           HEADER
        ====================================================== */

        .header {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
        }

        .header-left {
            width: 65%;
        }

        .header-right {
            width: 35%;
            text-align: right;
        }

        .system-name {
            font-size: 16px;
            font-weight: bold;
            color: #172033;
        }

        .document-title {
            font-size: 9px;
            color: #64748b;
        }

        .document-number {
            font-size: 10px;
            font-weight: bold;
            color: #2563eb;
        }

        .document-date {
            font-size: 7px;
            color: #64748b;
        }


        /* =====================================================
           SECCIONES
        ====================================================== */

        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #172033;
            border-left: 3px solid #2563eb;
            padding-left: 6px;
            margin: 12px 0 6px 0;
        }


        /* =====================================================
           RESUMEN
        ====================================================== */

        .summary {
            width: 100%;
            margin-bottom: 5px;
        }

        .summary td {
            width: 25%;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 7px;
            vertical-align: top;
        }

        .summary-label {
            color: #64748b;
            font-size: 6px;
            font-weight: bold;
        }

        .summary-value {
            color: #172033;
            font-size: 9px;
            font-weight: bold;
        }

        .status {
            padding: 3px 6px;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            font-size: 7px;
            font-weight: bold;
        }


        /* =====================================================
           TABLA
        ====================================================== */

        .table-wrapper {
            width: 100%;
            margin-top: 5px;
        }

        .detalles {
            width: 100%;
            table-layout: fixed;
        }

        .detalles thead {
            display: table-header-group;
        }

        /*
        Importante:
        No usamos page-break-inside: avoid en cada fila.
        Esto reduce bastante el consumo de memoria de Dompdf.
        */

        .detalles th {
            background: #172033;
            color: #ffffff;
            padding: 5px 4px;
            text-align: left;
            font-size: 6px;
            font-weight: bold;
        }

        .detalles td {
            border: 1px solid #e2e8f0;
            padding: 4px;
            font-size: 7px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .center {
            text-align: center;
        }

        .codigo {
            font-weight: bold;
            color: #172033;
        }

        .descripcion {
            color: #334155;
        }

        .cantidad {
            font-weight: bold;
            color: #2563eb;
        }

        .estado {
            font-size: 6px;
            font-weight: bold;
        }


        /* =====================================================
           TOTALES
        ====================================================== */

        .totales {
            width: 100%;
            margin-top: 10px;
        }

        .totales td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            background: #f8fafc;
        }

        .total-label {
            text-align: right;
            color: #64748b;
            font-weight: bold;
        }

        .total-value {
            width: 70px;
            text-align: center;
            color: #172033;
            font-weight: bold;
            font-size: 9px;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .footer {
            margin-top: 15px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6px;
            color: #94a3b8;
        }

        .footer strong {
            color: #64748b;
        }
    </style>

</head>


<body>


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="header">

        <table class="header-table">

            <tr>

                <td class="header-left">

                    <div class="system-name">
                        GESTIÓN DE REDISTRIBUCIÓN
                    </div>

                    <div class="document-title">
                        Comprobante de movimiento de redistribución
                    </div>

                </td>


                <td class="header-right">

                    <div class="document-number">
                        {{ $lote->numero_lote }}
                    </div>

                    <div class="document-date">

                        Generado:
                        {{ \Carbon\Carbon::parse($lote->fecha_generacion)->format('d/m/Y H:i') }}

                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- =====================================================
         INFORMACIÓN DEL LOTE
    ====================================================== --}}

    <div class="section-title">
        INFORMACIÓN DEL LOTE
    </div>


    <table class="summary">

        <tr>

            <td>

                <span class="summary-label">
                    NÚMERO DE LOTE
                </span>

                <br>

                <span class="summary-value">
                    {{ $lote->numero_lote }}
                </span>

            </td>


            <td>

                <span class="summary-label">
                    ESTADO
                </span>

                <br>

                <span class="status">
                    {{ $lote->estado }}
                </span>

            </td>


            <td>

                <span class="summary-label">
                    TOTAL TRANSFERENCIAS
                </span>

                <br>

                <span class="summary-value">
                    {{ $lote->total_transferencias }}
                </span>

            </td>


            <td>

                <span class="summary-label">
                    TOTAL PRODUCTOS
                </span>

                <br>

                <span class="summary-value">
                    {{ $lote->total_productos }}
                </span>

            </td>

        </tr>

    </table>


    {{-- =====================================================
         DETALLE
    ====================================================== --}}

    <div class="section-title">
        DETALLE DE TRANSFERENCIAS
    </div>


    <div class="table-wrapper">

        <table class="detalles">

            <thead>

                <tr>

                    <th width="4%" class="center">
                        #
                    </th>

                    <th width="15%">
                        Código
                    </th>

                    <th width="25%">
                        Descripción
                    </th>

                    <th width="18%">
                        Origen
                    </th>

                    <th width="18%">
                        Destino
                    </th>

                    <th width="9%" class="center">
                        Cantidad
                    </th>

                    <th width="11%">
                        Estado
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse ($lote->detalles as $detalle)
                    <tr>

                        <td class="center">
                            {{ $loop->iteration }}
                        </td>


                        <td class="codigo">
                            {{ $detalle->codigo ?? '-' }}
                        </td>


                        <td class="descripcion">
                            {{ $detalle->descripcion ?? '-' }}
                        </td>


                        <td>
                            {{ optional($detalle->origen)->suc_descri ?? '-' }}
                        </td>


                        <td>
                            {{ optional($detalle->destino)->suc_descri ?? '-' }}
                        </td>


                        <td class="center cantidad">
                            {{ $detalle->cantidad ?? 0 }}
                        </td>


                        <td class="estado">
                            {{ $detalle->estado ?? '-' }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7" class="center">
                            No existen transferencias registradas en este lote.
                        </td>

                    </tr>
                @endforelse

            </tbody>

        </table>

    </div>


    {{-- =====================================================
         TOTALES
    ====================================================== --}}

    <table class="totales">

        <tr>

            <td class="total-label">
                Total de transferencias
            </td>

            <td class="total-value">
                {{ $lote->detalles->count() }}
            </td>


            <td class="total-label">
                Total unidades
            </td>

            <td class="total-value">
                {{ $lote->detalles->sum('cantidad') }}
            </td>

        </tr>

    </table>


    {{-- =====================================================
         FOOTER
    ====================================================== --}}

    <div class="footer">

        Documento generado automáticamente por el sistema de
        <strong>Gestión de Redistribución</strong>.

    </div>


</body>

</html>
