<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Semester;
use DB;

class SemesterController extends Controller
{
    public function getAllSemester() {
        $semester = Semester::with('matkul', 'matkul.semester')->where('row_status', 1)->get();

        return response([
            'success' => true,
            'message' => '',
            'data' => $semester
        ]);
    }

    public function getSemester($id)
    {
        try {
            $semester = Semester::with('matkul', 'matkul.semester')->findOrFail($id);

            return response([
                'status' => 'success',
                'message' => '',
                'data' => $semester
            ], 200);
        } catch(ModelNotFoundException $e) {
            return response([
                'status' => 'failed',
                'message' => 'ID semester tidak ditemukan',
                'data' => $id
            ], 404);
        }
    }
}
