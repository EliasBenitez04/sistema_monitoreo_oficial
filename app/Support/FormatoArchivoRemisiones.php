<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class FormatoArchivoRemisiones
{
    public static function detectar(UploadedFile $archivo, bool $admiteCsv = true): string
    {
        $ruta = $archivo->getRealPath();
        if (!$ruta || !$archivo->getSize()) {
            throw new InvalidArgumentException('El archivo ENVIOS está vacío o no se pudo leer.');
        }

        $lectores = [IOFactory::READER_XLSX, IOFactory::READER_XLS];
        if ($admiteCsv) {
            $lectores[] = IOFactory::READER_CSV;
        }

        try {
            return IOFactory::identify($ruta, $lectores);
        } catch (ReaderException $e) {
            $instruccion = 'Abrilo en Excel y guardalo como Libro de Excel (.xlsx)';
            if ($admiteCsv) {
                $instruccion .= ', o volvé a exportarlo como CSV';
            }
            throw new InvalidArgumentException(
                'No se reconoció el formato del archivo ENVIOS. ' . $instruccion . '.',
                0,
                $e
            );
        }
    }
}
