<?php

namespace App\Services;

use App\Models\User;
use App\Models\Group;
use App\Models\UserGroup;
use App\Models\GroupQuota;

class UserGroupService
{
    /**
     * Obtener usuarios con sus grupos
     */
    public static function getUsersWithGroups()
    {
        try {
            $users = User::find();

            if (!$users) {
                return [];
            }

            $result = [];
            foreach ($users as $user) {
                try {
                    $userGroups = UserGroup::find([
                        'user_id = :uid:',
                        'bind' => ['uid' => $user->id]
                    ]);

                    $groupIds = [];
                    if ($userGroups) {
                        foreach ($userGroups as $ug) {
                            $groupIds[] = (int)$ug->group_id;
                        }
                    }

                    $result[] = [
                        'id' => (int)$user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => $user->role,
                        'quota_mb' => (int)$user->quota_mb,
                        'group_ids' => $groupIds
                    ];
                } catch (\Exception $e) {
                    error_log('Error procesando usuario ' . $user->id . ': ' . $e->getMessage());
                    continue;
                }
            }

            return $result;
        } catch (\Exception $e) {
            error_log('UserGroupService::getUsersWithGroups - ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener grupos disponibles
     */
    public static function getAllGroupsForAssignment()
    {
        try {
            $groups = Group::find();
            $result = [];

            if ($groups) {
                foreach ($groups as $group) {
                    $quota = GroupQuota::findFirst([
                        'group_id = :gid:',
                        'bind' => ['gid' => $group->id]
                    ]);

                    $quotaMb = $quota ? (int)$quota->quota_mb : 0;
                    $usedMb = self::getGroupUsedQuota($group->id);

                    $result[] = [
                        'id' => (int)$group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'quota_mb' => $quotaMb,
                        'used_mb' => $usedMb,
                        'available_mb' => max(0, $quotaMb - $usedMb)
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            error_log('UserGroupService::getAllGroupsForAssignment - ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ⭐ Obtener cuota usada en un grupo (suma de cuotas de usuarios)
     */
    public static function getGroupUsedQuota($groupId)
    {
        try {
            $userGroups = UserGroup::find([
                'group_id = :gid:',
                'bind' => ['gid' => $groupId]
            ]);

            $totalUsed = 0;

            if ($userGroups) {
                foreach ($userGroups as $ug) {
                    $user = User::findFirst($ug->user_id);
                    if ($user) {
                        // ⭐ Usar cuota del usuario
                        $userQuota = (int)$user->quota_mb;
                        $totalUsed += $userQuota;
                    }
                }
            }

            error_log("📊 Grupo $groupId - Cuota usada: {$totalUsed}MB");
            return $totalUsed;
        } catch (\Exception $e) {
            error_log('UserGroupService::getGroupUsedQuota - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ⭐ Obtener cuota disponible en un grupo para un usuario
     */
    public static function getGroupAvailableQuotaForUser($groupId, $userId)
    {
        try {
            $group = Group::findFirst($groupId);
            if (!$group) {
                return 0;
            }

            $quota = GroupQuota::findFirst([
                'group_id = :gid:',
                'bind' => ['gid' => $groupId]
            ]);

            if (!$quota || (int)$quota->quota_mb === 0) {
                error_log("⚠️  Grupo $groupId sin cuota asignada");
                return 0;
            }

            $groupQuotaMb = (int)$quota->quota_mb;
            $groupUsedMb = self::getGroupUsedQuota($groupId);
            $groupAvailableMb = $groupQuotaMb - $groupUsedMb;

            // Obtener cuota del usuario
            $user = User::findFirst($userId);
            if (!$user) {
                return 0;
            }

            $userQuotaMb = (int)$user->quota_mb;

            error_log("🔍 Grupo: $groupQuotaMb MB | Usado: $groupUsedMb MB | Disponible: $groupAvailableMb MB | Usuario: $userQuotaMb MB");

            // Retornar el mínimo entre lo que el usuario necesita y lo disponible en el grupo
            return min($userQuotaMb, $groupAvailableMb);
        } catch (\Exception $e) {
            error_log('UserGroupService::getGroupAvailableQuotaForUser - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ⭐ Asignar usuario a grupo CON VALIDACIÓN
     */
    public static function assignUserToGroup($userId, $groupId)
    {
        try {
            $user = User::findFirst($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }

            $group = Group::findFirst($groupId);
            if (!$group) {
                return ['success' => false, 'error' => 'Grupo no encontrado'];
            }

            // Verificar si ya existe
            $existing = UserGroup::findFirst([
                'user_id = :uid: AND group_id = :gid:',
                'bind' => ['uid' => $userId, 'gid' => $groupId]
            ]);

            if ($existing) {
                return ['success' => true, 'message' => 'Usuario ya asignado a este grupo'];
            }

            // ⭐ VALIDACIÓN: Verificar disponibilidad
            $availableQuota = self::getGroupAvailableQuotaForUser($groupId, $userId);

            if ($availableQuota <= 0) {
                error_log("❌ No hay espacio en grupo $groupId para usuario $userId");
                return [
                    'success' => false,
                    'error' => 'El grupo no tiene suficiente cuota disponible para este usuario'
                ];
            }

            // Asignar usuario
            $userGroup = new UserGroup();
            $userGroup->user_id = $userId;
            $userGroup->group_id = $groupId;
            $userGroup->created_at = date('Y-m-d H:i:s');

            if ($userGroup->save()) {
                error_log("✅ Usuario $userId asignado a grupo $groupId (usando {$availableQuota}MB)");
                return [
                    'success' => true,
                    'message' => "Usuario asignado con {$availableQuota}MB",
                    'available_quota' => $availableQuota
                ];
            }

            return ['success' => false, 'error' => 'Error al asignar usuario'];
        } catch (\Exception $e) {
            error_log('UserGroupService::assignUserToGroup - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remover usuario de grupo
     */
    public static function removeUserFromGroup($userId, $groupId)
    {
        try {
            $userGroup = UserGroup::findFirst([
                'user_id = :uid: AND group_id = :gid:',
                'bind' => ['uid' => $userId, 'gid' => $groupId]
            ]);

            if (!$userGroup) {
                return ['success' => false, 'error' => 'Asignación no encontrada'];
            }

            if ($userGroup->delete()) {
                error_log("✅ Usuario $userId removido de grupo $groupId");
                return ['success' => true, 'message' => 'Usuario removido del grupo'];
            }

            return ['success' => false, 'error' => 'Error al remover usuario'];
        } catch (\Exception $e) {
            error_log('UserGroupService::removeUserFromGroup - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
