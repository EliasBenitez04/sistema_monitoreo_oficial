<td class="num">@include('invierno._valores-celda', ['valor' => $valores['prendas']])</td>
@if(!empty($disponiblePorRevisar))
    <td class="num text-muted">Por revisar</td>
@else
    <td class="num iw-disponible-importe">@include('invierno._valores-celda', ['valor' => $valores['disponible']])</td>
@endif
