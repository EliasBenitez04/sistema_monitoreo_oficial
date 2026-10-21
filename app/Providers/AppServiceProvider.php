<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // El filtro de clientes de Mayoristas ya contiene exclusivamente
        // clientes de COMERCIAL MATRIZ. Acá lo convertimos en un buscador
        // escribible sin depender de Select2 ni de librerías externas.
        View::composer('ventas.mayoristas', function ($view) {
            $factory = $view->getFactory();
            $factory->startPush('page_scripts');
            echo <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('mayCliente');
    if (!select || document.getElementById('mayClienteBuscar')) return;

    var wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    wrapper.style.minWidth = '230px';

    var input = document.createElement('input');
    input.type = 'text';
    input.id = 'mayClienteBuscar';
    input.className = 'form-control';
    input.placeholder = 'Buscar cliente de Comercial Matriz...';
    input.autocomplete = 'off';

    var lista = document.createElement('div');
    lista.style.position = 'absolute';
    lista.style.left = '0';
    lista.style.right = '0';
    lista.style.top = '100%';
    lista.style.zIndex = '1050';
    lista.style.maxHeight = '280px';
    lista.style.overflowY = 'auto';
    lista.style.background = '#fff';
    lista.style.border = '1px solid #ced4da';
    lista.style.borderTop = '0';
    lista.style.display = 'none';

    var opciones = Array.prototype.slice.call(select.options).filter(function (op) {
        return op.value !== '';
    });

    function cerrar() {
        lista.style.display = 'none';
    }

    function mostrar(filtro) {
        var texto = (filtro || '').toLowerCase().trim();
        lista.innerHTML = '';
        var encontrados = 0;

        opciones.forEach(function (op) {
            if (texto && op.text.toLowerCase().indexOf(texto) === -1) return;
            encontrados++;
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item';
            item.textContent = op.text;
            item.addEventListener('click', function () {
                select.value = op.value;
                input.value = op.text;
                cerrar();
            });
            lista.appendChild(item);
        });

        if (!encontrados) {
            var vacio = document.createElement('div');
            vacio.className = 'px-3 py-2 text-muted small';
            vacio.textContent = 'No se encontraron clientes de Comercial Matriz';
            lista.appendChild(vacio);
        }
        lista.style.display = 'block';
    }

    if (select.value) {
        var seleccionada = opciones.find(function (op) { return op.value === select.value; });
        if (seleccionada) input.value = seleccionada.text;
    }

    input.addEventListener('focus', function () { mostrar(input.value); });
    input.addEventListener('input', function () {
        select.value = '';
        mostrar(input.value);
    });
    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) cerrar();
    });

    select.style.display = 'none';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(input);
    wrapper.appendChild(lista);
    wrapper.appendChild(select);
});
</script>
HTML;
            $factory->stopPush();
        });
    }
}
