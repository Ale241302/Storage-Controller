<?php

namespace App\Models;

use Phalcon\Mvc\Model;


class Group extends Model
{
    public $id;
    public $name;
    public $description;
    public $created_at;
    public $updated_at;


    public function initialize()
    {
        $this->setSource('groups');
        $this->hasMany('id', 'App\Models\UserGroup', 'group_id', ['alias' => 'users']);
        $this->hasMany('id', 'App\Models\GroupQuota', 'group_id', ['alias' => 'quotas']);
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function beforeCreate()
    {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
