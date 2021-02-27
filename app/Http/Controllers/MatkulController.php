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

class MatkulController extends Controller
{

    public function rules($id = '') {
        return [
            'nama_matkul' => 'required|unique:matkul,nama_matkul,'. $id.',id_matkul',
            'id_semester' => 'required|numeric',
            'sks' => 'required|numeric',
            'image' => 'required',
            'link_matkul' => 'required',
        ];
    }

    protected $pesan = [
        'required' => 'Kolom :attribute tidak boleh kosong.',
        'unique' => ':attribute sudah ada.',
        'numeric' => 'Reminder harus diisi dengan angka.'
    ];

    public function getMatkul() {
        $matkul = Matkul::with('semester')->get();
        $tugasA = Tugas::with('matkul', 'matkul.semester')
            ->where('due_date', '>=', DB::raw('now() AT TIME ZONE \'Asia/Jakarta\''))
            ->orderBy('due_date')
            ->get();
        $tgGateway = new TugasGateway();
        $tugas = array();
        foreach($tugasA as $t) {
            $tugas[] = [
                'judul' => $t->judul,
                'deadline' => $tgGateway->datedifference(strtotime($t->due_date))."\n* ".date("d M Y h:i", strtotime($t->due_date)),
                'image' => $t->matkul->image,
                'linkmatkul' => $t->matkul->link_matkul,
                'linkmodul' => $t->link_modul,
            ];
        }

        return response([
            'success' => true,
            'message' => '',
            'data' => $tugas
        ]);
    }

    public function showMatkul($id)
    {
        try {
            $matkul = Matkul::with('semester')->findOrFail($id);

            return response([
                'status' => 'success',
                'message' => '',
                'data' => $matkul
            ], 200);
        } catch(ModelNotFoundException $e) {
            return response([
                'status' => 'failed',
                'message' => 'ID Matkul tidak ditemukan',
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
            $matkul = Matkul::create($request->all());

            return response()->json([
                'status' => 'success',
                'message'=> 'Data berhasil ditambahkan.',
                'data' => $matkul
            ], 200);
        }
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), $this->rules($id), $this->pesan);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message'=> $validator->errors(),
                'data' => ''
            ], 500);
        } else {
            $matkul = Matkul::findOrFail($id);
            $matkul->update($request->all());

            return response()->json([
                'status' => 'success',
                'message'=> 'Data berhasil diupdate.',
                'data' => $matkul
            ], 200);
        }
    }

    public function destroy($id)
    {
        try {
            $matkul = Matkul::findOrFail($id);
            $matkul->row_status = 0;
            $matkul->update();

            return response([
                'status' => 'success',
                'message' => 'Data berhasil dihapus.',
                'data' => $matkul
            ], 200);
        } catch(ModelNotFoundException $e) {
            return response([
                'status' => 'failed',
                'message' => 'ID Matkul tidak ditemukan',
                'data' => $id
            ], 404);
        }

    }

}
