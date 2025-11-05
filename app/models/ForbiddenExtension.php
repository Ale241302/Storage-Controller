<?php

namespace App\Models;

use Phalcon\Mvc\Model;

// Modelo para gestionar extensiones prohibidas
class ForbiddenExtension extends Model
{
    public $id;
    public $extension;
    public $description;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('forbidden_extensions');
    }

    public function beforeSave()
    {
        // Actualizar fecha de modificación
        $this->updated_at = date('Y-m-d H:i:s.u');
    }

    public function beforeCreate()
    {
        // Establecer fecha de creación
        $this->created_at = date('Y-m-d H:i:s.u');
        $this->updated_at = date('Y-m-d H:i:s.u');
    }
}
