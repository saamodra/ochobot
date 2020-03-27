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
            ->orderBy('due_date')
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
        
        return (($years != 0) ? $years." tahun " : "").(($months != 0) ? $months." bulan " : "").(($days != 0) ? $days." hari " : "")
        .(($hours) ? $hours. " jam " : "").(($minutes) ? $minutes." menit ": "").(($seconds) ? $seconds." detik ": "");
    }
}
