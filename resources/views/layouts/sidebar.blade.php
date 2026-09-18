<aside class="main-sidebar sidebar-dark-darkest elevation-4">
    <a href="{{ route('home') }}" class="brand-link text-center">
        <img src="{{ asset('storage/logos/gts_logo.jpg') }}" alt="Gotitas" class="brand-image img-circle elevation-3"
            style="opacity: .9">

        <span class="brand-text font-weight-bold text">
            {{ config('app.name') }}
        </span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                @include('layouts.menu')
            </ul>
        </nav>
    </div>
    <style>
        .sidebar-dark-darkest {
            background-color: #000000;
            /* casi negro */
            color: #f9fbff;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link.active {
            background-color: #0a60ff;
            /* mantiene azul para activo */
            color: #fff;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link:hover {
            background-color: #313131;
            color: #fff;
        }
    </style>
</aside>
