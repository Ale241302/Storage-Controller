<?php

namespace App\Models;

use Phalcon\Mvc\Model;

// Modelo para almacenar información de archivos subidos
class File extends Model
{
    public $id;
    public $user_id;
    public $filename;
    public $original_filename;
    public $file_path;
    public $file_size;
    public $mime_type;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('files');
        $this->belongsTo('user_id', 'App\Models\User', 'id', ['alias' => 'user']);
    }
}
