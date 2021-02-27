<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;
use App\Matkul;

class MatkulGateway {
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct() {
        $this->db = app('db');
    }

    // Matkul
    function getMatkul(int $matkulId)
    {
        $matkul = Matkul::with('semester')
            ->where('id_matkul', $matkulId)
            ->first();

        if ($matkul) {
            return (array) $matkul;
        }

        return null;
    }

    function getAllMatkul() {
        $semester = Semester::with('matkul')->where('status_semester', '1')->first();

        if($semester) {
            return $semester->matkul;
        }

        return null;
    }

    function isAnswerEqual(int $number, string $answer)
    {
        return $this->db->table('questions')
            ->where('number', $number)
            ->where('answer', $answer)
            ->exists();
    }
}