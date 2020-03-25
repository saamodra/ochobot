<?php

namespace App\Gateway;
use Illuminate\Database\ConnectionInterface;
use DB;

class TugasGateway {
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct() {
        $this->db = app('db');
    }

    // Matkul
    function getTugas(int $tugasId)
    {
        $tugas = $this->db->table('tugas')
            ->where('id_tugas', $tugasId)
            ->first();
 
        if ($tugas) {
            return (array) $tugas;
        }
 
        return null;
    }

    function getTugasMatkul(int $matkulId)
    {
        $tugas = $this->db->table('tugas')
            ->where('tugas.id_matkul', $matkulId)
            ->where('due_date', '>=', DB::raw('now() AT TIME ZONE \'Asia/Jakarta\''))
            ->join('matkul', 'matkul.id_matkul', 'tugas.id_matkul')
            ->get();

        if($tugas) {
            return $tugas;
        }

        return null;
    }
    
    function getAllTugas() {
        $tugas = $this->db->table('tugas')->all();

        if($tugas) {
            return (array) $tugas;
        }

        return null;
    }
}