@php
    $moneda = \App\Support\InviernoValorizacion::class;
    $documentado = $moneda::linea($cantidadDocumentada, $costo, $precio);
    $disponible = $moneda::linea($cantidadDisponible, $costo, $precio);
@endphp
<td class="num iw-importe">@include('invierno._valores-celda', ['valor' => $moneda::linea(1, $costo, $precio), 'sinIndicador' => true])</td>
<td class="num">@include('invierno._valores-celda', ['valor' => $documentado])</td>
<td class="num iw-disponible-importe">@include('invierno._valores-celda', ['valor' => $disponible])</td>
