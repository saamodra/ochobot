<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;

class UserGateway {
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct() {
        $this->db = app('db');
    }

    public function getUser(string $userId) {
        $user = $this->db->table('users')
        ->where('user_id', $userId)
        ->first();

        if($user) {
            return (array) $user;
        }

        return null;
    }

    public function saveUser(string $userId, string $displayName) {
        $this->db->table('users')
        ->insert([
            'user_id' => $userId,
            'display_name' => $displayName
        ]);
    }

    function setUserState(string $userId, int $userState)
    {
        $this->db->table('users')
            ->update([
                'state' => $userState,
                'user_id' => $userId
            ]);
    }
}