<li class="nav-item">
    <a href="{{ route('home') }}" class="nav-link {{ Request::is('home') ? 'active' : '' }}">
        <i class="nav-icon fas fa-home"></i>
        <p>Inicio</p>
    </a>
</li>


{{-- ===================== CARGA DE DATOS ===================== --}}
@php
    $menuCargaDatos = request()->routeIs(
        'lineas.*',
        'Departamentos.*',
        'ciudades.*',
        'clientes.*',
        'sucursal.*',
        'articulos.*',
        'stocks.*',
    );
@endphp


@php
    $menuConfiguracion = request()->routeIs('usuarios.*', 'permissions.*', 'roles.*');
@endphp


@php
    $menuMonitoreo = request()->routeIs('stock_ventas_sucursales.*', 'RedistribucionSugeridas.*');
@endphp


@can('pedido_compras index')

    <li class="nav-header">
        CARGA DE DATOS
    </li>

    <li class="nav-item {{ $menuCargaDatos ? 'menu-open' : '' }}">

        <a href="#" class="nav-link {{ $menuCargaDatos ? 'active' : '' }}">

            <i class="nav-icon fas fa-database"></i>

            <p>
                Gestión
                <i class="right fas fa-angle-left"></i>
            </p>

        </a>


        <ul class="nav nav-treeview">

            <li class="nav-item">
                <a href="{{ route('articulos.index') }}"
                    class="nav-link {{ request()->routeIs('articulos.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-box-open"></i>

                    <p>- Artículos</p>

                </a>
            </li>


            @can('stocks importar')
                <li class="nav-item">
                    <a href="{{ route('stocks.index') }}" class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-sync-alt"></i>

                        <p>- Importar Stock</p>

                    </a>
                </li>
            @endcan


            <li class="nav-item">
                <a href="{{ route('lineas.index') }}" class="nav-link {{ request()->routeIs('lineas.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-tshirt"></i>

                    <p>- Líneas</p>

                </a>
            </li>


            <li class="nav-item">
                <a href="{{ route('sucursal.index') }}"
                    class="nav-link {{ request()->routeIs('sucursal.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-store"></i>

                    <p>- Sucursales</p>

                </a>
            </li>


            <li class="nav-item">
                <a href="{{ route('clientes.index') }}"
                    class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-user-friends"></i>

                    <p>- Clientes</p>

                </a>
            </li>


            <li class="nav-item">
                <a href="{{ route('Departamentos.index') }}"
                    class="nav-link {{ request()->routeIs('Departamentos.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-map-marked-alt"></i>

                    <p>- Departamentos</p>

                </a>
            </li>


            <li class="nav-item">
                <a href="{{ route('ciudades.index') }}"
                    class="nav-link {{ request()->routeIs('ciudades.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-city"></i>

                    <p>- Ciudades</p>

                </a>
            </li>

        </ul>

    </li>

@endcan



{{-- ===================== PEDIDOS ===================== --}}
@can('pedido_compras index')
    <li class="nav-header">
        PEDIDOS
    </li>


    <li class="nav-item {{ request()->routeIs('pedido_compras.*') ? 'menu-open' : '' }}">

        <a href="#" class="nav-link {{ request()->routeIs('pedido_compras.*') ? 'active' : '' }}">

            <i class="nav-icon fas fa-file-invoice"></i>

            <p>
                Pedidos
                <i class="right fas fa-angle-left"></i>
            </p>

        </a>


        <ul class="nav nav-treeview">

            <li class="nav-item">

                <a href="{{ route('pedido_compras.index') }}"
                    class="nav-link {{ request()->routeIs('pedido_compras.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-angle-right"></i>

                    <p>Realizar Pedido</p>

                </a>

            </li>

        </ul>

    </li>
@endcan



{{-- ===================== IA ===================== --}}
{{-- @can('ia index')
    <li class="nav-header">INTELIGENCIA ARTIFICIAL</li>

    <li class="nav-item {{ request()->routeIs('ia.*') ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ request()->routeIs('ia.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-file-image"></i>
            <p>Procesar Imágenes <i class="right fas fa-angle-left"></i></p>
        </a>

        <ul class="nav nav-treeview">

            <li class="nav-item">
                <a href="{{ route('ia.index') }}"
                    class="nav-link {{ request()->routeIs('ia.*') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-magic"></i>

                    <p>Eliminar Fondo</p>

                </a>
            </li>

        </ul>

    </li>

@endcan --}}



