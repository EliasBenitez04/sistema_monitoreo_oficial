<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="auditorias-table">
            <thead>
                <tr>
                    <th>DATOS ANTIGUOS</th>
                    <th>DATOS NUEVOS</th>
                    <th>OP</th>
                    <th>TABLA</th>
                    <th>IP</th>
                    <th>ID</th>
                    <th>FECHA</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($auditorias as $aud)
                    <tr>
                        <td>{{ $aud->datos_antiguos }}</td>
                        <td>{{ $aud->datos_nuevos }}</td>
                        <td>{{ $aud->operaciones }}</td>
                        <td>{{ $aud->tabla }}</td>
                        <td>{{ $aud->ip }}</td>
                        <td>{{ $aud->id }}</td>
                        <td>{{ $aud->fecha }}</td>
                        <td style="width: 120px">
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        <div class="float-right">
            {{-- @include('adminlte-templates::common.paginate', ['records' => $auditorias]) --}}
        </div>
    </div>
</div>
