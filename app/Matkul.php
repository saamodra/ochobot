<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Matkul extends Model {
    protected $table = 'matkul';

    protected $primaryKey = 'id_matkul';

    public $timestamps = false;
}