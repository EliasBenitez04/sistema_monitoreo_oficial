<?php

namespace App\Support;

/** Misma lectura de nombres que Ecommerce de sistema_mayorista. */
class ArticuloImagenNombre
{
    public static function leer(string $nombre): ?array
    {
        $base = trim(pathinfo($nombre, PATHINFO_FILENAME));
        if (!preg_match('/^([0-9]{9}(?:[A-Z]{2}(?:[A-Z0-9]{2})?)?)(?:\s+|$)(.*)$/iu', $base, $m)) {
            return null;
        }
        $codigo = strtoupper($m[1]);
        if (strlen($codigo) === 13) {
            $codigo = substr($codigo, 0, 11);
        }
        $resto = trim($m[2]);
        $valorV = null;
        $valorC = null;
        $ot = null;
        $fecha = null;

        // Puede aparecer delante de "NENA..." o al final; también es opcional.
        if (preg_match('/(?<![0-9])([0-9]{2}[-\/.][0-9]{2}[-\/.](?:[0-9]{4}|[0-9]{2}))(?![0-9])/u', $resto, $d)) {
            $fechaTexto = str_replace(['/', '.'], '-', $d[1]);
            $formato = strlen($fechaTexto) === 8 ? '!d-m-y' : '!d-m-Y';
            $fechaObj = \DateTimeImmutable::createFromFormat($formato, $fechaTexto);
            if (!$fechaObj || $fechaObj->format(substr($formato, 1)) !== $fechaTexto) {
                return null;
            }
            $fecha = $fechaObj->format('Y-m-d');
            $resto = preg_replace('/(?<![0-9])[0-9]{2}[-\/.][0-9]{2}[-\/.](?:[0-9]{4}|[0-9]{2})(?![0-9])/u',
                ' ', $resto, 1);
        }

        if (preg_match('/(?:^|\s)V\s*([0-9]+(?:[.,][0-9]+)?)(?=\s|$)/iu', $resto, $v)) {
            $valorV = 'V' . $v[1];
            $resto = preg_replace('/(?:^|\s)V\s*[0-9]+(?:[.,][0-9]+)?(?=\s|$)/iu', ' ', $resto, 1);
        } elseif (preg_match('/^\s*([0-9]+(?:[.,][0-9]+)?)\s+(?=C\s*[0-9])/iu', $resto, $v)) {
            // Archivos heredados con "79 C28062..." en vez de "V79 C28062...".
            $valorV = 'V' . $v[1];
            $resto = preg_replace('/^\s*[0-9]+(?:[.,][0-9]+)?\s+(?=C\s*[0-9])/iu', '', $resto, 1);
        }

        if (preg_match('/(?:^|\s)C\s*([0-9]+(?:[.,][0-9]+)?)(?=\s|$)/iu', $resto, $c)) {
            $valorC = 'C' . $c[1];
            $resto = preg_replace('/(?:^|\s)C\s*[0-9]+(?:[.,][0-9]+)?(?=\s|$)/iu', ' ', $resto, 1);
        }
        if (preg_match('/(?:^|\s)OT\s*([0-9]+)(?=\s|$)/iu', $resto, $o)) {
            $ot = $o[1];
            $resto = preg_replace('/(?:^|\s)OT\s*[0-9]+(?=\s|$)/iu', ' ', $resto, 1);
        }

        $descripcion = trim(preg_replace('/\s+/u', ' ', $resto));
        return [
            'codigo_base' => $codigo,
            'valor_v' => $valorV,
            'valor_c' => $valorC,
            'ot' => $ot,
            'descripcion_archivo' => $descripcion === '' ? null : mb_substr($descripcion, 0, 255),
            'fecha_archivo' => $fecha,
        ];
    }

}

