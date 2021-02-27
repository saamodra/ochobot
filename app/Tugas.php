<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tugas extends Model {
    protected $table = 'tugas';

    protected $primaryKey = 'id_tugas';

    public $timestamps = false;

    protected $fillable = [
        'judul', 'due_date', 'deskripsi', 'id_matkul', 'link_modul'
    ];

    /**
     * Get the matkul that owns the tugas.
     */
    public function matkul()
    {
        return $this->belongsTo(Matkul::class, 'id_matkul', 'id_matkul');
    }
}