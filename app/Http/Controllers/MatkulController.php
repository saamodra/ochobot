<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Matkul;
use DB;

class MatkulController extends Controller
{
    public function getMatkul() {
        $matkul = Matkul::get();

        return response([
            'success' => true,
            'data' => $matkul
        ]);
    }

    public function showMatkul($id) {
        $matkul = Matkul::findOrFail($id);

        return response([
            'success' => true,
            'data' => $matkul
        ]);
    }
}

