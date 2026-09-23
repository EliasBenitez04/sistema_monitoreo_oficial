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
            background: #0b0d10;
            color: #f8fafc;
        }

        .sidebar-dark-darkest .brand-link {
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            padding-top: .9rem;
            padding-bottom: .9rem;
        }

        .sidebar-dark-darkest .nav-header {
            padding: 1.25rem 1rem .45rem;
            color: #7f8b99;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .09em;
        }

        .sidebar-dark-darkest .nav-sidebar > .nav-item {
            margin: 2px 8px;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link {
            display: flex;
            align-items: center;
            min-height: 42px;
            border-radius: 8px;
            color: #cbd5e1;
            transition: background-color .15s ease, color .15s ease;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link .nav-icon {
            width: 1.65rem;
            margin-right: .45rem;
            font-size: .95rem;
            text-align: center;
            color: #94a3b8;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link p {
            margin: 0;
            font-size: .88rem;
            font-weight: 500;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link:hover {
            background: rgba(255, 255, 255, .07);
            color: #fff;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link:hover .nav-icon {
            color: #dbeafe;
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link.active {
            background: #0a60ff;
            color: #fff;
            box-shadow: 0 4px 12px rgba(10, 96, 255, .22);
        }

        .sidebar-dark-darkest .nav-sidebar .nav-link.active .nav-icon {
            color: #fff;
        }

        .sidebar-dark-darkest .nav-treeview {
            margin: 3px 0 7px;
            padding-left: 6px;
        }

        .sidebar-dark-darkest .nav-treeview > .nav-item > .nav-link {
            min-height: 37px;
            padding-top: .42rem;
            padding-bottom: .42rem;
            padding-left: .85rem;
            color: #aeb8c5;
        }

        .sidebar-dark-darkest .nav-treeview > .nav-item > .nav-link .nav-icon {
            font-size: .82rem;
            opacity: .9;
        }

        .sidebar-dark-darkest .nav-treeview > .nav-item > .nav-link p {
            font-size: .82rem;
        }

        .sidebar-dark-darkest .nav-sidebar .right {
            top: .85rem;
        }
    </style>
</aside>
