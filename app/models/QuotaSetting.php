<?php

namespace App\Models;

use Phalcon\Mvc\Model;

// Modelo para configuración global de cuotas
class QuotaSetting extends Model
{
    public $id;
    public $setting_key;
    public $setting_value;
    public $description;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('quota_settings');
    }
}
