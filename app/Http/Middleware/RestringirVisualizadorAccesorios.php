<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestringirVisualizadorAccesorios
{
    /**
     * Aislar el rol de solo lectura del resto del sistema, incluso si alguien
     * intenta abrir directamente una URL oculta en el menú.
     */
    public function handle(Request $request, Closure $next)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->hasRole('visualizador_accesorios')
            || $usuario->hasRole('ADMIN')) {
            return $next($request);
        }

        // Permitimos cerrar sesión; no se permite ninguna otra operación POST.
        if ($request->isMethod('POST') && $request->routeIs('logout')) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($request->routeIs(
                'home',
                'accesorios.index',
                'accesorios.listado',
                'accesorios.show',
                'accesorios.detalle.resumen',
                'accesorios.facturas.index'
            )) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permiso para visualizar esta pantalla.');
    }
}
