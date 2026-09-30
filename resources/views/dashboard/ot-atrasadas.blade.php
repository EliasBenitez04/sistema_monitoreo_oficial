@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="atr-eyebrow">CONTROL GERENCIAL · PRODUCCIÓN</div>
            <h1 class="atr-title mb-1">
                <i class="fas fa-exclamation-triangle text-danger mr-2"></i>OT Atrasadas
            </h1>
            <p class="text-muted mb-0">
                Órdenes con <strong>{{ $diasAlerta }} días o más sin movimiento</strong>.
                OT finalizadas y POSTERGADAS quedan excluidas.
            </p>
        </div>
        <div class="mt-2 mt-md-0 no-print">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-print mr-1"></i> Imprimir / PDF
            </button>
            <a href="{{ route('dashboard.ot') }}" class="btn btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar OT
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Revisá los filtros:</strong>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FILTROS --}}
    <div class="card atr-card mb-4 no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="card-title font-weight-bold float-none mb-1">
                        <i class="fas fa-filter text-primary mr-2"></i>Filtros del informe
                    </h3>
                    <small class="text-muted">
                        Las fechas corresponden al <strong>último movimiento registrado</strong> de la OT.
                    </small>
                </div>
                @if($filtrosActivos > 0)
                    <span class="badge badge-primary px-3 py-2">
                        {{ $filtrosActivos }} {{ $filtrosActivos === 1 ? 'filtro activo' : 'filtros activos' }}
                    </span>
                @endif
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.ot-atrasadas') }}">
                <div class="row">
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                        <label class="atr-filter-label">Buscar OT / código / descripción</label>
                        <input type="text"
                               name="buscar"
                               value="{{ $buscar }}"
                               class="form-control"
                               placeholder="Ej. 30659 o REMERA">
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                        <label class="atr-filter-label">Proceso detenido</label>
                        <select name="proceso" class="form-control">
                            <option value="">Todos los procesos</option>
                            @foreach($procesosDisponibles as $proceso)
                                <option value="{{ $proceso->proceso }}"
                                    {{ $procesoFiltro === $proceso->proceso ? 'selected' : '' }}>
                                    {{ $proceso->proceso }} ({{ $proceso->cantidad }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                        <label class="atr-filter-label">Nivel de atraso</label>
                        <select name="nivel" class="form-control">
                            <option value="">Todos</option>
                            <option value="critica" {{ $nivelFiltro === 'critica' ? 'selected' : '' }}>Crítica · 90+ días</option>
                            <option value="grave" {{ $nivelFiltro === 'grave' ? 'selected' : '' }}>Grave · 60–89 días</option>
                            <option value="riesgo" {{ $nivelFiltro === 'riesgo' ? 'selected' : '' }}>En riesgo · 30–59 días</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
                        <label class="atr-filter-label">Últ. movimiento desde</label>
                        <input type="date"
                               name="fecha_desde"
                               value="{{ $fechaDesde ? $fechaDesde->format('Y-m-d') : '' }}"
                               class="form-control">
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
                        <label class="atr-filter-label">Últ. movimiento hasta</label>
                        <input type="date"
                               name="fecha_hasta"
                               value="{{ $fechaHasta ? $fechaHasta->format('Y-m-d') : '' }}"
                               class="form-control">
                    </div>

                    <div class="col-xl-1 col-lg-3 col-md-6 mb-3">
                        <label class="atr-filter-label">Días mín.</label>
                        <input type="number"
                               min="{{ $diasAlerta }}"
                               name="dias_desde"
                               value="{{ $diasDesde }}"
                               class="form-control"
                               placeholder="{{ $diasAlerta }}">
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
                        <label class="atr-filter-label">Días máx.</label>
                        <input type="number"
                               min="{{ $diasAlerta }}"
                               name="dias_hasta"
                               value="{{ $diasHasta }}"
                               class="form-control"
                               placeholder="Sin límite">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                        <label class="atr-filter-label">Ordenar por</label>
                        <select name="orden" class="form-control">
                            <option value="atraso_desc" {{ $orden === 'atraso_desc' ? 'selected' : '' }}>Mayor atraso primero</option>
                            <option value="atraso_asc" {{ $orden === 'atraso_asc' ? 'selected' : '' }}>Menor atraso primero</option>
                            <option value="ot_asc" {{ $orden === 'ot_asc' ? 'selected' : '' }}>Número de OT</option>
                            <option value="proceso_asc" {{ $orden === 'proceso_asc' ? 'selected' : '' }}>Proceso</option>
                        </select>
                    </div>

                    <div class="col-xl-7 col-lg-8 d-flex align-items-end justify-content-end mb-3">
                        <a href="{{ route('dashboard.ot-atrasadas') }}" class="btn btn-outline-secondary mr-2">
                            <i class="fas fa-undo mr-1"></i> Limpiar
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search mr-1"></i> Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row mb-1">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="atr-kpi is-danger">
                <div>
                    <div class="atr-kpi-label">OT atrasadas</div>
                    <div class="atr-kpi-value">{{ number_format($totalAtrasadas,0,',','.') }}</div>
                    <div class="atr-kpi-meta">{{ $diasAlerta }} días o más sin movimiento</div>
                </div>
                <div class="atr-kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="atr-kpi is-volume">
                <div>
                    <div class="atr-kpi-label">Prendas afectadas</div>
                    <div class="atr-kpi-value">{{ number_format($prendasAfectadas,0,',','.') }}</div>
                    <div class="atr-kpi-meta">Cantidad orden asociada a las OT filtradas</div>
                </div>
                <div class="atr-kpi-icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="atr-kpi is-warning">
                <div>
                    <div class="atr-kpi-label">Promedio sin movimiento</div>
                    <div class="atr-kpi-value">{{ number_format($promedioDiasAtraso,1,',','.') }}</div>
                    <div class="atr-kpi-meta">días promedio de las OT mostradas</div>
                </div>
                <div class="atr-kpi-icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="atr-kpi is-dark">
                <div>
                    <div class="atr-kpi-label">Mayor atraso</div>
                    <div class="atr-kpi-value">{{ number_format($mayorAtraso,0,',','.') }}</div>
                    <div class="atr-kpi-meta">
                        @if($totalAtrasadas > 0)
                            días · requiere revisión prioritaria
                        @else
                            sin OT atrasadas
                        @endif
                    </div>
                </div>
                <div class="atr-kpi-icon"><i class="fas fa-hourglass-end"></i></div>
            </div>
        </div>
    </div>

    {{-- SEVERIDAD --}}
    @php
        $querySinNivel = request()->except('nivel');
    @endphp
    <div class="row mb-3 no-print">
        <div class="col-lg-4 mb-3">
            <a href="{{ route('dashboard.ot-atrasadas', array_merge($querySinNivel, ['nivel' => 'critica'])) }}"
               class="atr-severity is-critical {{ $nivelFiltro === 'critica' ? 'active' : '' }}">
                <div class="atr-severity-icon"><i class="fas fa-fire"></i></div>
                <div class="flex-grow-1">
                    <span>Críticas</span>
                    <strong>{{ $criticas }}</strong>
                    <small>90 días o más</small>
                </div>
            </a>
        </div>

        <div class="col-lg-4 mb-3">
            <a href="{{ route('dashboard.ot-atrasadas', array_merge($querySinNivel, ['nivel' => 'grave'])) }}"
               class="atr-severity is-serious {{ $nivelFiltro === 'grave' ? 'active' : '' }}">
                <div class="atr-severity-icon"><i class="fas fa-exclamation"></i></div>
                <div class="flex-grow-1">
                    <span>Graves</span>
                    <strong>{{ $graves }}</strong>
                    <small>60 a 89 días</small>
                </div>
            </a>
        </div>

        <div class="col-lg-4 mb-3">
            <a href="{{ route('dashboard.ot-atrasadas', array_merge($querySinNivel, ['nivel' => 'riesgo'])) }}"
               class="atr-severity is-risk {{ $nivelFiltro === 'riesgo' ? 'active' : '' }}">
                <div class="atr-severity-icon"><i class="fas fa-clock"></i></div>
                <div class="flex-grow-1">
                    <span>En riesgo</span>
                    <strong>{{ $riesgo }}</strong>
                    <small>30 a 59 días</small>
                </div>
            </a>
        </div>
    </div>

    {{-- PROCESOS --}}
    @if($detalleProcesos->isNotEmpty())
        <div class="card atr-card mb-4">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h3 class="card-title font-weight-bold float-none mb-1">
                            <i class="fas fa-project-diagram text-primary mr-2"></i>Concentración por proceso
                        </h3>
                        <small class="text-muted">Dónde se encuentran actualmente las OT detenidas del informe.</small>
                    </div>
                    @if($procesoMasAfectado)
                        <div class="text-right mt-2 mt-md-0">
                            <small class="text-muted d-block">Mayor concentración</small>
                            <strong>{{ $procesoMasAfectado['proceso'] }} · {{ $procesoMasAfectado['cantidad'] }} OT</strong>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-body pt-2">
                <div class="row">
                    @foreach($detalleProcesos as $detalle)
                        @php
                            $queryProceso = array_merge(request()->except('proceso'), ['proceso' => $detalle['proceso']]);
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="{{ route('dashboard.ot-atrasadas', $queryProceso) }}"
                               class="atr-process {{ $procesoFiltro === $detalle['proceso'] ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="atr-process-icon"><i class="fas fa-cogs"></i></div>
                                    <span class="atr-process-share">{{ number_format($detalle['porcentaje'],1,',','.') }}%</span>
                                </div>
                                <div class="atr-process-name">{{ $detalle['proceso'] }}</div>
                                <div class="atr-process-stats">
                                    <div><strong>{{ $detalle['cantidad'] }}</strong><span>OT</span></div>
                                    <div><strong>{{ number_format($detalle['prendas'],0,',','.') }}</strong><span>Prendas</span></div>
                                    <div><strong>{{ number_format($detalle['promedio_dias'],1,',','.') }}</strong><span>Días prom.</span></div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- INFORME --}}
    <div class="card atr-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold float-none mb-1">
                    <i class="fas fa-clipboard-list text-danger mr-2"></i>Informe de OT atrasadas
                </h3>
                <small class="text-muted">
                    {{ $totalAtrasadas }} {{ $totalAtrasadas === 1 ? 'OT encontrada' : 'OT encontradas' }}
                    @if($filtrosActivos > 0) con los filtros seleccionados @endif
                </small>
            </div>
            <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
                Corte: {{ now()->format('d/m/Y') }}
            </span>
        </div>

        @if($otsAtrasadas->isEmpty())
            <div class="atr-empty">
                <i class="fas fa-check-circle"></i>
                <h4>No hay OT atrasadas con estos filtros</h4>
                <p>Probá ampliar el rango de fechas, días o quitar algún filtro.</p>
                <a href="{{ route('dashboard.ot-atrasadas') }}" class="btn btn-outline-primary no-print">
                    Mostrar todas
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 atr-table">
                    <thead>
                        <tr>
                            <th>Prioridad</th>
                            <th>OT</th>
                            <th>Código / Descripción</th>
                            <th class="text-right">Cantidad</th>
                            <th>Proceso detenido</th>
                            <th>Último movimiento</th>
                            <th class="text-center">Sin movimiento</th>
                            <th class="text-center">Avance</th>
                            <th class="text-center no-print">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($otsAtrasadas as $index => $item)
                        @php
                            $ot = $item['ot'];
                            $procesos = $item['procesos'];
                            $nivel = $item['nivel'];
                            $dias = (int) $item['dias_sin_movimiento'];
                        @endphp

                        <tr>
                            <td>
                                @if($nivel === 'critica')
                                    <span class="atr-badge is-critical">CRÍTICA</span>
                                @elseif($nivel === 'grave')
                                    <span class="atr-badge is-serious">GRAVE</span>
                                @else
                                    <span class="atr-badge is-risk">EN RIESGO</span>
                                @endif
                            </td>
                            <td><strong class="atr-ot">OT {{ $ot->nro_ot }}</strong></td>
                            <td>
                                <strong>{{ $ot->codigo }}</strong>
                                <small class="d-block text-muted">{{ $ot->descripcion }}</small>
                            </td>
                            <td class="text-right"><strong>{{ number_format($ot->cantidad_orden,0,',','.') }}</strong></td>
                            <td>
                                <strong>{{ $item['ultimo_proceso_normalizado'] }}</strong>
                                <small class="d-block text-muted">{{ $item['cantidad_procesos'] }} movimientos registrados</small>
                            </td>
                            <td>
                                <strong>{{ $item['fecha_ultimo_movimiento']->format('d/m/Y') }}</strong>
                            </td>
                            <td class="text-center">
                                <strong class="{{ $nivel === 'critica' ? 'text-danger' : ($nivel === 'grave' ? 'text-warning' : 'text-dark') }}">
                                    {{ $dias }} días
                                </strong>
                            </td>
                            <td class="text-center">
                                <strong>{{ number_format($item['avance'],0,',','.') }}%</strong>
                                <div class="progress atr-progress mt-1">
                                    <div class="progress-bar {{ $item['avance'] >= 70 ? 'bg-info' : 'bg-warning' }}"
                                         style="width:{{ min(100,$item['avance']) }}%"></div>
                                </div>
                            </td>
                            <td class="text-center no-print">
                                <button class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        data-toggle="collapse"
                                        data-target="#detalle-atraso-{{ $index }}"
                                        aria-expanded="false">
                                    <i class="fas fa-eye mr-1"></i> Ver
                                </button>
                            </td>
                        </tr>

                        <tr class="atr-detail-row no-print">
                            <td colspan="9" class="p-0 border-0">
                                <div id="detalle-atraso-{{ $index }}" class="collapse">
                                    <div class="atr-detail">
                                        <div class="row mb-3">
                                            <div class="col-lg-3 col-md-6 mb-2">
                                                <div class="atr-detail-box">
                                                    <span>Estado OT</span>
                                                    <strong>{{ $ot->estado ?: 'SIN ESTADO' }}</strong>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-2">
                                                <div class="atr-detail-box">
                                                    <span>Días sin movimiento</span>
                                                    <strong>{{ $dias }} días</strong>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-2">
                                                <div class="atr-detail-box">
                                                    <span>Avance del flujo</span>
                                                    <strong>{{ number_format($item['avance'],0,',','.') }}%</strong>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-2">
                                                <div class="atr-detail-box">
                                                    <span>Último movimiento</span>
                                                    <strong>{{ $item['fecha_ultimo_movimiento']->format('d/m/Y') }}</strong>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><i class="fas fa-route text-primary mr-2"></i>Historial de procesos</strong>
                                                <small class="d-block text-muted">Trazabilidad registrada de la OT.</small>
                                            </div>
                                            <span class="badge badge-light border">{{ count($procesos) }} movimientos</span>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered bg-white mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Proceso</th>
                                                        <th>Resultado</th>
                                                        <th>Fecha</th>
                                                        <th>Duración</th>
                                                        <th>Acumulado</th>
                                                        <th>Avance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($procesos as $numero => $proceso)
                                                    <tr class="{{ $proceso['es_suspendido'] ? 'table-secondary' : '' }}">
                                                        <td>{{ $numero + 1 }}</td>
                                                        <td>
                                                            <strong>{{ $proceso['proceso'] }}</strong>
                                                            @if($proceso['es_suspendido'])
                                                                <span class="badge badge-secondary ml-1">Suspendido</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $proceso['resultado'] !== null ? number_format($proceso['resultado'],0,',','.') : '—' }}</td>
                                                        <td>{{ $proceso['fecha']->format('d/m/Y') }}</td>
                                                        <td>
                                                            {{ $proceso['duracion_horas'] > 0
                                                                ? number_format($proceso['duracion_horas'],2,',','.') . ' h'
                                                                : '—' }}
                                                        </td>
                                                        <td>{{ number_format($proceso['horas_acumuladas'],2,',','.') }} h</td>
                                                        <td style="min-width:120px">
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress atr-progress flex-grow-1 mr-2">
                                                                    <div class="progress-bar {{ $proceso['avance_acumulado'] >= 70 ? 'bg-info' : 'bg-warning' }}"
                                                                         style="width:{{ min(100,$proceso['avance_acumulado']) }}%"></div>
                                                                </div>
                                                                <small>{{ $proceso['avance_acumulado'] }}%</small>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.atr-eyebrow{font-size:10px;font-weight:900;letter-spacing:.12em;color:#94a3b8}
.atr-title{font-size:29px;font-weight:900;color:#0f172a}
.atr-card{border:1px solid #e6ebf1;border-radius:15px;overflow:hidden;box-shadow:0 6px 20px rgba(15,23,42,.045)}
.atr-filter-label{display:block;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin-bottom:5px}
.atr-kpi{height:100%;min-height:125px;background:#fff;border:1px solid #e6ebf1;border-radius:14px;padding:18px;display:flex;justify-content:space-between;align-items:center;position:relative;overflow:hidden;box-shadow:0 5px 16px rgba(15,23,42,.04)}
.atr-kpi:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#64748b}
.atr-kpi.is-danger:before{background:#dc2626}.atr-kpi.is-volume:before{background:#7c3aed}.atr-kpi.is-warning:before{background:#f59e0b}.atr-kpi.is-dark:before{background:#0f172a}
.atr-kpi-label{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.055em;color:#94a3b8}
.atr-kpi-value{font-size:31px;line-height:1;font-weight:900;color:#0f172a;margin:8px 0 5px}
.atr-kpi-meta{font-size:10px;color:#64748b}
.atr-kpi-icon{width:46px;height:46px;border-radius:13px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#475569;font-size:18px;flex:0 0 46px}
.atr-severity{height:100%;min-height:96px;background:#fff;border:1px solid #e6ebf1;border-radius:13px;padding:15px;display:flex;gap:13px;align-items:center;color:inherit!important;text-decoration:none!important;transition:.18s ease}
.atr-severity:hover,.atr-severity.active{transform:translateY(-2px);box-shadow:0 8px 18px rgba(15,23,42,.07)}
.atr-severity.is-critical.active{border-color:#ef4444}.atr-severity.is-serious.active{border-color:#f59e0b}.atr-severity.is-risk.active{border-color:#eab308}
.atr-severity-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:0 0 42px}
.is-critical .atr-severity-icon{background:#fef2f2;color:#dc2626}.is-serious .atr-severity-icon{background:#fff7ed;color:#ea580c}.is-risk .atr-severity-icon{background:#fefce8;color:#ca8a04}
.atr-severity span{display:block;font-size:11px;font-weight:850;color:#334155}.atr-severity strong{font-size:25px;line-height:1;color:#0f172a}.atr-severity small{display:block;color:#94a3b8;font-size:10px}
.atr-process{display:block;height:100%;min-height:155px;border:1px solid #e6ebf1;border-radius:13px;padding:15px;background:#fff;text-decoration:none!important;color:inherit!important;transition:.18s ease}
.atr-process:hover,.atr-process.active{transform:translateY(-2px);border-color:#93c5fd;box-shadow:0 8px 18px rgba(15,23,42,.06)}
.atr-process-icon{width:34px;height:34px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center}
.atr-process-share{font-size:11px;font-weight:900;color:#64748b}
.atr-process-name{font-size:12px;font-weight:900;color:#0f172a;min-height:34px;margin:12px 0}
.atr-process-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.atr-process-stats div{background:#f8fafc;border-radius:8px;text-align:center;padding:7px 4px}
.atr-process-stats strong{display:block;font-size:13px;color:#0f172a}.atr-process-stats span{display:block;font-size:8px;text-transform:uppercase;color:#94a3b8;font-weight:800}
.atr-table th{background:#f8fafc;color:#64748b;font-size:9px;text-transform:uppercase;letter-spacing:.045em;white-space:nowrap;vertical-align:middle!important}
.atr-table td{font-size:12px;vertical-align:middle!important}
.atr-ot{font-size:14px;color:#0f172a;white-space:nowrap}
.atr-badge{display:inline-block;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:900;white-space:nowrap}
.atr-badge.is-critical{background:#fef2f2;color:#b91c1c}.atr-badge.is-serious{background:#fff7ed;color:#c2410c}.atr-badge.is-risk{background:#fefce8;color:#a16207}
.atr-progress{height:5px;border-radius:999px;background:#e9ecef;overflow:hidden;min-width:80px}
.atr-detail-row:hover{background:transparent!important}.atr-detail{padding:20px;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0}
.atr-detail-box{height:100%;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:11px}.atr-detail-box span{display:block;font-size:9px;text-transform:uppercase;color:#94a3b8;font-weight:900}.atr-detail-box strong{display:block;color:#0f172a;margin-top:4px}
.atr-empty{text-align:center;padding:55px 20px}.atr-empty i{font-size:45px;color:#22c55e;margin-bottom:13px}.atr-empty h4{font-weight:900;color:#0f172a}.atr-empty p{color:#64748b}
@media(max-width:767.98px){.atr-title{font-size:24px}}
@media print{
    .main-sidebar,.main-header,.content-header .no-print,.no-print{display:none!important}
    .content-wrapper{margin-left:0!important}
    .atr-card,.atr-kpi{box-shadow:none!important}
    .atr-detail-row{display:none!important}
    body{background:#fff!important}
}
</style>
@endpush
