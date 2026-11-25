<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Mueve un upload al destino con reintentos: en Windows el antivirus o el
 * indexador pueden bloquear por milisegundos un archivo recién escrito y el
 * rename directo falla con "Acceso denegado" aunque haya permisos.
 */
class UploadFiles
{
    public static function move(UploadedFile $file, string $destDir, string $filename, int $tries = 10): void
    {
        $target = rtrim($destDir, '/\\').DIRECTORY_SEPARATOR.$filename;
        $last = null;

        for ($i = 0; $i < $tries; $i++) {
            try {
                if (is_file($target)) {
                    @unlink($target);
                }
                $file->move($destDir, $filename);

                return;
            } catch (\Throwable $e) {
                $last = $e;
                // rename() falla si el antivirus/indexador tiene abierto el
                // temporal: la copia por stream no sufre ese bloqueo.
                if (@copy($file->getPathname(), $target)) {
                    @unlink($file->getPathname());

                    return;
                }
                usleep(1000000);
            }
        }

        throw $last;
    }
}
