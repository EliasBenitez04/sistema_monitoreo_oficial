@php
    $moneda = \App\Support\InviernoValorizacion::class;
    $documentado = $moneda::linea($cantidadDocumentada, $costo, $precio);
    $disponible = $moneda::linea($cantidadDisponible, $costo, $precio);
@endphp
<td class="num" role="cell" data-etiqueta="Valores unitarios">@include('invierno._valores-celda', ['valor' => $moneda::linea(1, $costo, $precio), 'sinIndicador' => true])</td>
<td class="num" role="cell" data-etiqueta="{{ $etiquetaDocumentado ?? 'Valor documentado' }}">@include('invierno._valores-celda', ['valor' => $documentado])</td>
<td class="num iw-disponible-importe" role="cell" data-etiqueta="Valor disponible">@include('invierno._valores-celda', ['valor' => $disponible])</td>
