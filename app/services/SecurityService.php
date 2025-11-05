<?php

namespace App\Services;

use App\Models\ForbiddenExtension;
use ZipArchive;

// Servicio para validar seguridad de archivos subidos
class SecurityService
{

    // Obtener todas las extensiones prohibidas en array
    public function getForbiddenExtensions()
    {
        $extensions = ForbiddenExtension::find();
        $forbidden = [];
        foreach ($extensions as $ext) {
            $forbidden[] = strtolower($ext->extension);
        }
        return $forbidden;
    }

    // Validar que extensión del archivo no esté prohibida
    public function isExtensionAllowed($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $forbidden = $this->getForbiddenExtensions();
        return !in_array($ext, $forbidden);
    }

    // Inspeccionar contenido de ZIP y validar archivos internos
    public function validateZipContents($zipPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            return ['valid' => false, 'error' => 'No se pudo abrir el ZIP'];
        }

        $forbidden = $this->getForbiddenExtensions();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameByIndex($i);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $forbidden)) {
                $zip->close();
                return [
                    'valid' => false,
                    'error' => "Archivo '{$filename}' no permitido dentro del ZIP"
                ];
            }
        }

        $zip->close();
        return ['valid' => true];
    }

    // Generar nombre único para archivo subido
    public function generateUniqueFilename($originalName)
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $unique = time() . '_' . bin2hex(random_bytes(8));
        return $unique . '.' . $ext;
    }

    // Sanitizar nombre de archivo para evitar exploits
    public function sanitizeFilename($filename)
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}
