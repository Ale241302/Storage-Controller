<?php

namespace App\Models;

use Phalcon\Mvc\Model;

// Modelo para gestionar usuarios del sistema
class User extends Model
{
    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $quota_mb;           // ⭐ AGREGAR ESTA LÍNEA
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        // Especificar tabla exacta
        $this->setSource('users');

        // Relaciones con otras tablas
        $this->hasMany('id', 'App\Models\File', 'user_id', ['alias' => 'files']);
        $this->hasMany('id', 'App\Models\UserGroup', 'user_id', ['alias' => 'groups']);
    }

    public function beforeSave()
    {
        // Actualizar fecha de modificación
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function beforeCreate()
    {
        // Establecer fecha de creación
        $this->created_at = date('Y-m-d H:i:s');

        // ⭐ Si no tiene cuota, asignar 100 MB por defecto
        if (empty($this->quota_mb)) {
            $this->quota_mb = 100;
        }
    }
}