{{-- ===================== OT ===================== --}}
@can('ot index')

    <li class="nav-header">
        SEGUIMIENTO OT
    </li>


    <li
        class="nav-item {{ request()->routeIs('ot.*', 'dashboard.ot', 'dashboard.ot-logistica', 'dashboard.ot-atrasadas') ? 'menu-open' : '' }}">

        <a href="#"
            class="nav-link {{ request()->routeIs('ot.*', 'dashboard.ot', 'dashboard.ot-logistica', 'dashboard.ot-atrasadas') ? 'active' : '' }}">

            <i class="nav-icon fas fa-file"></i>

            <p>
                OT
                <i class="right fas fa-angle-left"></i>
            </p>

        </a>


        <ul class="nav nav-treeview">


            @can('ot importar')
                <li class="nav-item">

                    <a href="{{ route('ot.index') }}" class="nav-link {{ request()->routeIs('ot.*') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-angle-right"></i>

                        <p>Importar Datos</p>

                    </a>

                </li>
            @endcan



            @can('ot dashboard')
                <li class="nav-item">

                    <a href="{{ route('dashboard.ot') }}"
                        class="nav-link {{ request()->routeIs('dashboard.ot') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-chart-line"></i>

                        <p>Dashboard OT</p>

                    </a>

                </li>
            @endcan



            <li class="nav-item">

                <a href="{{ route('dashboard.ot-logistica') }}"
                    class="nav-link {{ request()->routeIs('dashboard.ot-logistica') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-chart-line"></i>

                    <p>Dashboard Logística</p>

                </a>

            </li>



            <li class="nav-item">

                <a href="{{ route('dashboard.ot-atrasadas') }}"
                    class="nav-link {{ request()->routeIs('dashboard.ot-atrasadas') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-exclamation-triangle"></i>

                    <p>OT Atrasadas</p>

                </a>

            </li>



            {{-- <li class="nav-item">
                <a href="{{ route('ots.historia-general') }}"
                    class="nav-link {{ request()->routeIs('ots.historia-general') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-history"></i>

                    <p>Historia General</p>

                </a>
            </li> --}}



            {{-- <li class="nav-item">
                <a href="{{ route('dashboard.otAnalisis') }}"
                    class="nav-link {{ request()->routeIs('dashboard.otAnalisis') ? 'active' : '' }}">

                    <i class="nav-icon fas fa-chart-line"></i>

                    <p>Análisis de OT</p>

                </a>
            </li> --}}

        </ul>

    </li>

@endcan



{{-- ===================== MONITOREO ===================== --}}
@can('redistribucionsugerencia index')

    <li class="nav-header">
        MONITOREO
    </li>


    <li class="nav-item {{ $menuMonitoreo ? 'menu-open' : '' }}">

        <a href="#" class="nav-link {{ $menuMonitoreo ? 'active' : '' }}">

            <i class="nav-icon fas fa-desktop"></i>

            <p>
                MONITOREO
                <i class="right fas fa-angle-left"></i>
            </p>

        </a>


        <ul class="nav nav-treeview">


            @can('redistribucionsugerencia importar')
                <li class="nav-item">

                    <a href="{{ route('stock_ventas_sucursales.index') }}"
                        class="nav-link {{ request()->routeIs('stock_ventas_sucursales.*') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-angle-right"></i>

                        <p>Importar Datos</p>

                    </a>

                </li>
            @endcan



            @can('redistribucionsugerencia index')
                <li class="nav-item">

                    <a href="{{ route('RedistribucionSugeridas.index') }}"
                        class="nav-link {{ request()->routeIs('RedistribucionSugeridas.*') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-chart-line"></i>

                        <p>Redis. Sugerida</p>

                    </a>

                </li>
            @endcan



            @can('redistribucionsugerencia lotes')
                <li class="nav-item">

                    <a href="{{ route('RedistribucionSugeridas.lotes') }}"
                        class="nav-link {{ request()->routeIs('RedistribucionSugeridas.lotes') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-layer-group"></i>

                        <p>Gestión de lotes</p>

                    </a>

                </li>
            @endcan

        </ul>

    </li>

@endcan



{{-- @can('redistribucionsugerencia index') --}}

<li class="nav-header">
    AUDITORIA
</li>


<li class="nav-item {{ request()->routeIs('control.terminacion') ? 'menu-open' : '' }}">

    <a href="#" class="nav-link {{ request()->routeIs('control.terminacion') ? 'active' : '' }}">

        <i class="nav-icon fas fa-layer-group"></i>

        <p>
            TERMINACIÓN
            <i class="right fas fa-angle-left"></i>
        </p>

    </a>


    <ul class="nav nav-treeview">


        {{-- @can('controlterminacion ver') --}}

        <li class="nav-item">

            <a href="{{ route('control.terminacion') }}"
                class="nav-link {{ request()->routeIs('control.terminacion') ? 'active' : '' }}">

                <i class="nav-icon fas fa-angle-right"></i>

                <p>Control de Terminación</p>

            </a>

        </li>

        {{-- @endcan --}}

    </ul>

</li>

{{-- @endcan --}}



{{-- ===================== CONFIGURACIÓN ===================== --}}

<li class="nav-header">
    CONFIGURACIÓN
</li>


<li class="nav-item {{ $menuConfiguracion ? 'menu-open' : '' }}">

    <a href="#" class="nav-link {{ $menuConfiguracion ? 'active' : '' }}">

        <i class="nav-icon fas fa-cog"></i>

        <p>
            Configuración
            <i class="right fas fa-angle-left"></i>
        </p>

    </a>


    <ul class="nav nav-treeview">


        <li class="nav-item">

            <a href="{{ route('usuarios.index') }}"
                class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">

                <i class="nav-icon fas fa-users"></i>

                <p>Usuarios</p>

            </a>

        </li>



        <li class="nav-item">

            <a href="{{ route('permissions.index') }}"
                class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">

                <i class="nav-icon fas fa-key"></i>

                <p>Permisos</p>

            </a>

        </li>



        <li class="nav-item">

            <a href="{{ route('roles.index') }}"
                class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">

                <i class="nav-icon fas fa-user-shield"></i>

                <p>Roles</p>

            </a>

        </li>

    </ul>

</li>

{{-- <li class="nav-item">
    <a href="{{ route('redistribucionConfigs.index') }}" class="nav-link {{ Request::is('redistribucionConfigs*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-home"></i>
        <p>Redistribucion Configs</p>
    </a>
</li> --}}
