<?php

namespace App\Http\Controllers;

use App\Imports\MaestroCodigosImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MaestroCodigoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    public function index(Request $request)
    {
        $this->asegurarTabla();

        $buscar = trim((string) $request->input('buscar', ''));
        $temporada = trim((string) $request->input('temporada', ''));
        $linea = trim((string) $request->input('linea', ''));
        $anio = trim((string) $request->input('anio', ''));
        $tipoCodigo = trim((string) $request->input('tipo_codigo', ''));

        $query = DB::table('maestro_codigos');

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('cod_articulo', 'ilike', '%' . $buscar . '%')
                    ->orWhere('cod_base', 'ilike', '%' . $buscar . '%')
                    ->orWhere('cod_imagen', 'ilike', '%' . $buscar . '%')
                    ->orWhere('articulo', 'ilike', '%' . $buscar . '%')
                    ->orWhere('grupo', 'ilike', '%' . $buscar . '%')
                    ->orWhere('grupo_plan', 'ilike', '%' . $buscar . '%');
            });
        }

        if ($temporada !== '') {
            $query->where('temporada', $temporada);
        }

        if ($linea !== '') {
            $query->where('linea', $linea);
        }

        if ($anio !== '') {
            $query->where('anio', (int) $anio);
        }

        if ($tipoCodigo !== '') {
            $query->where('tipo_codigo', $tipoCodigo);
        }

        $codigos = $query
            ->orderBy('cod_articulo')
            ->paginate(50)
            ->appends($request->query());

        $temporadas = DB::table('maestro_codigos')
            ->whereNotNull('temporada')
            ->where('temporada', '<>', '')
            ->distinct()
            ->orderBy('temporada')
            ->pluck('temporada');

        $lineas = DB::table('maestro_codigos')
            ->whereNotNull('linea')
            ->where('linea', '<>', '')
            ->distinct()
            ->orderBy('linea')
            ->pluck('linea');

        $anios = DB::table('maestro_codigos')
            ->whereNotNull('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');

        $tiposCodigo = DB::table('maestro_codigos')
            ->whereNotNull('tipo_codigo')
            ->where('tipo_codigo', '<>', '')
            ->distinct()
            ->orderBy('tipo_codigo')
            ->pluck('tipo_codigo');

        $resumen = (object) [
            'total' => (int) DB::table('maestro_codigos')->count(),
            'bases' => (int) DB::table('maestro_codigos')
                ->whereNotNull('cod_base')
                ->distinct('cod_base')
                ->count('cod_base'),
            'imagenes' => (int) DB::table('maestro_codigos')
                ->whereNotNull('cod_imagen')
                ->distinct('cod_imagen')
                ->count('cod_imagen'),
            'temporadas' => (int) $temporadas->count(),
        ];

        return view(
            'maestro_codigos.index',
            compact(
                'codigos',
                'buscar',
                'temporada',
                'linea',
                'anio',
                'tipoCodigo',
                'temporadas',
                'lineas',
                'anios',
                'tiposCodigo',
                'resumen'
            )
        );
    }

    public function importarForm()
    {
        $this->asegurarTabla();

        return view('maestro_codigos.importar');
    }

    public function importar(Request $request)
    {
        $this->asegurarTabla();

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,csv,txt|max:204800',
            'import_token' => 'required|string|max:100',
        ]);

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '-1');
        @ini_set('memory_limit', '1536M');
        @ignore_user_abort(true);

        DB::disableQueryLog();

        $token = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '',
            (string) $request->input('import_token')
        );

        if ($token === '') {
            $token = Str::random(40);
        }

        $importador = new MaestroCodigosImport($token);

        try {
            $archivo = $request->file('archivo');
            $extension = strtolower(
                $archivo->getClientOriginalExtension()
            );

            if ($extension === 'xlsx') {
                $importador->importarXlsx(
                    $archivo->getRealPath()
                );
            } else {
                $importador->importarCsv(
                    $archivo->getRealPath()
                );
            }

            $resumen = $importador->resumen();

            return response()->json([
                'success' => true,
                'message' => 'Maestro de códigos importado correctamente.',
                'resumen' => $resumen,
                'redirect' => route('maestro-codigos.index'),
            ]);
        } catch (\Throwable $e) {
            $importador->marcarError($e->getMessage());

            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function progreso($token)
    {
        $token = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '',
            (string) $token
        );

        return response()->json(
            Cache::get(
                'maestro_codigos_import_' . $token,
                [
                    'estado' => 'ESPERANDO',
                    'mensaje' => 'Esperando inicio de importación...',
                    'procesadas' => 0,
                    'guardadas' => 0,
                    'omitidas' => 0,
                    'total' => null,
                    'porcentaje' => null,
                ]
            )
        );
    }

    private function asegurarTabla(): void
    {
        abort_unless(
            Schema::hasTable('maestro_codigos'),
            503,
            'Falta ejecutar php artisan migrate para crear maestro_codigos.'
        );
    }
}
