<?php

namespace App\Models;

use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\PresenceOf;

/**
 * Modelo para cuota específica de grupo
 */
class GroupQuota extends Model
{
    public $id;
    public $group_id;
    public $quota_mb;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('group_quotas');
        $this->belongsTo('group_id', 'App\Models\Group', 'id', ['alias' => 'group']);
    }

    /**
     * ⭐ VALIDACIONES CORREGIDAS
     */
    public function validation()
    {
        $validator = new Validation();

        // Validar que group_id existe
        $validator->add('group_id', new PresenceOf([
            'message' => 'El grupo es requerido'
        ]));

        // Validar que quota_mb sea numérico
        $validator->add('quota_mb', new Numericality([
            'message' => 'La cuota debe ser numérica'
        ]));

        return $this->validate($validator);
    }

    /**
     * Antes de guardar
     */
    public function beforeSave()
    {
        // Convertir a entero
        $this->quota_mb = (int)$this->quota_mb;

        // Validar que sea >= 0
        if ($this->quota_mb < 0) {
            $this->quota_mb = 0;
        }

        // Actualizar fecha de modificación
        $this->updated_at = date('Y-m-d H:i:s.u');
    }

    /**
     * Antes de crear
     */
    public function beforeCreate()
    {
        // Establecer fecha de creación
        $this->created_at = date('Y-m-d H:i:s.u');
        $this->updated_at = date('Y-m-d H:i:s.u');
    }

    /**
     * Después de guardar
     */
    public function afterSave()
    {
        // Log para debugging
        error_log("✅ GroupQuota guardada: group_id={$this->group_id}, quota_mb={$this->quota_mb}");
    }
}
