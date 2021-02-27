<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Gateway\TugasGateway;
use App\Tugas;
use App\Matkul;
use App\Semester;
use DB;

class TugasController extends Controller
{

    public function rules() {
        return [
            'judul' => 'required|unique:tugas',
            'due_date' => 'required|date',
            'id_matkul' => 'required',
            'link_modul' => 'required',
        ];
    }

    protected $pesan = [
        'required' => 'Kolom :attribute wajib diisi.',
        'unique' => ':attribute sudah ada.',
        'date' => 'Due date tidak valid.'
    ];

    public function getAllTugas() {
        $tugas = Tugas::with('matkul', 'matkul.semester')->where('row_status', 1)->get();

        return response([
            'success' => true,
            'message' => '',
            'data' => $tugas
        ]);
    }

    public function getTugas($id)
    {
        try {
            $tugas = Tugas::with('matkul', 'matkul.semester')->findOrFail($id);

            return response([
                'status' => 'success',
                'message' => '',
                'data' => $tugas
            ], 200);
        } catch(ModelNotFoundException $e) {
            return response([
                'status' => 'failed',
                'message' => 'ID tugas tidak ditemukan',
                'data' => $id
            ], 404);
        }
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), $this->rules(), $this->pesan);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message'=> $validator->errors(),
                'data' => ''
            ], 500);
        } else {
            $tugas = Tugas::create($request->all());

            return response()->json([
                'status' => 'success',
                'message'=> 'Data berhasil ditambahkan.',
                'data' => $tugas
            ], 200);
        }
    }

    public function update(Request $request, $id) {
        $rules = $this->rules();
        $rules['judul'] = $rules['judul'].',judul,'.$id.',id_tugas';
        $validator = Validator::make($request->all(), $rules, $this->pesan);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message'=> $validator->errors(),
                'data' => ''
            ], 500);
        } else {
            $tugas = Tugas::findOrFail($id);
            $tugas->update($request->all());

            return response()->json([
                'status' => 'success',
                'message'=> 'Data berhasil diupdate.',
                'data' => $tugas
            ], 200);
        }
    }

    public function destroy($id)
    {
        try {
            $tugas = Tugas::findOrFail($id);
            $tugas->row_status = 0;
            $tugas->update();

            return response([
                'status' => 'success',
                'message' => 'Data berhasil dihapus.',
                'data' => $tugas
            ], 200);
        } catch(ModelNotFoundException $e) {
            return response([
                'status' => 'failed',
                'message' => 'ID tugas tidak ditemukan',
                'data' => $id
            ], 404);
        }

    }

}
