<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use Illuminate\Http\Request;

class AlatController extends Controller
{
    public function index() {
        return response()->json(Alat::all());
    }

    // PASTIKAN FUNGSI INI ADA DAN TULISANNYA TEPAT 'store'
    public function store(Request $request) {
        // Validasi input sesuai kolom di Tugas Akhir [cite: 31, 217]
        $validated = $request->validate([
            'nama_alat'      => 'required',
            'ruang_lab' => 'required',
            'total'          => 'required|integer',
            'tersedia'       => 'required|integer',
            'kondisi'        => 'required',
        ]);

        $alat = Alat::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Alat berhasil ditambah',
            'data'    => $alat
        ], 201);
    }
        public function update(Request $request, $id) 
    {
        // Cari alat berdasarkan ID yang dikirim melalui URL
        $alat = Alat::findOrFail($id);

        // Validasi data baru dari Postman
        $validated = $request->validate([
            'nama_alat'      => 'sometimes|required',
            'ruang_lab_merk' => 'sometimes|required',
            'total'          => 'sometimes|required|integer',
            'tersedia'       => 'sometimes|required|integer',
            'kondisi'        => 'sometimes|required',
        ]);

        // Update data di database phpMyAdmin
        $alat->update($validated);

        return response()->json([
            'message' => 'Data inventori berhasil diperbarui',
            'data'    => $alat
        ], 200);
    }
}
