<td class="num" role="cell" data-etiqueta="{{ $etiquetaDocumentado ?? 'Valor documentado' }}">@include('invierno._valores-celda', ['valor' => $valores['prendas']])</td>
@if(!empty($disponiblePorRevisar))
    <td class="num text-muted" role="cell" data-etiqueta="Valor disponible">Por revisar</td>
@else
    <td class="num iw-disponible-importe" role="cell" data-etiqueta="Valor disponible">@include('invierno._valores-celda', ['valor' => $valores['disponible']])</td>
@endif
