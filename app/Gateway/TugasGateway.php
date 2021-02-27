<?php

namespace App\Gateway;
use Illuminate\Database\ConnectionInterface;
use App\Tugas;
use App\Matkul;
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
        $tugas = Tugas::with('matkul', 'matkul.semester')
            ->where('id_tugas', $tugasId)
            ->first();

        if ($tugas) {
            return (array) $tugas;
        }

        return null;
    }

    function getTugasMatkul(int $matkulId)
    {
        $tugas = Tugas::with('matkul', 'matkul.semester')
            ->where('tugas.id_matkul', $matkulId)
            ->where('due_date', '>=', DB::raw('now() AT TIME ZONE \'Asia/Jakarta\''))
            ->where('row_status', 1)
            ->orderBy('due_date')
            ->get();

        if($tugas) {
            return $tugas;
        }

        return null;
    }

    function getAllTugas() {
        $tugas = Tugas::with('matkul', 'matkul.semester')
            ->where('due_date', '>=', DB::raw('now() AT TIME ZONE \'Asia/Jakarta\''))
            ->where('row_status', 1)
            ->orderBy('due_date')
            ->get();

        if($tugas) {
            return $tugas;
        }

        return null;
    }

    public function getIdMatkul($nama) {
        $matkul = Matkul::where('nama_matkul', $nama)->first();

        return $matkul->id_matkul;
    }

    public function datedifference($date1) {
        date_default_timezone_set('Asia/Jakarta');

        $date2 = strtotime("now");

        $diff = abs($date2 - $date1);

        $years = floor($diff / (365*60*60*24));

        $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));

        $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
        $hours = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24) / (60*60));
        $minutes = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);

        $seconds = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60));

        return (($years != 0) ? $years."t " : "").(($months != 0) ? $months."b " : "").(($days != 0) ? $days."h " : "")
        .(($hours != 0) ? $hours. "j " : "").(($minutes) ? $minutes."m ": "").(($minutes <= 1) ? $seconds."detik": "");
    }
}