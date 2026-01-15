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

    public function getAlatByLab($kode_lab)
{
    // Kita ubah 'kode-lab' (dari URL) kembali jadi nama asli untuk pencarian di DB
    // Contoh: 'lab-timur' jadi 'Lab Timur'
    $namaLabAsli = str_replace('-', ' ', $kode_lab);

    // Ambil semua alat yang ada di ruangan tersebut
    $alats = Alat::where('ruang_lab', 'LIKE', '%' . $namaLabAsli . '%')->get();

    if ($alats->isEmpty()) {
        return response()->json([
            'status' => 'empty',
            'message' => 'Lab tidak ditemukan',
            'info_lab' => ['nama' => ucwords($namaLabAsli)]
        ], 200);
    }

    return response()->json([
        'status' => 'sukses',
        'info_lab' => [
            'nama' => ucwords($namaLabAsli),
            'total_jenis_alat' => $alats->count()
        ],
        'daftar_alat' => $alats // Mahasiswa akan melihat daftar ini di HP mereka
    ]);
}
    
    public function daftarLab()
    {
    // Mengambil nama ruang_lab yang unik dari tabel alats
    $labs = \App\Models\Alat::select('ruang_lab')
                ->distinct()
                ->whereNotNull('ruang_lab')
                ->get();

    // Memetakan (mapping) data agar setiap lab memiliki link QR masing-masing
    $dataWithQR = $labs->map(function ($item) {
        $kodeLab = strtolower(str_replace(' ', '-', $item->ruang_lab));
        return [
            'ruang_lab' => $item->ruang_lab,
            'kode_scan' => $kodeLab,
            'link_cetak_qr' => "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $kodeLab
        ];
    });

    return response()->json([
        'status' => 'sukses',
        'total_lab' => $dataWithQR->count(),
        'daftar_lab' => $dataWithQR
        ]);
    }
}