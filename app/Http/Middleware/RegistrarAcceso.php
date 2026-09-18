<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAcceso
{
    /**
     * Registrar actividad de los usuarios.
     */
    public function handle(Request $request, Closure $next)
    {
        /*
        |--------------------------------------------------------------------------
        | Ignorar archivos estáticos
        |--------------------------------------------------------------------------
        |
        | No necesitamos registrar CSS, JS, imágenes, fuentes, etc.
        |
        */

        if ($this->esArchivoEstatico($request)) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Datos del usuario
        |--------------------------------------------------------------------------
        */

        $usuario = auth()->check()
            ? auth()->user()
            : null;

        /*
        |--------------------------------------------------------------------------
        | Inicio del tiempo
        |--------------------------------------------------------------------------
        */

        $inicio = microtime(true);

        /*
        |--------------------------------------------------------------------------
        | Ejecutar petición
        |--------------------------------------------------------------------------
        */

        $response = $next($request);

        /*
        |--------------------------------------------------------------------------
        | Tiempo de respuesta
        |--------------------------------------------------------------------------
        */

        $tiempoRespuesta = round(
            (microtime(true) - $inicio) * 1000,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Información de la petición
        |--------------------------------------------------------------------------
        */

        $datos = [

            // =====================================================
            // USUARIO
            // =====================================================

            'usuario_id' => $usuario?->id,

            'usuario' => $usuario?->name
                ?? $usuario?->email
                ?? 'No autenticado',

            // =====================================================
            // RED
            // =====================================================

            'ip' => $request->ip(),

            'ip_real' => $this->obtenerIpReal($request),

            // =====================================================
            // PETICIÓN
            // =====================================================

            'metodo' => $request->method(),

            'ruta' => $request->path(),

            'url' => $request->fullUrl(),

            'nombre_ruta' => optional(
                $request->route()
            )->getName(),

            // =====================================================
            // RESPUESTA
            // =====================================================

            'status' => $response->getStatusCode(),

            'tiempo_ms' => $tiempoRespuesta,

            // =====================================================
            // NAVEGADOR / PC
            // =====================================================

            'user_agent' => $request->userAgent(),

            'navegador' => $this->detectarNavegador(
                $request->userAgent()
            ),

            'sistema_operativo' => $this->detectarSistemaOperativo(
                $request->userAgent()
            ),

            // =====================================================
            // SESIÓN
            // =====================================================

            'sesion' => $request->hasSession()
                ? $request->session()->getId()
                : null,

            // =====================================================
            // FECHA
            // =====================================================

            'fecha' => now()->format('Y-m-d H:i:s'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Registrar en Laravel Log
        |--------------------------------------------------------------------------
        */

        Log::channel('daily')->info(
            'ACTIVIDAD DEL SISTEMA',
            $datos
        );

        return $response;
    }

    /**
     * Detectar archivos estáticos.
     */
    private function esArchivoEstatico(Request $request): bool
    {
        $extensiones = [
            'css',
            'js',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'svg',
            'ico',
            'webp',
            'woff',
            'woff2',
            'ttf',
            'map',
        ];

        $extension = strtolower(
            pathinfo(
                $request->path(),
                PATHINFO_EXTENSION
            )
        );

        return in_array($extension, $extensiones);
    }

    /**
     * Obtener IP.
     */
    private function obtenerIpReal(Request $request): string
    {
        /*
         * Si estás detrás de un proxy,
         * Laravel puede manejar correctamente
         * la IP mediante TrustProxies.
         */

        return $request->ip();
    }

    /**
     * Detectar navegador.
     */
    private function detectarNavegador(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Desconocido';
        }

        if (str_contains($userAgent, 'Edg/')) {
            return 'Microsoft Edge';
        }

        if (str_contains($userAgent, 'Chrome')) {
            return 'Google Chrome';
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Mozilla Firefox';
        }

        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }

        if (str_contains($userAgent, 'Opera')) {
            return 'Opera';
        }

        return 'Otro';
    }

    /**
     * Detectar sistema operativo.
     */
    private function detectarSistemaOperativo(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Desconocido';
        }

        if (str_contains($userAgent, 'Windows NT 10.0')) {
            return 'Windows 10/11';
        }

        if (str_contains($userAgent, 'Windows NT 6.3')) {
            return 'Windows 8.1';
        }

        if (str_contains($userAgent, 'Windows NT 6.2')) {
            return 'Windows 8';
        }

        if (str_contains($userAgent, 'Windows NT 6.1')) {
            return 'Windows 7';
        }

        if (str_contains($userAgent, 'Mac OS X')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (
            str_contains($userAgent, 'iPhone') ||
            str_contains($userAgent, 'iPad')
        ) {
            return 'iOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return 'Otro';
    }
}
