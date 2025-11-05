<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupQuota;
use App\Models\QuotaSetting;
use App\Models\UserGroup;

class GroupService
{
    /**
     * Obtener todos los grupos con su cuota
     */
    public static function getAllGroupsWithQuota()
    {
        try {
            $groups = Group::find(['order' => 'id DESC'])->toArray();

            foreach ($groups as &$group) {
                $quota = GroupQuota::findFirst([
                    'conditions' => 'group_id = ?1',
                    'bind' => [1 => $group['id']]
                ]);
                $group['quota_mb'] = $quota ? (int)$quota->quota_mb : 0;
            }

            return $groups;
        } catch (\Exception $e) {
            error_log('GroupService::getAllGroupsWithQuota - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener grupo por ID
     */
    public static function getGroupById($id)
    {
        try {
            return Group::findFirst($id);
        } catch (\Exception $e) {
            error_log('GroupService::getGroupById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ⭐ OBTENER CUOTA TOTAL USADA POR TODOS LOS GRUPOS
     */
    public static function getTotalGroupsQuota()
    {
        try {
            $quotas = GroupQuota::find();
            $total = 0;

            if ($quotas) {
                foreach ($quotas as $quota) {
                    $total += (int)$quota->quota_mb;
                }
            }

            error_log("📊 Cuota total de grupos: {$total}MB");
            return $total;
        } catch (\Exception $e) {
            error_log('GroupService::getTotalGroupsQuota - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ⭐ OBTENER CUOTA GLOBAL
     */
    public static function getGlobalQuota()
    {
        try {
            $setting = QuotaSetting::findFirst([
                'setting_key = :key:',
                'bind' => ['key' => 'default_quota_mb']
            ]);

            $globalQuota = $setting ? (int)$setting->setting_value : 200;
            error_log("📢 Cuota global del sistema: {$globalQuota}MB");
            return $globalQuota;
        } catch (\Exception $e) {
            error_log('GroupService::getGlobalQuota - ' . $e->getMessage());
            return 200;
        }
    }

    /**
     * ⭐ Crear nuevo grupo
     * LÓGICA:
     * - Si $quotaMb es NULL: calcular automáticamente (0 o 100)
     * - Si $quotaMb es especificado: validar que NO exceda el global
     */
    public static function createGroup($name, $description = '', $quotaMb = null)
    {
        try {
            if (empty($name)) {
                return ['success' => false, 'error' => 'El nombre del grupo es requerido'];
            }

            $globalQuota = self::getGlobalQuota();
            $totalGroupsQuota = self::getTotalGroupsQuota();

            error_log("🔍 CREAR GRUPO '$name' - Global: {$globalQuota}MB, Grupos actuales: {$totalGroupsQuota}MB");

            // ⭐ LÓGICA MEJORADA
            if ($quotaMb === null) {
                // Si NO especifica cuota, calcular automáticamente
                if ($totalGroupsQuota >= $globalQuota) {
                    $quotaMb = 0;
                    error_log("⚠️  Sin cuota especificada: suma ({$totalGroupsQuota}MB) >= global ({$globalQuota}MB) → 0MB");
                } else {
                    $quotaMb = 100;
                    error_log("✅ Sin cuota especificada: suma ({$totalGroupsQuota}MB) < global ({$globalQuota}MB) → 100MB");
                }
            } else {
                // Si especifica cuota, VALIDAR que no exceda global
                $quotaMb = (int)$quotaMb;

                if ($quotaMb > $globalQuota) {
                    error_log("❌ Cuota solicitada ({$quotaMb}MB) > global ({$globalQuota}MB) → Limitando a {$globalQuota}MB");
                    $quotaMb = $globalQuota;
                }

                // Validar que suma + esta cuota no exceda global
                if (($totalGroupsQuota + $quotaMb) > $globalQuota) {
                    $maxAllowed = $globalQuota - $totalGroupsQuota;
                    error_log("⚠️  Cuota solicitada ({$quotaMb}MB) haría que suma exceda global → Limitando a {$maxAllowed}MB");
                    $quotaMb = max(0, $maxAllowed);
                }

                error_log("✅ Cuota validada: {$quotaMb}MB (solicitado: {$quotaMb}MB, global: {$globalQuota}MB)");
            }

            $group = new Group();
            $group->name = $name;
            $group->description = $description;

            if ($group->save()) {
                error_log("✅ Grupo guardado: ID={$group->id}, Nombre=$name");

                $quota = new GroupQuota();
                $quota->group_id = $group->id;
                $quota->quota_mb = $quotaMb;

                if ($quota->save()) {
                    error_log("✅ Cuota guardada: group_id={$group->id}, quota_mb={$quotaMb}MB");
                    return [
                        'success' => true,
                        'group_id' => $group->id,
                        'quota_mb' => $quotaMb,
                        'message' => "Grupo creado con cuota {$quotaMb}MB"
                    ];
                } else {
                    $group->delete();
                    error_log("❌ Error al guardar cuota para grupo {$group->id}");
                    return ['success' => false, 'error' => 'Error al crear cuota del grupo'];
                }
            }

            return ['success' => false, 'error' => 'Error al crear grupo'];
        } catch (\Exception $e) {
            error_log('❌ GroupService::createGroup ERROR: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ⭐ Actualizar grupo
     * LÓGICA:
     * - Si $quotaMb es NULL: calcular automáticamente (0 o 100)
     * - Si $quotaMb es especificado: validar que NO exceda el global
     */
    public static function updateGroup($id, $name, $description = '', $quotaMb = null)
    {
        try {
            if (empty($name)) {
                return ['success' => false, 'error' => 'El nombre del grupo es requerido'];
            }

            $group = Group::findFirst($id);
            if (!$group) {
                return ['success' => false, 'error' => 'Grupo no encontrado'];
            }

            $globalQuota = self::getGlobalQuota();
            $totalGroupsQuota = self::getTotalGroupsQuota();

            $currentGroupQuota = GroupQuota::findFirst([
                'conditions' => 'group_id = ?1',
                'bind' => [1 => $id]
            ]);
            if ($currentGroupQuota) {
                $totalGroupsQuota -= (int)$currentGroupQuota->quota_mb;
            }

            error_log("🔍 ACTUALIZAR GRUPO '$name' - Global: {$globalQuota}MB, Otros grupos: {$totalGroupsQuota}MB");

            // ⭐ LÓGICA MEJORADA
            if ($quotaMb === null) {
                // Si NO especifica cuota, calcular automáticamente
                if ($totalGroupsQuota >= $globalQuota) {
                    $quotaMb = 0;
                    error_log("⚠️  Sin cuota especificada: suma ({$totalGroupsQuota}MB) >= global ({$globalQuota}MB) → 0MB");
                } else {
                    $quotaMb = 100;
                    error_log("✅ Sin cuota especificada: suma ({$totalGroupsQuota}MB) < global ({$globalQuota}MB) → 100MB");
                }
            } else {
                // Si especifica cuota, VALIDAR que no exceda global
                $quotaMb = (int)$quotaMb;

                if ($quotaMb > $globalQuota) {
                    error_log("❌ Cuota solicitada ({$quotaMb}MB) > global ({$globalQuota}MB) → Limitando a {$globalQuota}MB");
                    $quotaMb = $globalQuota;
                }

                // Validar que suma + esta cuota no exceda global
                if (($totalGroupsQuota + $quotaMb) > $globalQuota) {
                    $maxAllowed = $globalQuota - $totalGroupsQuota;
                    error_log("⚠️  Cuota solicitada ({$quotaMb}MB) haría que suma exceda global → Limitando a {$maxAllowed}MB");
                    $quotaMb = max(0, $maxAllowed);
                }

                error_log("✅ Cuota validada: {$quotaMb}MB");
            }

            $group->name = $name;
            $group->description = $description;

            if ($group->update()) {
                error_log("✅ Grupo actualizado: ID={$group->id}, Nombre=$name");

                $quota = GroupQuota::findFirst([
                    'conditions' => 'group_id = ?1',
                    'bind' => [1 => $id]
                ]);

                if ($quota) {
                    $quota->quota_mb = $quotaMb;
                    $quota->update();
                    error_log("✅ Cuota actualizada: group_id={$id}, quota_mb={$quotaMb}MB");
                } else {
                    $newQuota = new GroupQuota();
                    $newQuota->group_id = $id;
                    $newQuota->quota_mb = $quotaMb;
                    $newQuota->save();
                    error_log("✅ Cuota creada: group_id={$id}, quota_mb={$quotaMb}MB");
                }

                return [
                    'success' => true,
                    'quota_mb' => $quotaMb,
                    'message' => "Grupo actualizado con cuota {$quotaMb}MB"
                ];
            }

            return ['success' => false, 'error' => 'Error al actualizar grupo'];
        } catch (\Exception $e) {
            error_log('❌ GroupService::updateGroup ERROR: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    /**
     * Eliminar grupo
     */
    public static function deleteGroup($id)
    {
        try {
            $userGroupCount = UserGroup::count([
                'conditions' => 'group_id = ?1',
                'bind' => [1 => $id]
            ]);

            if ($userGroupCount > 0) {
                error_log("❌ No se puede eliminar grupo {$id}. Hay {$userGroupCount} usuario(s) asignado(s)");
                return [
                    'success' => false,
                    'error' => "No se puede eliminar. Hay {$userGroupCount} usuario(s) asignado(s)"
                ];
            }

            $quota = GroupQuota::findFirst([
                'conditions' => 'group_id = ?1',
                'bind' => [1 => $id]
            ]);

            if ($quota) {
                $quota->delete();
                error_log("✅ Cuota eliminada para grupo {$id}");
            }

            $group = Group::findFirst($id);
            if (!$group) {
                return ['success' => false, 'error' => 'Grupo no encontrado'];
            }

            if ($group->delete()) {
                error_log("✅ Grupo eliminado: ID={$id}, Nombre={$group->name}");
                return ['success' => true, 'message' => 'Grupo eliminado exitosamente'];
            }

            return ['success' => false, 'error' => 'Error al eliminar grupo'];
        } catch (\Exception $e) {
            error_log('❌ GroupService::deleteGroup ERROR: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener cuota de un grupo
     */
    public static function getGroupQuota($groupId)
    {
        try {
            $quota = GroupQuota::findFirst([
                'conditions' => 'group_id = ?1',
                'bind' => [1 => $groupId]
            ]);

            return $quota ? (int)$quota->quota_mb : 0;
        } catch (\Exception $e) {
            error_log('GroupService::getGroupQuota - ' . $e->getMessage());
            return 0;
        }
    }
}
