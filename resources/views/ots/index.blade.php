@extends('layouts.app')

@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">

            <div class="col-sm-6">
                <h1>Importación De OT</h1>
            </div>

            <div class="col-sm-6">
                <div class="d-flex justify-content-end">

                    <form action="{{ route('ots.buscarEditar') }}"
                        method="GET"
                        class="d-flex align-items-center">

                        <div class="input-group">

                            <input
                                type="number"
                                name="nro_ot"
                                class="form-control"
                                placeholder="Ingrese N° de OT"
                                min="1"
                                required>

                            <div class="input-group-append">

                                <button
                                    type="submit"
                                    class="btn btn-primary">

                                    <i class="fas fa-search"></i>
                                    Buscar OT

                                </button>

                            </div>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</section>


<div class="content px-3">

    @include('sweetalert::alert')

    <div class="clearfix"></div>

    <div class="card">

        <div class="card-body">

            @include('ots.table')

        </div>

    </div>

</div>

@endsection