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
            return (array) $tugas;
        }
 
        return null;
    }
}
