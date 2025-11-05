<?php

namespace App\Services;

use App\Models\File;
use App\Models\ForbiddenExtension;
use ZipArchive;
use App\Helpers\Logger;

class FileService
{
    const UPLOAD_DIR = 'storage/uploads/';
    const TEMP_DIR = 'storage/temp/';

    /**
     * Validar archivo antes de guardar
     */
    public static function validateFile($filePath, $fileName)
    {
        try {
            // 1. Validar extensión simple
            $extension = self::getFileExtension($fileName);
            $extensionCheck = self::validateExtension($extension);

            if (!$extensionCheck['valid']) {
                return $extensionCheck;
            }

            // 2. Si es ZIP, validar contenido interno
            if (strtolower($extension) === 'zip') {
                $zipCheck = self::validateZipContent($filePath);
                if (!$zipCheck['valid']) {
                    return $zipCheck;
                }
            }

            Logger::info("Archivo validado: {$fileName}");
            return ['valid' => true];
        } catch (\Exception $e) {
            Logger::error('FileService::validateFile - ' . $e->getMessage());
            return [
                'valid' => false,
                'error' => 'Error validando archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener extensión del archivo
     */
    public static function getFileExtension($fileName)
    {
        $parts = explode('.', $fileName);
        return strtolower(end($parts));
    }

    /**
     * Validar si la extensión está permitida
     */
    public static function validateExtension($extension)
    {
        try {
            // ⭐ CORREGIDO: Usar sintaxis de Phalcon 3.4.5 con :
            $forbidden = ForbiddenExtension::findFirst([
                'extension = :ext:',
                'bind' => ['ext' => $extension]
            ]);

            if ($forbidden) {
                Logger::warn("Extensión prohibida detectada: .{$extension}");
                return [
                    'valid' => false,
                    'error' => "❌ El tipo de archivo '.{$extension}' no está permitido"
                ];
            }

            return ['valid' => true];
        } catch (\Exception $e) {
            Logger::error('FileService::validateExtension - ' . $e->getMessage());
            return [
                'valid' => false,
                'error' => 'Error validando extensión'
            ];
        }
    }

    /**
     * Validar contenido de archivos ZIP
     */
    public static function validateZipContent($zipPath)
    {
        try {
            if (!file_exists($zipPath)) {
                return [
                    'valid' => false,
                    'error' => '❌ Archivo ZIP no encontrado'
                ];
            }

            $zip = new ZipArchive();
            $openResult = $zip->open($zipPath);

            if ($openResult !== true) {
                $errorMsg = self::getZipError($openResult);
                Logger::warn("Error abriendo ZIP: {$errorMsg}");
                return [
                    'valid' => false,
                    'error' => '❌ No se pudo abrir el archivo ZIP: ' . $errorMsg
                ];
            }

            // Iterar sobre archivos dentro del ZIP
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $fileName = $stat['name'];

                // Ignorar directorios
                if (substr($fileName, -1) === '/') {
                    continue;
                }

                // ⭐ Obtener extensión del archivo interno
                $extension = self::getFileExtension($fileName);

                // ⭐ CORREGIDO: Usar sintaxis correcta de Phalcon 3.4.5
                $forbidden = ForbiddenExtension::findFirst([
                    'extension = :ext:',
                    'bind' => ['ext' => $extension]
                ]);

                if ($forbidden) {
                    $zip->close();
                    Logger::warn("Archivo prohibido dentro de ZIP: {$fileName}");
                    return [
                        'valid' => false,
                        'error' => "❌ El archivo '{$fileName}' dentro del ZIP no está permitido (extensión: .{$extension})"
                    ];
                }
            }

            $zip->close();
            Logger::info("Contenido de ZIP validado correctamente");
            return ['valid' => true];
        } catch (\Exception $e) {
            Logger::error('FileService::validateZipContent - ' . $e->getMessage());
            return [
                'valid' => false,
                'error' => 'Error validando contenido del ZIP'
            ];
        }
    }

    /**
     * Obtener descripción de error ZIP
     */
    private static function getZipError($errorCode)
    {
        $errors = [
            ZipArchive::ER_OK => 'No hay error',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip no soportado',
            ZipArchive::ER_RENAME => 'Error renombrando archivo temporal',
            ZipArchive::ER_CLOSE => 'Error cerrando archivo',
            ZipArchive::ER_SEEK => 'Error seek en archivo',
            ZipArchive::ER_READ => 'Error leyendo archivo',
            ZipArchive::ER_WRITE => 'Error escribiendo archivo',
            ZipArchive::ER_CRC => 'Error CRC',
            ZipArchive::ER_ZIPCLOSED => 'ZIP cerrado',
            ZipArchive::ER_NOENT => 'Archivo no encontrado',
            ZipArchive::ER_EXISTS => 'Archivo existe',
            ZipArchive::ER_OPEN => 'No se puede abrir archivo',
            ZipArchive::ER_TMPOPEN => 'No se puede abrir archivo temporal',
            ZipArchive::ER_ZLIB => 'Error Zlib',
            ZipArchive::ER_MEMORY => 'Error de memoria',
            ZipArchive::ER_CHANGED => 'Cambios no guardados'
        ];

        return $errors[$errorCode] ?? 'Error desconocido (' . $errorCode . ')';
    }

    /**
     * Guardar archivo subido
     */
    public static function saveUploadedFile($userId, $originalFileName, $tempPath, $fileSizeBytes)
    {
        try {
            // Crear directorio si no existe
            if (!is_dir(self::UPLOAD_DIR)) {
                mkdir(self::UPLOAD_DIR, 0755, true);
            }

            // Generar nombre único
            $uniqueFileName = self::generateFileName($userId, $originalFileName);
            $destPath = self::UPLOAD_DIR . $uniqueFileName;

            // Mover archivo del temporal a ubicación definitiva
            if (!move_uploaded_file($tempPath, $destPath)) {
                Logger::error("Error moviendo archivo a: {$destPath}");
                return [
                    'success' => false,
                    'error' => 'Error moviendo archivo al almacenamiento'
                ];
            }

            // Obtener MIME type correctamente
            $mimeType = self::getMimeType($destPath);

            // Guardar información en BD
            $file = new File();
            $file->user_id = (int)$userId;
            $file->filename = $uniqueFileName;        // Nombre único en servidor
            $file->original_filename = $originalFileName; // Nombre original del usuario
            $file->file_path = $destPath;
            $file->file_size = (int)$fileSizeBytes;
            $file->mime_type = $mimeType;
            $file->created_at = date('Y-m-d H:i:s');
            $file->updated_at = date('Y-m-d H:i:s');

            if ($file->save()) {
                Logger::info("Archivo guardado: {$uniqueFileName} (ID: {$file->id}, Usuario: {$userId})");
                return [
                    'success' => true,
                    'file_id' => $file->id,
                    'message' => '✅ Archivo guardado exitosamente'
                ];
            } else {
                // Limpiar archivo si falla guardar en BD
                @unlink($destPath);
                Logger::error("Error guardando archivo en BD");
                return [
                    'success' => false,
                    'error' => 'Error guardando información del archivo'
                ];
            }
        } catch (\Exception $e) {
            Logger::error('FileService::saveUploadedFile - ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener MIME type de forma segura
     */
    private static function getMimeType($filePath)
    {
        try {
            // ⭐ Usar finfo en lugar de mime_content_type (deprecated)
            if (function_exists('finfo_file')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                return $mimeType ?: 'application/octet-stream';
            } elseif (function_exists('mime_content_type')) {
                return mime_content_type($filePath);
            } else {
                return 'application/octet-stream';
            }
        } catch (\Exception $e) {
            Logger::warn('Error obteniendo MIME type: ' . $e->getMessage());
            return 'application/octet-stream';
        }
    }

    /**
     * Generar nombre único para el archivo
     */
    private static function generateFileName($userId, $originalFileName)
    {
        $extension = self::getFileExtension($originalFileName);
        $timestamp = time();
        $random = bin2hex(random_bytes(8));

        return "{$userId}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Obtener archivos de un usuario
     */
    public static function getUserFiles($userId)
    {
        try {
            // ⭐ CORREGIDO: Usar sintaxis de Phalcon 3.4.5
            $files = File::find([
                'user_id = :uid:',
                'order' => 'created_at DESC',
                'bind' => ['uid' => (int)$userId]
            ]);

            $result = [];
            if ($files) {
                foreach ($files as $file) {
                    $result[] = [
                        'id' => (int)$file->id,
                        'filename' => $file->original_filename, // Mostrar nombre original al usuario
                        'file_size' => (int)$file->file_size,
                        'file_size_mb' => round($file->file_size / (1024 * 1024), 2),
                        'created_at' => $file->created_at,
                        'mime_type' => $file->mime_type
                    ];
                }
            }

            Logger::info("Obtenidos {$files->count()} archivos del usuario {$userId}");
            return $result;
        } catch (\Exception $e) {
            Logger::error('FileService::getUserFiles - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Descargar archivo
     */
    public static function downloadFile($fileId, $userId)
    {
        try {
            // ⭐ CORREGIDO: Usar sintaxis de Phalcon 3.4.5
            $file = File::findFirst([
                'id = :fid: AND user_id = :uid:',
                'bind' => ['fid' => (int)$fileId, 'uid' => (int)$userId]
            ]);

            if (!$file) {
                Logger::warn("Intento de descarga de archivo no encontrado: ID {$fileId}, Usuario {$userId}");
                return [
                    'success' => false,
                    'error' => 'Archivo no encontrado'
                ];
            }

            if (!file_exists($file->file_path)) {
                Logger::error("Archivo físico no existe: {$file->file_path}");
                return [
                    'success' => false,
                    'error' => 'Archivo no existe en almacenamiento'
                ];
            }

            Logger::info("Descarga iniciada: {$file->original_filename} (Usuario: {$userId})");
            return [
                'success' => true,
                'file_path' => $file->file_path,
                'file_name' => $file->original_filename,
                'mime_type' => $file->mime_type
            ];
        } catch (\Exception $e) {
            Logger::error('FileService::downloadFile - ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error descargando archivo'
            ];
        }
    }

    /**
     * Eliminar archivo
     */
    public static function deleteFile($fileId, $userId)
    {
        try {
            // ⭐ CORREGIDO: Usar sintaxis de Phalcon 3.4.5
            $file = File::findFirst([
                'id = :fid: AND user_id = :uid:',
                'bind' => ['fid' => (int)$fileId, 'uid' => (int)$userId]
            ]);

            if (!$file) {
                Logger::warn("Intento de eliminar archivo no encontrado: ID {$fileId}");
                return [
                    'success' => false,
                    'error' => 'Archivo no encontrado'
                ];
            }

            // Eliminar archivo físico
            if (file_exists($file->file_path)) {
                @unlink($file->file_path);
                Logger::info("Archivo físico eliminado: {$file->file_path}");
            }

            // Eliminar registro de BD
            if ($file->delete()) {
                Logger::info("Archivo eliminado: ID {$fileId}, Usuario {$userId}");
                return [
                    'success' => true,
                    'message' => '✅ Archivo eliminado'
                ];
            }

            Logger::error("Error eliminando registro de archivo ID {$fileId}");
            return [
                'success' => false,
                'error' => 'Error al eliminar'
            ];
        } catch (\Exception $e) {
            Logger::error('FileService::deleteFile - ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
