<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;

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
            ->where('id_matkul', $matkulId)
            ->whereDate('due_date', '>=', NOW())
            ->join('matkul', 'matkul.id_matkul', 'tugas.id_matkul')
            ->get();
 
        if ($tugas) {
            return (array) $tugas;
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