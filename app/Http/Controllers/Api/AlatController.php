<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use Illuminate\Http\Request;

class AlatController extends Controller
{
    public function index(Request $request) 
    {
        $query = Alat::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nama_alat', 'like', "%{$search}%")
                  ->orWhere('ruang_lab', 'like', "%{$search}%");
        }

        return response()->json($query->get(), 200);
    }

    public function store(Request $request) 
    {
        $validated = $request->validate([
            'nama_alat' => 'required',
            'ruang_lab' => 'required',
            'total'     => 'required|integer|min:0',
            'tersedia'  => 'required|integer|min:0|lte:total', // Aturan perbandingan
            'kondisi'   => 'required|string',
        ], [
            // Pesan kustom Bahasa Indonesia diletakkan di array kedua
            'tersedia.lte' => 'Jumlah alat tersedia tidak boleh melebihi total stok alat (:value).'
        ]);

        $alat = Alat::create($validated);
        
        return response()->json([
            'sukses' => true,
            'pesan' => 'Alat berhasil ditambah',
            'data'    => $alat
        ], 201);
    }

    public function show($id) 
    {
        $alat = Alat::findOrFail($id);
        return response()->json($alat, 200);
    }

    public function update(Request $request, $id) 
    {
        $alat = Alat::findOrFail($id);

        $validated = $request->validate([
            'nama_alat' => 'sometimes|required',
            'ruang_lab' => 'sometimes|required',
            'total'     => 'sometimes|required|integer|min:0',
            // Tambahkan lte:total di sini agar saat edit tetap tervalidasi
            'tersedia'  => 'sometimes|required|integer|min:0|lte:total',
            'kondisi'   => 'sometimes|required',
        ], [
            'tersedia.lte' => 'Jumlah alat tersedia tidak boleh melebihi total stok alat (:value).'
        ]);

        // Proses update data ke database
        $alat->update($validated);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data inventori berhasil diperbarui',
            'data'    => $alat
        ], 200);
    }

    public function destroy($id) 
    {
        $alat = Alat::findOrFail($id);
        $alat->delete();

        return response()->json([
            'sukses' => true,
            'pesan' => 'Alat berhasil dihapus dari inventori'
        ], 200);
    }
}