<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model {
    protected $table = 'semester';

    protected $primaryKey = 'id_semester';

    public $timestamps = false;

    /**
     * Get the matkul for the semester.
     */
    public function matkul()
    {
        return $this->hasMany(Matkul::class, 'id_semester');
    }
}