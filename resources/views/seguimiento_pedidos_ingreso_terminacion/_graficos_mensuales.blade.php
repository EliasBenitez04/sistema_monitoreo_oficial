{{-- Gráficos mensuales de salidas TERMINACION - TERMINACION (pedidos IT).
    Se reutiliza la presentación del dashboard Producción sin compartir sus datos. --}}
<style>
.prod-section-card {
            border: 1px solid #e5eaf1 !important;
            border-radius: 11px !important;
            overflow: hidden;
            box-shadow: 0 3px 13px rgba(20, 45, 72, .04);
            margin-bottom: 18px
        }
.prod-section-card>.card-header {
            background: #f8fafc !important;
            border-bottom: 1px solid #e5eaf1;
            padding: 15px 18px;
            font-weight: 700;
            color: #24394f
        }
.prod-section-card>.card-header strong {
            font-size: 1rem
        }
.prod-chart-card>.card-header {
            background: #fff !important;
            padding: 14px 19px;
            gap: 12px
        }
.prod-chart-card .prod-chart-switch .btn {
            min-width: 75px;
            font-size: .86rem;
            font-weight: 600
        }
.prod-chart-card .prod-chart-switch .btn.active {
            box-shadow: none
        }
.prod-day-filter {
            gap: 7px
        }
.prod-day-filter-label {
            font-size: .76rem;
            color: #64748b;
            font-weight: 600;
            margin-right: 3px
        }
.prod-day-filter .btn {
            font-size: .79rem;
            font-weight: 700
        }
.prod-day-filter .btn.active {
            box-shadow: none
        }
.prod-day-filter {
                width: 100%;
                justify-content: flex-end
            }
.prod-chart-card .prod-chart-caption {
            font-size: .8rem;
            color: #64748b
        }
.prod-chart-legend {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: .82rem;
            color: #536579;
            margin: 1px 0 10px
        }
.prod-chart-key {
            display: inline-block;
            vertical-align: middle;
            width: 13px;
            height: 13px;
            border-radius: 3px;
            margin-right: 6px
        }
.prod-chart-svg {
            display: block;
            width: 100%;
            max-height: 350px
        }
.prod-chart-canvas {
            min-height: 260px;
            background: #fff;
            position: relative
        }
.prod-chart-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px
        }
.prod-chart-summary-item {
            border: 1px solid #e3ebf3;
            background: #f9fbfd;
            border-radius: 9px;
            padding: 11px 14px;
            min-width: 0
        }
.prod-chart-summary-item small {
            display: block;
            color: #63758b;
            font-size: .72rem;
            margin-bottom: 3px
        }
.prod-chart-summary-item strong {
            font-size: 1.15rem;
            color: #23364b;
            font-weight: 800
        }
.prod-chart-summary-item strong.is-positive {
            color: #15815c
        }
.prod-chart-summary-item strong.is-negative {
            color: #ba4141
        }
.prod-chart-tooltip {
            position: absolute;
            z-index: 5;
            pointer-events: none;
            background: #183153;
            color: #fff;
            border-radius: 8px;
            padding: 11px 13px;
            font-size: .76rem;
            line-height: 1.7;
            white-space: pre-line;
            box-shadow: 0 6px 18px rgba(15, 35, 55, .22);
            max-width: 260px
        }
.prod-chart-tooltip[hidden] {
            display: none
        }
.prod-chart-chartlabel {
            font-size: .83rem;
            font-weight: 700;
            color: #28445f
        }
.prod-chart-zoom-note {
            font-size: .73rem;
            color: #64748b;
            text-align: center;
            margin-top: 5px
        }
.prod-chart-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
.prod-chart-summary-item {
                padding: 9px
            }
.prod-chart-summary-item strong {
                font-size: 1rem
            }
.prod-chart-donut-svg {
            display: block;
            margin: auto;
            max-width: 280px;
            width: 100%;
            height: auto
        }
.prod-chart-donut-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 22px;
            margin: 10px 0 8px;
            font-size: .88rem
        }
.prod-chart-empty {
            color: #64748b;
            text-align: center;
            padding: 110px 10px
        }
.prod-chart-card>.card-header {
                padding: 12px
            }
.prod-chart-svg {
                min-width: 490px
            }
.prod-chart-scroll {
                overflow-x: auto
            }
