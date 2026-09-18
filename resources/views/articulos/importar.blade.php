@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Importar Artículos</h2>
        {!! Form::open(['route' => 'articulos.importar', 'method' => 'post', 'files' => true]) !!}
        <div class="mb-3">
            <label for="archivo" class="form-label">Archivo Excel/CSV</label>
            <input type="file" name="archivo" id="archivo" class="form-control" required>
        </div>
        {!! Form::submit('Importar', ['class' => 'btn btn-success']) !!}
        <a href="{{ route('articulos.index') }}" class="btn btn-secondary">Cancelar</a>
        {!! Form::close() !!}
    </div>
@endsection
