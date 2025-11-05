<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGroup;

class UserService
{
    public static function getAllUsers()
    {
        try {
            $users = User::find();
            return $users ? $users->toArray() : [];
        } catch (\Exception $e) {
            error_log('UserService::getAllUsers - ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getUserById($id)
    {
        try {
            $user = User::findFirst($id);
            if (!$user) {
                return null;
            }

            $userData = $user->toArray();

            // ⭐ Cargar group_ids
            $userGroups = UserGroup::find([
                'user_id = :uid:',
                'bind' => ['uid' => $id]
            ]);

            $userData['group_ids'] = [];
            foreach ($userGroups as $ug) {
                $userData['group_ids'][] = $ug->group_id;
            }

            return $userData;
        } catch (\Exception $e) {
            error_log('UserService::getUserById - ' . $e->getMessage());
            throw $e;
        }
    }

    // ⭐ ACTUALIZADO: Agregar parámetro $quotaMb
    public static function createUser($username, $email, $password, $role = 'user', $quotaMb = null)
    {
        try {
            $existing = User::findFirst([
                'username = :user: OR email = :email:',
                'bind' => [
                    'user' => $username,
                    'email' => $email
                ]
            ]);

            if ($existing) {
                return ['success' => false, 'error' => 'El usuario o email ya existe'];
            }

            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->password = password_hash($password, PASSWORD_BCRYPT);
            $user->role = $role;
            $user->quota_mb = !empty($quotaMb) ? (int)$quotaMb : null;  // ⭐ AGREGAR CUOTA
            $user->created_at = date('Y-m-d H:i:s');
            $user->updated_at = date('Y-m-d H:i:s');

            if ($user->save()) {
                return ['success' => true, 'message' => 'Usuario creado', 'user_id' => $user->id];
            } else {
                $messages = $user->getMessages();
                $error = '';
                foreach ($messages as $message) {
                    $error .= $message->getMessage() . ' ';
                }
                return ['success' => false, 'error' => 'Error: ' . trim($error)];
            }
        } catch (\Exception $e) {
            error_log('UserService::createUser - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ⭐ ACTUALIZADO: Agregar parámetro $quotaMb
    public static function updateUser($userId, $username, $email, $role = 'user', $password = null, $quotaMb = null)
    {
        try {
            $user = User::findFirst($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }

            $user->username = $username;
            $user->email = $email;
            $user->role = $role;
            $user->updated_at = date('Y-m-d H:i:s');

            // ⭐ Actualizar cuota (si se proporciona)
            if ($quotaMb !== null) {
                $user->quota_mb = !empty($quotaMb) ? (int)$quotaMb : null;
            }

            if (!empty($password)) {
                $user->password = password_hash($password, PASSWORD_BCRYPT);
            }

            if ($user->update()) {
                return ['success' => true, 'message' => 'Usuario actualizado'];
            } else {
                $messages = $user->getMessages();
                $error = '';
                foreach ($messages as $message) {
                    $error .= $message->getMessage() . ' ';
                }
                return ['success' => false, 'error' => 'Error: ' . trim($error)];
            }
        } catch (\Exception $e) {
            error_log('UserService::updateUser - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function deleteUser($userId)
    {
        try {
            $user = User::findFirst($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }

            if ($user->delete()) {
                return ['success' => true, 'message' => 'Usuario eliminado'];
            } else {
                return ['success' => false, 'error' => 'Error al eliminar'];
            }
        } catch (\Exception $e) {
            error_log('UserService::deleteUser - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ⭐ NUEVOS MÉTODOS DE UTILIDAD

    /**
     * Obtener la cuota efectiva de un usuario
     * Prioridad: Usuario > Grupo > Global
     */
    public static function getUserEffectiveQuota($userId)
    {
        try {
            $user = User::findFirst($userId);
            if (!$user) {
                return 100;
            }

            // Si el usuario tiene cuota personalizada, usarla
            if ($user->quota_mb !== null) {
                return (int)$user->quota_mb;
            }

            // Si no, usar global
            return (int)($_SESSION['global_quota_mb'] ?? getenv('GLOBAL_QUOTA_MB') ?: 100);
        } catch (\Exception $e) {
            error_log('UserService::getUserEffectiveQuota - ' . $e->getMessage());
            return 100;
        }
    }

    /**
     * Obtener espacio usado por un usuario
     */
    public static function getUserUsedSpace($userId)
    {
        try {
            $db = \Phalcon\Di::getDefault()->get('db');

            $result = $db->query('
                SELECT SUM(size_bytes) as total_bytes 
                FROM files 
                WHERE user_id = ?
            ', [$userId]);

            $row = $result->fetch();
            $totalBytes = $row['total_bytes'] ?? 0;

            return round($totalBytes / 1024 / 1024, 2);
        } catch (\Exception $e) {
            error_log('UserService::getUserUsedSpace - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Validar si un usuario puede subir un archivo
     */
    public static function canUserUploadFile($userId, $fileSizeBytes)
    {
        try {
            $quotaMb = self::getUserEffectiveQuota($userId);
            $usedMb = self::getUserUsedSpace($userId);
            $fileSizeMb = round($fileSizeBytes / 1024 / 1024, 2);

            $availableMb = $quotaMb - $usedMb;

            if ($fileSizeMb > $availableMb) {
                return [
                    'allowed' => false,
                    'reason' => "Cuota excedida. Disponible: {$availableMb} MB, Archivo: {$fileSizeMb} MB"
                ];
            }

            return ['allowed' => true];
        } catch (\Exception $e) {
            error_log('UserService::canUserUploadFile - ' . $e->getMessage());
            return ['allowed' => false, 'reason' => 'Error al validar cuota'];
        }
    }
}