.prod-chart-card .prod-chart-switch .btn {
                min-width: 65px
            }
</style>

            {{-- Gráfico interactivo del mismo período y los mismos indicadores usados en la tabla. --}}
            @php
                $serieGraficaProduccion = $diasGraficoIt
                    ->filter(function ($dia) {
                        return $dia->registrado && $dia->proyeccion !== null;
                    })
                    ->map(function ($dia) {
                        return [
                            'fecha' => $dia->fecha,
                            'habil' => (bool) $dia->habil,
                            'proyeccion' => (int) $dia->proyeccion,
                            'anterior' => $dia->proyeccion_anterior !== null ? (int) $dia->proyeccion_anterior : null,
                            'prendas' => (int) $dia->prendas,
                            'acumulado' => (int) $dia->acumulado,
                            'promedio' => $dia->promedio !== null ? (float) $dia->promedio : null,
                            'prendas_ayer' => $dia->prendas_anterior !== null ? (int) $dia->prendas_anterior : null,
                            'dias_habiles' => (int) $dia->dias_transcurridos,
                        ];
                    })
                    ->values()
                    ->all();
            @endphp
            <div class="card prod-section-card prod-chart-card" id="ingresoTerminacionGraficos">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <strong><i class="fas fa-chart-pie text-primary mr-2"></i>Gráficos de Ingreso Terminación</strong>
                        <div class="prod-chart-caption font-weight-normal mt-1">Comparación de salidas T-T contra el
                            promedio diario y seguimiento de la proyección del mes.</div>
                    </div>
                    <div id="prodFiltroTipoDia" class="d-flex align-items-center flex-wrap prod-day-filter ml-auto mr-2"
                        role="group" aria-label="Filtrar fechas del gráfico">
                        <span class="prod-day-filter-label">Días:</span>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de día">
                            <button type="button" class="btn btn-primary active prod-day-filter-button"
                                data-filtro-dia="habil" aria-pressed="true"
                                title="Mostrar solo lunes a viernes">Hábil</button>
                            <button type="button" class="btn btn-outline-primary prod-day-filter-button"
                                data-filtro-dia="no-habil" aria-pressed="false"
                                title="Incluir sábados y domingos junto con los días hábiles">No hábil</button>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm prod-chart-switch" role="group"
                        aria-label="Cambiar tipo de gráfico">
                        <button type="button" class="btn btn-primary active prod-chart-switch-button" data-grafico="diario"
                            aria-pressed="true">Ritmo diario</button>
                        <button type="button" class="btn btn-outline-primary prod-chart-switch-button"
                            data-grafico="proyeccion" aria-pressed="false">Proyección</button>
                        <button type="button" class="btn btn-outline-primary prod-chart-switch-button" data-grafico="donut"
                            aria-pressed="false">Donut</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="prodGraficoArea">
                        <div class="prod-chart-summary" aria-live="polite">
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta1">Prendas del último
                                    día</small><strong id="prodResumenValor1">—</strong></div>
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta2">Prendas del día
                                    anterior</small><strong id="prodResumenValor2">—</strong></div>
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta3">Diferencia con
                                    ayer</small><strong id="prodResumenValor3">—</strong></div>
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta4">Promedio diario
                                    acumulado</small><strong id="prodResumenValor4">—</strong></div>
                        </div>
                        <div class="prod-chart-legend">
                            <span><span class="prod-chart-key" style="background:#3d91c2"></span><span
                                    id="prodLeyendaAzul">Prendas remitidas a TERMINACION - TERMINACION</span></span>
                            <span><span class="prod-chart-key" style="background:#c8ced8"></span><span
                                    id="prodLeyendaGris">Promedio por día hábil hasta esa fecha</span></span>
                        </div>
                        <div class="prod-chart-scroll">
                            <div class="prod-chart-canvas">
                                <svg id="prodGraficoAreaSvg" class="prod-chart-svg" viewBox="0 0 960 350" role="img"
                                    aria-label="Prendas reales producidas cada fecha comparadas con el promedio de producción por día hábil a esa fecha"></svg>
                                <div class="prod-chart-tooltip" id="prodGraficoTooltip" hidden></div>
                            </div>
                        </div>
                        <p id="prodDescripcionGrafico" class="small text-muted text-center mb-0 mt-2">Azul: producción
                            registrada en esa fecha. Gris: promedio diario acumulado. Son dos cifras en prendas comparables;
                            los fines de semana sin producción figuran en cero.</p>
                        <div id="prodNotaEscala" class="prod-chart-zoom-note d-none">Escala vertical ampliada para
                            apreciar las variaciones de la proyección; el eje no comienza en cero.</div>
                    </div>
                    <div id="prodGraficoDonut" class="d-none">
                        <svg id="prodGraficoDonutSvg" class="prod-chart-donut-svg" viewBox="0 0 280 280" role="img"
                            aria-label="Distribución real de prendas registradas con pedido P y sin pedido P"></svg>
                        <div class="prod-chart-donut-legend">
                            <span><span class="prod-chart-key" style="background:#3d91c2"></span>Con pedido IT:
                                <strong>{{ number_format($resumenMensual->prendas_con_pedido, 0, ',', '.') }}</strong></span>
                            <span><span class="prod-chart-key" style="background:#c8ced8"></span>Sin pedido IT:
                                <strong>{{ number_format($resumenMensual->prendas_sin_pedido, 0, ',', '.') }}</strong></span>
                        </div>
                        <p class="small text-muted text-center mb-0">El donut muestra prendas realmente registradas hasta
                            el corte; no representa producción proyectada.</p>
                    </div>
                </div>
            </div>


