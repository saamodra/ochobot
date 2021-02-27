<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Matkul extends Model {
    protected $table = 'matkul';

    protected $primaryKey = 'id_matkul';

    protected $fillable = [
        'nama_matkul', 'id_semester', 'sks', 'image', 'link_matkul'
    ];

    public $timestamps = false;
}