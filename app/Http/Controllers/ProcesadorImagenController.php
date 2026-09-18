<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class ProcesadorImagenController extends Controller
{
    public function index()
    {
        return view('ia_prendas.index');
    }

    public function subir(Request $request)
    {
        $request->validate([
            'imagenes' => 'required',
            'imagenes.*' => 'image|max:10240',
            'estilo' => 'nullable|string'
        ]);

        $token = env('REPLICATE_API_TOKEN');

        $base = storage_path('app/public/prendas');
        $originales = $base . '/originales';
        $procesadas = $base . '/procesadas';

        File::ensureDirectoryExists($originales);
        File::ensureDirectoryExists($procesadas);

        File::cleanDirectory($originales);
        File::cleanDirectory($procesadas);

        $imagenes = [];

        foreach ($request->file('imagenes') as $img) {
            $name = uniqid() . '.png';
            $img->move($originales, $name);
            $imagenes[] = $name;
        }

        // REMOVE BACKGROUND
        exec('rembg p "' . $originales . '" "' . $procesadas . '" 2>&1');

        $preview = [];

        foreach ($imagenes as $nombre) {

            $nombreSinExt = pathinfo($nombre, PATHINFO_FILENAME);
            $pngPath = $procesadas . '/' . $nombreSinExt . '.png';

            if (!File::exists($pngPath)) continue;

            $prompt = $this->prompt($request->estilo);

            $fondoUrl = $this->generarFondoIA($prompt, $token);

            if (!$fondoUrl) continue;

            // descargar fondo REAL
            $tmpBg = storage_path('app/public/prendas/bg_' . uniqid() . '.jpg');
            file_put_contents($tmpBg, file_get_contents($fondoUrl));

            $fondo = imagecreatefromstring(file_get_contents($tmpBg));
            $producto = imagecreatefrompng($pngPath);

            imagesavealpha($producto, true);

            // resize producto
            $newW = 700;
            $ratio = imagesy($producto) / imagesx($producto);
            $newH = intval($newW * $ratio);

            $tmp = imagecreatetruecolor($newW, $newH);
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);

            imagecopyresampled($tmp, $producto, 0, 0, 0, 0, $newW, $newH, imagesx($producto), imagesy($producto));

            $producto = $tmp;

            // centrar
            $x = (imagesx($fondo) - imagesx($producto)) / 2;
            $y = (imagesy($fondo) - imagesy($producto)) / 2;

            imagecopy($fondo, $producto, $x, $y, 0, 0, imagesx($producto), imagesy($producto));

            $finalPath = $procesadas . '/' . $nombreSinExt . '_final.jpg';
            imagejpeg($fondo, $finalPath, 92);

            imagedestroy($fondo);
            imagedestroy($producto);

            $preview[] = [
                'original' => asset('storage/prendas/originales/' . $nombre),
                'final' => asset('storage/prendas/procesadas/' . $nombreSinExt . '_final.jpg'),
            ];
        }

        session()->flash('preview_ia', $preview);

        return back();
    }

    // ================= FIX REAL REPLICATE =================
    private function generarFondoIA($prompt, $token)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ])->post('https://api.replicate.com/v1/models/stability-ai/sdxl/predictions', [
            "input" => [
                "prompt" => $prompt,
                "width" => 768,
                "height" => 768
            ]
        ]);

        $prediction = $response->json();

        if (!isset($prediction['urls']['get'])) {
            return null;
        }

        // polling correcto
        for ($i = 0; $i < 40; $i++) {

            sleep(2);

            $check = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get($prediction['urls']['get'])->json();

            if (($check['status'] ?? '') === 'succeeded') {

                return $check['output'][0] ?? null;
            }

            if (($check['status'] ?? '') === 'failed') {
                return null;
            }
        }

        return null;
    }

    private function prompt($estilo)
    {
        return match ($estilo) {
            'verano' => 'luxury beach ecommerce background, soft sunlight, empty center, product photography',
            'invierno' => 'snow winter cinematic studio background, soft light, ecommerce',
            'otoño' => 'autumn leaves warm aesthetic product photography background',
            default => 'clean professional ecommerce studio background'
        };
    }
}
