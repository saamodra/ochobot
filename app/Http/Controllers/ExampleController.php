<?php

namespace App\Http\Controllers;
use DB;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getTugasMatkul(int $matkulId)
    {
        $tugas = app('db')->table('tugas')
            ->where('tugas.id_matkul', $matkulId)
            ->where('due_date', '>=', DB::raw('now() AT TIME ZONE \'Asia/Jakarta\''))
            ->join('matkul', 'matkul.id_matkul', 'tugas.id_matkul')
            ->get();
 
        if ($tugas) {
            return $tugas;
        }
 
        return null;
    }

    public function getAllMatkul() {
        $matkul = app('db')->table('matkul')->get();

        if($matkul) {
            return $matkul;
        }

        return null;
    }
}
