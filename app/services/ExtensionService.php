<?php

namespace App\Services;

use App\Models\ForbiddenExtension;

class ExtensionService
{
    public static function getAllExtensions()
    {
        try {
            $extensions = ForbiddenExtension::find();
            return $extensions ? $extensions->toArray() : [];
        } catch (\Exception $e) {
            error_log('ExtensionService::getAllExtensions - ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getExtensionById($id)
    {
        try {
            $extension = ForbiddenExtension::findFirst($id);
            return $extension ? $extension->toArray() : null;
        } catch (\Exception $e) {
            error_log('ExtensionService::getExtensionById - ' . $e->getMessage());
            throw $e;
        }
    }

    public static function addExtension($extension, $description = '')
    {
        try {
            $extension = strtolower(trim($extension));
            $extension = ltrim($extension, '.');

            if (empty($extension)) {
                return ['success' => false, 'error' => 'La extensión es requerida'];
            }

            // ⭐ CORREGIR: Sin LIMIT en findFirst
            $existing = ForbiddenExtension::findFirst([
                'extension = :ext:',
                'bind' => ['ext' => $extension]
            ]);

            if ($existing) {
                return ['success' => false, 'error' => 'Esta extensión ya está prohibida'];
            }

            $ext = new ForbiddenExtension();
            $ext->extension = $extension;
            $ext->description = $description;
            $ext->created_at = date('Y-m-d H:i:s');
            $ext->updated_at = date('Y-m-d H:i:s');

            if ($ext->save()) {
                return ['success' => true, 'message' => 'Extensión añadida'];
            } else {
                $messages = $ext->getMessages();
                $error = '';
                foreach ($messages as $message) {
                    $error .= $message->getMessage() . ' ';
                }
                return ['success' => false, 'error' => 'Error: ' . trim($error)];
            }
        } catch (\Exception $e) {
            error_log('ExtensionService::addExtension - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function updateExtension($extensionId, $extension, $description = '')
    {
        try {
            $extension = strtolower(trim($extension));
            $extension = ltrim($extension, '.');

            if (empty($extension)) {
                return ['success' => false, 'error' => 'La extensión es requerida'];
            }

            $ext = ForbiddenExtension::findFirst($extensionId);
            if (!$ext) {
                return ['success' => false, 'error' => 'Extensión no encontrada'];
            }

            $ext->extension = $extension;
            $ext->description = $description;
            $ext->updated_at = date('Y-m-d H:i:s');

            if ($ext->update()) {
                return ['success' => true, 'message' => 'Extensión actualizada'];
            } else {
                return ['success' => false, 'error' => 'Error al actualizar'];
            }
        } catch (\Exception $e) {
            error_log('ExtensionService::updateExtension - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function deleteExtension($extensionId)
    {
        try {
            $extension = ForbiddenExtension::findFirst($extensionId);
            if (!$extension) {
                return ['success' => false, 'error' => 'Extensión no encontrada'];
            }

            if ($extension->delete()) {
                return ['success' => true, 'message' => 'Extensión eliminada'];
            } else {
                return ['success' => false, 'error' => 'Error al eliminar'];
            }
        } catch (\Exception $e) {
            error_log('ExtensionService::deleteExtension - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
