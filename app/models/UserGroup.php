<?php

namespace App\Models;

use Phalcon\Mvc\Model;

// Modelo para asociar usuarios a grupos
class UserGroup extends Model
{
    public $id;
    public $user_id;
    public $group_id;
    public $created_at;

    public function initialize()
    {
        $this->setSource('user_groups');
        $this->belongsTo('user_id', 'App\Models\User', 'id', ['alias' => 'user']);
        $this->belongsTo('group_id', 'App\Models\Group', 'id', ['alias' => 'group']);
    }
}
