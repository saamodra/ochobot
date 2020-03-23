<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;

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
        $matkul = $this->db->table('matkul')
            ->where('id', $matkulId)
            ->first();
 
        if ($matkul) {
            return (array) $matkul;
        }
 
        return null;
    }

    function getAllMatkul() {
        $matkul = $this->db->table('matkul')->all();

        if($matkul) {
            return (array) $matkul;
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