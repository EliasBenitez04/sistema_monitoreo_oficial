<!DOCTYPE html>
<html lang="es">

<head>
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta charset="utf-8">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-size: 14px;
            background: #fff;
            margin: 0 auto;
            width: 210mm;
            padding: 25px;
        }

        thead th {
            text-align: center !important;
            vertical-align: top !important;
        }

        .header {
            border-bottom: 3px solid #0051a3;
            margin-bottom: 15px;
        }

        .header-logo img {
            max-height: 135px;
        }

        .invoice-title {
            font-weight: bold;
            font-size: 22px;
            color: #0051a3;
        }

        table th {
            background-color: #0051a3;
            color: #fff;
        }

        .totales-table th {
            background-color: #0051a3;
        }

        .firma-section {
            margin-top: 60px;
        }

        .firma {
            border-top: 1px solid #000;
            width: 250px;
            text-align: center;
            font-size: 12px;
        }

        .compact-card {
            font-size: 14px;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        .compact-card .row {
            margin-bottom: 3px;
        }

        .compact-card strong {
            color: #000000;
        }

        @media print {

            @page {
                size: legal portrait;
                margin: 18mm 15mm 22mm 15mm;
            }

            body {
                transform: scale(0.88);
                transform-origin: top left;
            }

            thead {
                color: #000 !important;
                text-align: center !important;
                vertical-align: top !important;
            }

            th,
            td {
                color: #000 !important;
                border-color: #000 !important;
            }

            .table {
                border-collapse: collapse !important;
                width: 100% !important;
            }

            .table th,
            .table td {
                border: 1px solid #000 !important;
            }

            tr {
                page-break-inside: avoid;
            }

            .btn-print {
                display: none !important;
            }

            .header {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
            }

            .header-logo {
                order: 1 !important;
                margin-left: 0 !important;
                display: flex !important;
                justify-content: flex-start !important;
            }

            .header-info {
                order: 2 !important;
                text-align: right !important;
                margin-left: auto !important;
            }

            .totales-table {
                width: 320px !important;
                /* más chica */
                font-size: 12px !important;
                margin-left: auto !important;
                /* mantiene a la derecha */
            }

            .totales-table th,
            .totales-table td {
                padding: 4px !important;
            }

            .totales-table strong {
                font-size: 12px !important;
            }
        }
    </style>

    <title>Pedido {{ $pedido->nro_pedido }}</title>
</head>

<body>

    @php
        $hayDescuento = $detalle->contains(function ($d) {
            return $d->det_descuento > 0;
        });
    @endphp

    <table style="width:100%; border-collapse:collapse;">

        <thead class="print-header">
            <tr>
                <td style="border:none; padding:0;">

                    <div class="row header align-items-center mb-3">
                        <div class="col-6 header-logo">
                            <img src="{{ asset('storage/logos/logo_gts.jpeg') }}" alt="Logo">
                        </div>
                        <div class="col-6 text-right header-info">
                            <p class="mb-1"><strong>SEDAMA S.A.</strong></p>
                            <p class="mb-1">Lomas Valentina casi Sargento González</p>
                            <p class="mb-1">R.U.C. 80093399-0</p>
                            <p class="mb-0 fs-5 fw-semibold">
                                <i class="bi bi-telephone-fill me-2"></i>
                                021 513 824 | 0984-261-267
                            </p>
                        </div>
                    </div>

                    <div class="text-center mb-3">
                        <span class="invoice-title">NOTA DE PEDIDO</span>
                        <hr style="width: 40%; border: 1px solid #0051a3;">
                    </div>

                </td>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td style="border:none; padding:0;">

                    <!-- DATOS CLIENTE -->
                    <div class="cliente-info compact-card">
                        <div class="row mb-1">
                            <div class="col-4"><strong>N° Pedido:</strong> {{ $pedido->nro_pedido }}</div>
                            <div class="col-4"><strong>Fecha Emisión:</strong>
                                {{ \Carbon\Carbon::parse($pedido->ped_fecha)->format('d/m/Y') }}</div>
                            <div class="col-4"><strong>Condición:</strong> {{ $pedido->condicion }}</div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-4"><strong>Cliente:</strong> {{ $pedido->cliente }}</div>
                            <div class="col-4"><strong>CI / RUC:</strong> {{ $pedido->cli_ci }}</div>
                            <div class="col-4"><strong>Teléfono:</strong> {{ $pedido->cli_telefono }}</div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-6"><strong>Dirección:</strong> {{ $pedido->cli_direccion }}</div>
                        </div>
                        @if ($pedido->condicion == 'CREDITO')
                            <div class="row mb-1">
                                <div class="col-4"><strong>Intervalo:</strong> {{ $pedido->intervalo }} días</div>
                                <div class="col-4"><strong>Cantidad de Cuotas:</strong> {{ $pedido->cant_cuotas }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- DETALLE DEL PEDIDO -->
                    <h5 class="mt-2 mb-2" style="font-size: 16px;">Detalle de Artículos</h5>

                    <table class="table table-bordered table-sm" style="font-size: 12px; line-height: 1.1;">
                        <thead class="text-center">
                            <tr>
                                <th style="width: 70px; padding:3px;">Código</th>
                                <th style="padding:3px;">Descripción</th>
                                <th style="width: 45px; padding:3px;">Cant.</th>
                                <th style="width: 90px; padding:3px;">P. Unit.</th>
                                <th style="width: 90px; padding:3px;">Subtotal</th>

                                @if ($hayDescuento)
                                    <th style="width: 95px; padding:3px;">C/ Desc.</th>
                                @endif
                            </tr>
                        </thead>
                        @php
                            $totalArticulos = $detalle->sum('det_cantidad');
                        @endphp
                        <tbody>
                            @foreach ($detalle as $d)
                                @php
                                    $factor = 1 - $d->det_descuento / 100;

                                    if ($d->det_cantidad > 0 && $factor > 0) {
                                        $precioUnitario = $d->det_subtotal / ($factor * $d->det_cantidad);
                                        $subtotalSinDesc = $d->det_subtotal / $factor;
                                    } else {
                                        $precioUnitario = 0;
                                        $subtotalSinDesc = 0;
                                    }
                                @endphp

                                <tr>
                                    <td class="text-center p-1">{{ $d->art_codigo }}</td>
                                    <td class="p-1">{{ $d->art_descripcion }}</td>
                                    <td class="text-center p-1">{{ $d->det_cantidad }}</td>

                                    <td class="text-center p-1">
                                        {{ number_format($precioUnitario, 0, ',', '.') }}
                                    </td>

                                    <td class="text-center p-1">
                                        {{ number_format($subtotalSinDesc, 0, ',', '.') }}
                                    </td>

                                    @if ($hayDescuento)
                                        <td class="text-center p-1">
                                            {{ number_format($d->det_subtotal, 0, ',', '.') }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            <tr style="font-weight: bold; background:#f2f2f2;">
                                <td colspan="2" class="text-right">TOTAL ARTÍCULOS:</td>
                                <td class="text-center">{{ $totalArticulos }}</td>
                                <td></td>
                                <td></td>

                                @if ($hayDescuento)
                                    <td></td>
                                @endif
                            </tr>
                        </tbody>
                    </table>

                    <!-- TOTAL -->
                    <table class="table table-bordered totales-table w-50 ml-auto">

                        <tbody>
                            @if ($hayDescuento)
                                <tr>
                                    <th>Total Sin Descuento</th>
                                    <td class="text-right">
                                        <strong>{{ number_format(
                                            $detalle->sum(function ($d) {
                                                $factor = 1 - $d->det_descuento / 100;
                                                return $factor > 0 ? $d->det_subtotal / $factor : 0;
                                            }),
                                        ) }}
                                            Gs.</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Descuento {{ $d->det_descuento }}%</th>
                                    <td class="text-right">
                                        <strong>{{ number_format(
                                            $detalle->sum(function ($d) {
                                                $factor = 1 - $d->det_descuento / 100;
                                                return $factor > 0 ? $d->det_subtotal / $factor - $d->det_subtotal : $d->det_subtotal;
                                            }),
                                            0,
                                            ',',
                                            '.',
                                        ) }}
                                            Gs.</strong>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Total Pedido</th>
                                <td class="text-right"><strong>{{ number_format($pedido->ped_total, 0, ',', '.') }}
                                        Gs.</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="text-center mt-3">
                        <button onclick="window.print()" class="btn btn-primary btn-print">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>

                </td>
            </tr>
        </tbody>

    </table>

</body>

</html>