@push('page_scripts')
    <script>
        (function() {
            'use strict';
            const raiz = document.getElementById('ingresoTerminacionGraficos');
            if (!raiz) return;

            const registrosBase = @json($serieGraficaProduccion);
            let filtroDia = 'habil';
            let registros = registrosBase.filter(function(fila) {
                return fila.habil === true;
            });
            const cantidadConP = Number(@json($resumenMensual->prendas_con_pedido)) || 0;
            const cantidadSinP = Number(@json($resumenMensual->prendas_sin_pedido)) || 0;
            const namespaceSVG = 'http://www.w3.org/2000/svg';
            const formato = new Intl.NumberFormat('es-PY', {
                maximumFractionDigits: 0
            });

            function elementoSvg(etiqueta, atributos, texto) {
                const elemento = document.createElementNS(namespaceSVG, etiqueta);
                Object.keys(atributos || {}).forEach(function(nombre) {
                    elemento.setAttribute(nombre, atributos[nombre]);
                });
                if (texto !== undefined && texto !== null) {
                    elemento.textContent = texto;
                }
                return elemento;
            }

            function etiquetaGrafico(numero) {
                const magnitud = Math.abs(numero);
                if (magnitud >= 1000000) return (numero / 1000000).toFixed(1) + ' M';
                if (magnitud >= 1000) return (numero / 1000).toFixed(magnitud % 1000 === 0 ? 0 : 1) + ' mil';
                return formato.format(numero);
            }

            // Los dos modos comparten fechas; las magnitudes comparadas tienen
            // siempre la misma unidad dentro de cada gráfico.
            let modoArea = 'diario';

            function resumen(entradas) {
                entradas.forEach(function(entrada, indice) {
                    const numero = indice + 1;
                    document.getElementById('prodResumenEtiqueta' + numero).textContent = entrada[0];
                    const valor = document.getElementById('prodResumenValor' + numero);
                    valor.textContent = entrada[1];
                    valor.classList.remove('is-positive', 'is-negative');
                    if (entrada[2] === 'positivo') valor.classList.add('is-positive');
                    if (entrada[2] === 'negativo') valor.classList.add('is-negative');
                });
            }

            function numeroSignado(valor) {
                if (valor === null || valor === undefined) return '—';
                return (valor > 0 ? '+' : '') + formato.format(valor);
            }

            function fechaCorta(fecha) {
                return String(fecha).slice(8, 10) + '/' + String(fecha).slice(5, 7);
            }

            function dibujarArea(modo) {
                modoArea = modo;
                const svg = document.getElementById('prodGraficoAreaSvg');
                const tooltip = document.getElementById('prodGraficoTooltip');
                if (!svg) return;
                svg.replaceChildren();
                tooltip.hidden = true;

                const esDiario = modo === 'diario';
                const azulTitulo = esDiario ?
                    'Prendas enviadas a Terminación cada día' :
                    'Proyección al cierre calculada cada día';
                const grisTitulo = esDiario ?
                    'Promedio acumulado por día hábil' :
                    'Primera proyección con movimiento';
                document.getElementById('prodLeyendaAzul').textContent = azulTitulo;
                document.getElementById('prodLeyendaGris').textContent = grisTitulo;
                document.getElementById('prodNotaEscala').classList.toggle('d-none', esDiario);
                document.getElementById('prodDescripcionGrafico').textContent = esDiario ?
                    'Azul: ingreso diario al hito IT. Gris: promedio acumulado por día hábil. Hábil muestra lunes a viernes; No hábil incluye también sábados y domingos. No cambia los cálculos.' :
                    'La línea azul indica la estimación de cada fecha visible y la gris la primera proyección con movimientos de ese tipo de día. La escala vertical ampliada está identificada.';

                svg.setAttribute('aria-label', esDiario ?
                    'Prendas ingresadas al hito de Salida a Terminación por fecha comparadas con el promedio acumulado diario' :
                    'Proyección mensual histórica comparada con la primera proyección con movimiento');

                if (!registros.length) {
                    resumen([
                        ['Último día', '—'],
                        ['Referencia', '—'],
                        ['Diferencia', '—'],
                        ['Promedio', '—']
                    ]);
                    svg.appendChild(elementoSvg('text', {
                        x: 480,
                        y: 170,
                        'text-anchor': 'middle',
                        fill: '#64748b',
                        'font-size': 19
                    }, 'No hay fechas de este tipo disponibles para graficar.'));
                    return;
                }

                // Evita usar una jornada sin movimientos como referencia de cierre.
                const indiceInicial = Math.max(0, registros.findIndex(function(fila) {
                    return Number(fila.proyeccion) > 0;
                }));
                const primero = registros[indiceInicial];
                const ultimo = registros[registros.length - 1];
                const inicial = Number(primero.proyeccion);
                // Comparar con la fecha anterior visible, no contra un fin de semana
                // oculto. No cambia los promedios ni las proyecciones calculadas.
                const ayer = registros.length > 1 ? Number(registros[registros.length - 2].prendas) : null;
                const produccion = Number(ultimo.prendas);
                const diferenciaDia = ayer === null ? null : produccion - ayer;
                const proyeccionAnterior = registros.length > 1 ?
                    Number(registros[registros.length - 2].proyeccion) : null;
                const diferenciaProyeccion = proyeccionAnterior === null ?
                    null : Number(ultimo.proyeccion) - proyeccionAnterior;

                if (esDiario) {
                    resumen([
                        ['Salida T-T · ' + fechaCorta(ultimo.fecha), formato.format(produccion)],
                        ['Fecha anterior visible', ayer === null ? '—' : formato.format(ayer)],
                        ['Diferencia vs ayer', numeroSignado(diferenciaDia),
                            diferenciaDia === null ? '' : (diferenciaDia > 0 ? 'positivo' : (diferenciaDia < 0 ?
                                'negativo' : ''))
                        ],
                        ['Promedio por día hábil', ultimo.promedio === null ? '—' : formato.format(ultimo
                            .promedio)]
                    ]);
                } else {
                    const deltaInicio = Number(ultimo.proyeccion) - inicial;
                    resumen([
                        ['Proyección actual', formato.format(ultimo.proyeccion)],
                        ['Proyección inicial', formato.format(inicial)],
                        ['Variación desde el inicio', numeroSignado(deltaInicio),
                            deltaInicio > 0 ? 'positivo' : (deltaInicio < 0 ? 'negativo' : '')
                        ],
                        ['Cambio vs fecha anterior', numeroSignado(diferenciaProyeccion),
                            diferenciaProyeccion === null ? '' : (diferenciaProyeccion > 0 ? 'positivo' : (
                                diferenciaProyeccion < 0 ? 'negativo' : ''))
                        ]
                    ]);
                }

                const azul = registros.map(function(fila) {
                    return esDiario ? Math.max(0, Number(fila.prendas)) : Math.max(0, Number(fila.proyeccion));
                });
                const gris = registros.map(function(fila, indice) {
                    return esDiario ?
                        (fila.promedio === null ? null : Math.max(0, Number(fila.promedio))) :
                        (indice >= indiceInicial ? inicial : null);
                });

                const izquierda = 88;
                const derecha = 926;
                const arriba = 25;
                const abajo = 303;
                const ancho = derecha - izquierda;
                const alto = abajo - arriba;
                const valores = azul.concat(gris.filter(function(valor) {
                    return valor !== null;
                }));
                const minimoDato = Math.min.apply(null, valores);
                const maximoDato = Math.max.apply(null, valores);
                const escala = Math.max(1, maximoDato - minimoDato);
                // Salida a Terminación real siempre desde cero. La proyección usa escala
                // ampliada y explícitamente rotulada para destacar los desvíos.
                const inferior = esDiario ? 0 : Math.max(0, minimoDato - Math.max(500, escala * 0.35));
                const superior = esDiario ? Math.max(1, maximoDato * 1.12) :
                    maximoDato + Math.max(500, escala * 0.35);
                const ejeY = function(valor) {
                    return abajo - (Math.max(inferior, Number(valor)) - inferior) /
                        (superior - inferior) * alto;
                };
                const ejeX = function(indice) {
                    return izquierda + (registros.length === 1 ?
                        ancho / 2 : indice * ancho / (registros.length - 1));
                };

                svg.appendChild(elementoSvg('text', {
                    x: izquierda,
                    y: 14,
                    fill: '#66788c',
                    'font-size': 12
                }, esDiario ? 'Prendas por día' : 'Prendas proyectadas al cierre'));

                for (let paso = 0; paso <= 4; paso++) {
                    const valor = inferior + (superior - inferior) * paso / 4;
                    const y = ejeY(valor);
                    svg.appendChild(elementoSvg('line', {
                        x1: izquierda,
                        x2: derecha,
                        y1: y,
                        y2: y,
                        stroke: '#e4ebf2',
                        'stroke-width': 1
                    }));
                    svg.appendChild(elementoSvg('text', {
                        x: izquierda - 12,
                        y: y + 4,
                        'text-anchor': 'end',
                        fill: '#718298',
                        'font-size': 13
                    }, etiquetaGrafico(valor)));
                }

                const pasoEtiquetas = Math.max(1, Math.ceil(registros.length / 9));
                registros.forEach(function(fila, indice) {
                    if (indice % pasoEtiquetas && indice !== registros.length - 1) return;
                    svg.appendChild(elementoSvg('text', {
                        x: ejeX(indice),
                        y: 334,
                        'text-anchor': 'middle',
                        fill: '#718298',
                        'font-size': 13
                    }, fechaCorta(fila.fecha)));
                });

                function trayectoria(valoresSerie) {
                    let camino = '';
                    let activos = [];
                    const tramos = [];
                    valoresSerie.forEach(function(valor, i) {
                        if (valor === null) {
                            if (activos.length) tramos.push(activos);
                            activos = [];
                        } else {
                            activos.push({
                                x: ejeX(i),
                                y: ejeY(valor)
                            });
                        }
                    });
                    if (activos.length) tramos.push(activos);

                    return tramos.map(function(puntos) {
                        camino = 'M ' + puntos[0].x + ' ' + puntos[0].y;
                        for (let i = 1; i < puntos.length; i++) {
                            const previo = puntos[i - 1];
                            const punto = puntos[i];
                            const medio = (previo.x + punto.x) / 2;
                            camino += ' C ' + medio + ' ' + previo.y + ' ' +
                                medio + ' ' + punto.y + ' ' +
                                punto.x + ' ' + punto.y;
                        }
                        if (puntos.length === 1) camino += ' L ' + puntos[0].x + ' ' + puntos[0].y;
                        return {
                            linea: camino,
                            area: camino + ' L ' + puntos[puntos.length - 1].x + ' ' + abajo +
                                ' L ' + puntos[0].x + ' ' + abajo + ' Z'
                        };
                    });
                }

                const lineaGris = trayectoria(gris);
                const lineaAzul = trayectoria(azul);

                // El gris se dibuja primero y su línea se mantiene visible
                // por encima del relleno azul, evitando la superposición confusa.
                lineaGris.forEach(function(tramo) {
                    if (esDiario) svg.appendChild(elementoSvg('path', {
                        d: tramo.area,
                        fill: '#c8d1dc',
                        'fill-opacity': .30
                    }));
                });
                lineaAzul.forEach(function(tramo) {
                    svg.appendChild(elementoSvg('path', {
                        d: tramo.area,
                        fill: '#4799c7',
                        'fill-opacity': esDiario ? .33 : .25
                    }));
                });
                lineaGris.forEach(function(tramo) {
                    svg.appendChild(elementoSvg('path', {
                        d: tramo.linea,
                        fill: 'none',
                        stroke: '#9aa9bd',
                        'stroke-width': esDiario ? 3 : 2.7,
                        'stroke-dasharray': esDiario ? '7 5' : '9 6',
                        'stroke-linecap': 'round'
                    }));
                });
                lineaAzul.forEach(function(tramo) {
                    svg.appendChild(elementoSvg('path', {
                        d: tramo.linea,
                        fill: 'none',
                        stroke: '#2386bf',
                        'stroke-width': 3.3,
                        'stroke-linecap': 'round'
                    }));
                });

                const guia = elementoSvg('line', {
                    x1: 0,
                    x2: 0,
                    y1: arriba,
                    y2: abajo,
                    stroke: '#456d93',
                    'stroke-width': 1.3,
                    'stroke-dasharray': '4 5',
                    'pointer-events': 'none',
                    visibility: 'hidden'
                });
                const marca = elementoSvg('circle', {
                    cx: 0,
                    cy: 0,
                    r: 5.5,
                    fill: '#fff',
                    stroke: '#2386bf',
                    'stroke-width': 2.8,
                    'pointer-events': 'none',
                    visibility: 'hidden'
                });
                svg.appendChild(guia);
                svg.appendChild(marca);

                function mostrarPunto(indice, px) {
                    const fila = registros[indice];
                    const proyeccionHoy = Number(fila.proyeccion);
                    const anteriorVisible = indice > 0 ? registros[indice - 1] : null;
                    const diferenciaHoy = anteriorVisible === null ? null :
                        proyeccionHoy - Number(anteriorVisible.proyeccion);
                    const cambioPrendas = anteriorVisible === null ? null :
                        Number(fila.prendas) - Number(anteriorVisible.prendas);
                    const texto = esDiario ?
                        fechaCorta(fila.fecha) + ' · Salida diaria T-T' +
                        '\nPrendas ingresadas: ' + formato.format(fila.prendas) +
                        '\nPromedio día hábil: ' + (fila.promedio === null ? '—' : formato.format(fila.promedio)) +
                        '\nFecha anterior visible: ' + (anteriorVisible === null ? '—' : formato.format(anteriorVisible
                            .prendas)) +
                        '\nDiferencia vs anterior: ' + numeroSignado(cambioPrendas) :
                        fechaCorta(fila.fecha) + ' · Proyección mensual' +
                        '\nEstimación de cierre: ' + formato.format(fila.proyeccion) +
                        '\nProyección inicial: ' + formato.format(inicial) +
                        '\nProyección anterior visible: ' + (anteriorVisible === null ? '—' : formato.format(
                            anteriorVisible.proyeccion)) +
                        '\nVariación vs anterior: ' + numeroSignado(diferenciaHoy) +
                        '\nSalida a Terminación acumulada: ' + formato.format(fila.acumulado);
                    tooltip.textContent = texto;
                    tooltip.hidden = false;
                    const anchoTip = Math.min(260, tooltip.offsetWidth || 220);
                    tooltip.style.left = Math.max(5, Math.min(px + 10,
                        svg.getBoundingClientRect().width - anchoTip - 5)) + 'px';
                    tooltip.style.top = '50px';
                    const x = ejeX(indice);
                    guia.setAttribute('x1', x);
                    guia.setAttribute('x2', x);
                    guia.setAttribute('visibility', 'visible');
                    marca.setAttribute('cx', x);
                    marca.setAttribute('cy', ejeY(azul[indice]));
                    marca.setAttribute('visibility', 'visible');
                }

                svg.onpointermove = function(evento) {
                    const rect = svg.getBoundingClientRect();
                    if (!rect.width) return;
                    const px = evento.clientX - rect.left;
                    const x = px / rect.width * 960;
                    const indice = registros.length === 1 ? 0 :
                        Math.max(0, Math.min(registros.length - 1, Math.round(
                            (x - izquierda) / ancho * (registros.length - 1)
                        )));
                    mostrarPunto(indice, px);
                };
                svg.onpointerleave = function() {
                    tooltip.hidden = true;
                    guia.setAttribute('visibility', 'hidden');
                    marca.setAttribute('visibility', 'hidden');
                };
            }

            function dibujarDonut() {
                const svg = document.getElementById('prodGraficoDonutSvg');
                if (!svg) return;
                svg.replaceChildren();

                const total = Math.max(0, cantidadConP) + Math.max(0, cantidadSinP);
                const centro = 140;
                const radio = 92;
                const perimetro = 2 * Math.PI * radio;
                const proporcion = total > 0 ? Math.max(0, cantidadConP) / total : 0;
                const base = {
                    cx: centro,
                    cy: centro,
                    r: radio,
                    fill: 'none',
                    'stroke-width': 45,
                    transform: 'rotate(-90 140 140)'
                };

                svg.appendChild(elementoSvg('circle', Object.assign({}, base, {
                    stroke: '#ecf1f6'
                })));

                if (total > 0) {
                    const longitudCon = perimetro * proporcion;
                    const longitudSin = perimetro - longitudCon;

                    if (longitudCon > 0) {
                        const segmentoP = elementoSvg('circle', Object.assign({}, base, {
                            stroke: '#3d91c2',
                            'stroke-dasharray': longitudCon + ' ' + (perimetro - longitudCon)
                        }));
                        segmentoP.appendChild(elementoSvg('title', {},
                            'Con pedido IT: ' + formato.format(cantidadConP) + ' prendas'));
                        svg.appendChild(segmentoP);
                    }
                    if (longitudSin > 0) {
                        const segmentoSin = elementoSvg('circle', Object.assign({}, base, {
                            stroke: '#c8ced8',
                            'stroke-dashoffset': -longitudCon,
                            'stroke-dasharray': longitudSin + ' ' + (perimetro - longitudSin)
                        }));
                        segmentoSin.appendChild(elementoSvg('title', {},
                            'Sin pedido IT: ' + formato.format(cantidadSinP) + ' prendas'));
                        svg.appendChild(segmentoSin);
                    }
                }

                svg.appendChild(elementoSvg('text', {
                    x: centro,
                    y: 134,
                    'text-anchor': 'middle',
                    fill: '#23364b',
                    'font-size': 30,
                    'font-weight': 800
                }, total > 0 ? (proporcion * 100).toFixed(1).replace('.', ',') + '%' : 'Sin datos'));
                svg.appendChild(elementoSvg('text', {
                    x: centro,
                    y: 158,
                    'text-anchor': 'middle',
                    fill: '#718096',
                    'font-size': 14
                }, total > 0 ? 'Con pedido IT' : 'Sin movimientos'));
            }

            dibujarArea('diario');
            dibujarDonut();

            const botonesFiltro = raiz.querySelectorAll('.prod-day-filter-button');
            botonesFiltro.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    filtroDia = boton.getAttribute('data-filtro-dia');
                    registros = registrosBase.filter(function(fila) {
                        return filtroDia === 'no-habil' || fila.habil === true;
                    });
                    botonesFiltro.forEach(function(otro) {
                        const seleccionado = otro.getAttribute('data-filtro-dia') === filtroDia;
                        otro.classList.toggle('active', seleccionado);
                        otro.classList.toggle('btn-primary', seleccionado);
                        otro.classList.toggle('btn-outline-primary', !seleccionado);
                        otro.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
                    });
                    dibujarArea(modoArea);
                });
            });

            const botones = raiz.querySelectorAll('.prod-chart-switch-button');
            botones.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const modo = boton.getAttribute('data-grafico');
                    const esDonut = modo === 'donut';
                    raiz.querySelector('#prodFiltroTipoDia').classList.toggle('d-none', esDonut);
                    raiz.querySelector('#prodGraficoArea').classList.toggle('d-none', esDonut);
                    raiz.querySelector('#prodGraficoDonut').classList.toggle('d-none', !esDonut);

                    if (!esDonut && modoArea !== modo) {
                        dibujarArea(modo);
                    }
                    botones.forEach(function(otro) {
                        const seleccionado = otro.getAttribute('data-grafico') === modo;
                        otro.classList.toggle('active', seleccionado);
                        otro.classList.toggle('btn-primary', seleccionado);
                        otro.classList.toggle('btn-outline-primary', !seleccionado);
                        otro.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
                    });
                });
            });
        })();
    </script>
@endpush
