<?php

namespace App\Models;

use Phalcon\Mvc\Model;

// Modelo para cuota específica de usuario
class UserQuota extends Model
{
    public $id;
    public $user_id;
    public $quota_mb;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('user_quotas');
        $this->belongsTo('user_id', 'App\Models\User', 'id', ['alias' => 'user']);
    }
}
