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
        $matkul = Matkul::with('semester')->get();

        if($matkul) {
            return $matkul;
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