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

    /**
     * Get the semester that owns the matkul.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester');
    }

    /**
     * Get the tugas for the matkul.
     */
    public function tugas()
    {
        return $this->hasMany(Tugas::class, 'id_matkul');
    }

}