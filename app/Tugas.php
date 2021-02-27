<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tugas extends Model {
    protected $table = 'tugas';

    protected $primaryKey = 'id_tugas';

    public $timestamps = false;
}