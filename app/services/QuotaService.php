<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGroup;
use App\Models\GroupQuota;
use App\Models\QuotaSetting;
use App\Models\File;

class QuotaService
{
    /**
     * ⭐ OBTENER CUOTA EFECTIVA CON PRIORIDAD:
     * 1. Cuota personalizada de usuario (si existe y > 0)
     * 2. Máxima cuota entre todos los grupos (si usuario está en grupos)
     *    - Si TODOS los grupos tienen 0 MB → heredar de GLOBAL
     *    - Si algunos tienen > 0 → tomar el MÁXIMO
     * 3. Cuota global por defecto
     */
    public static function getQuotaInfo($userId)
    {
        try {
            $user = User::findFirst($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }

            // 1️⃣ PRIORIDAD 1: Cuota personalizada del usuario
            if ($user->quota_mb && $user->quota_mb > 0) {
                $quotaMb = (int)$user->quota_mb;
                $quotaType = 'user';
                $quotaSource = "Cuota personal";
            } else {
                // 2️⃣ PRIORIDAD 2: Buscar grupos del usuario
                $userGroups = UserGroup::find([
                    'user_id = :uid:',
                    'bind' => ['uid' => $userId]
                ]);

                $maxGroupQuota = null;
                $maxGroupName = null;
                $hasGroups = false;

                if ($userGroups) {
                    foreach ($userGroups as $ug) {
                        $hasGroups = true;

                        $groupQuota = GroupQuota::findFirst([
                            'group_id = :gid:',
                            'bind' => ['gid' => $ug->group_id]
                        ]);

                        // ⭐ CAMBIO: Si groupQuota existe, usarlo (incluso si es 0)
                        if ($groupQuota) {
                            $quotaValue = (int)$groupQuota->quota_mb;

                            // Si > 0, buscar el máximo entre grupos
                            if ($quotaValue > 0) {
                                if ($maxGroupQuota === null || $quotaValue > $maxGroupQuota) {
                                    $maxGroupQuota = $quotaValue;

                                    // ⭐ Obtener nombre del grupo para logging
                                    $group = \App\Models\Group::findFirst($ug->group_id);
                                    $maxGroupName = $group ? $group->name : "Grupo {$ug->group_id}";
                                }
                            } else {
                                // Si es 0, marcar que encontramos un grupo con herencia
                                // Pero seguir buscando otros grupos con cuota > 0
                                if ($maxGroupQuota === null) {
                                    $maxGroupQuota = 0;
                                    $group = \App\Models\Group::findFirst($ug->group_id);
                                    $maxGroupName = $group ? $group->name : "Grupo {$ug->group_id}";
                                }
                            }
                        }
                    }
                }

                // ⭐ Si usuario está en grupos pero TODOS tienen 0 MB → heredar GLOBAL
                if ($hasGroups && $maxGroupQuota === 0) {
                    $quotaMb = self::getGlobalDefaultQuota();
                    $quotaType = 'global';
                    $quotaSource = "Cuota global (grupo heredando)";
                }
                // Si encontramos grupos con cuota > 0 → usar máximo
                else if ($maxGroupQuota !== null && $maxGroupQuota > 0) {
                    $quotaMb = (int)$maxGroupQuota;
                    $quotaType = 'group';
                    $quotaSource = "Grupo: $maxGroupName";
                }
                // Si no está en grupos → usar GLOBAL
                else {
                    $quotaMb = self::getGlobalDefaultQuota();
                    $quotaType = 'global';
                    $quotaSource = "Cuota global por defecto";
                }
            }

            // Calcular espacio usado
            $usedMb = self::getUsedStorage($userId);
            $availableMb = max(0, $quotaMb - $usedMb);
            $percentageUsed = $quotaMb > 0 ? round(($usedMb / $quotaMb) * 100, 2) : 0;

            return [
                'quota_mb' => $quotaMb,
                'used_mb' => $usedMb,
                'available_mb' => $availableMb,
                'percentage_used' => $percentageUsed,
                'quota_type' => $quotaType,
                'quota_source' => $quotaSource
            ];
        } catch (\Exception $e) {
            error_log('QuotaService::getQuotaInfo - ' . $e->getMessage());
            return [
                'quota_mb' => 100,
                'used_mb' => 0,
                'available_mb' => 100,
                'percentage_used' => 0,
                'quota_type' => 'global'
            ];
        }
    }

    /**
     * Obtener cuota global por defecto
     */
    public static function getGlobalDefaultQuota()
    {
        try {
            $setting = QuotaSetting::findFirst([
                'setting_key = :key:',
                'bind' => ['key' => 'default_quota_mb']
            ]);

            return $setting ? (int)$setting->setting_value : 100;
        } catch (\Exception $e) {
            error_log('QuotaService::getGlobalDefaultQuota - ' . $e->getMessage());
            return 100;
        }
    }

    /**
     * Obtener almacenamiento usado (en MB)
     */
    public static function getUsedStorage($userId)
    {
        try {
            $files = File::find([
                'user_id = :uid:',
                'bind' => ['uid' => $userId]
            ]);

            $totalBytes = 0;
            if ($files) {
                foreach ($files as $file) {
                    $totalBytes += (int)$file->file_size;
                }
            }

            return round($totalBytes / (1024 * 1024), 2);
        } catch (\Exception $e) {
            error_log('QuotaService::getUsedStorage - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si puede subir archivo
     */
    public static function canUploadFile($userId, $fileSizeBytes)
    {
        try {
            $quotaInfo = self::getQuotaInfo($userId);
            $fileSizeMb = round($fileSizeBytes / (1024 * 1024), 2);

            if ($fileSizeMb > $quotaInfo['quota_mb']) {
                return [
                    'allowed' => false,
                    'message' => "❌ El archivo ({$fileSizeMb}MB) excede tu cuota total ({$quotaInfo['quota_mb']}MB)"
                ];
            }

            if ($fileSizeMb > $quotaInfo['available_mb']) {
                return [
                    'allowed' => false,
                    'message' => "❌ No hay suficiente espacio. Disponible: {$quotaInfo['available_mb']}MB, Requerido: {$fileSizeMb}MB"
                ];
            }

            return ['allowed' => true];
        } catch (\Exception $e) {
            error_log('QuotaService::canUploadFile - ' . $e->getMessage());
            return ['allowed' => false, 'message' => 'Error validando cuota'];
        }
    }

    /**
     * Establecer cuota global
     */
    public static function setGlobalQuota($quotaMb)
    {
        try {
            $setting = QuotaSetting::findFirst([
                'setting_key = :key:',
                'bind' => ['key' => 'default_quota_mb']
            ]);

            if ($setting) {
                $setting->setting_value = $quotaMb;
                $setting->updated_at = date('Y-m-d H:i:s');
                $setting->update();
            } else {
                $setting = new QuotaSetting();
                $setting->setting_key = 'default_quota_mb';
                $setting->setting_value = $quotaMb;
                $setting->description = 'Cuota por defecto para nuevos usuarios';
                $setting->created_at = date('Y-m-d H:i:s');
                $setting->updated_at = date('Y-m-d H:i:s');
                $setting->save();
            }

            return ['success' => true, 'message' => "Cuota global actualizada a {$quotaMb}MB"];
        } catch (\Exception $e) {
            error_log('QuotaService::setGlobalQuota - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
