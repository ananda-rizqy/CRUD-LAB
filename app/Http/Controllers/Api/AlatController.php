<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlatController extends Controller
{

    // Tambahkan daftar ruangan yang diizinkan agar konsisten
    protected $daftarRuangan = [
        'Laboratorium Barat',
        'Laboratorium Timur',
        'Laboratorium Broadcast',
        'Laboratorium MST',
    ];

    // Endpoint baru untuk Frontend mengambil daftar dropdown
    public function getRuanganList()
    {
        return response()->json($this->daftarRuangan);
    }

    public function index(Request $request) 
{
    $role = $request->query('role');
    $search = $request->query('search');
    $laboratorium = $request->query('lab'); 
    $for_peminjaman = $request->query('for_peminjaman');

    $query = Alat::query(); 

    // 1. Filter Laboratorium
    if ($laboratorium) {
        $query->where('letak', 'like', "%{$laboratorium}%");
    }

    // 2. Filter Pencarian
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('nama_alat', 'like', "%{$search}%")
              ->orWhere('kode_tag', 'like', "%{$search}%")
              ->orWhere('letak', 'like', "%{$search}%");
        });
    }

    if ($role !== 'staff') {
            $query->where('kondisi', 'baik')->where('jumlah', '>', 0);
        }

    // 3. Filter Anti-Tabrakan Peminjaman
    if ($for_peminjaman) {
        $query
              ->whereDoesntHave('peminjamans', function ($q) {
                  // Alat dianggap tidak tersedia jika statusnya salah satu di bawah ini
                  $q->whereIn('status', ['pending', 'approved', 'ongoing']);
              });
    }

    // 4. Response untuk Staff atau List Umum (Tanpa Agregasi)
    if ($role === 'staff' || $role === 'dosen' || ($role === 'mahasiswa' && !$laboratorium)) {
        $data = $query->latest()->get();
        return response()->json($data, 200);
    }

    // 5. Response Agregasi (Pilihan Alat Mahasiswa)
    $queryAgregasi = $query->select(
            DB::raw('MIN(id) as id'), 
            'nama_alat', 
            'letak',
            DB::raw('SUM(jumlah) as total_stok'),
            DB::raw('GROUP_CONCAT(CASE WHEN kode_tag IS NOT NULL THEN kode_tag END SEPARATOR ",") as daftar_kode_tag')
        )
        ->where('kondisi', 'baik') 
        ->groupBy('nama_alat', 'letak')
        ->get();
        
    $result = $queryAgregasi->map(function ($item) {
        return [
            'id' => $item->id,
            'nama_alat' => $item->nama_alat,
            'letak' => $item->letak,
            'jumlah' => (int) $item->total_stok,
            'kode_tag_list' => $item->daftar_kode_tag ? explode(',', $item->daftar_kode_tag) : [],
            'is_aset' => !empty($item->daftar_kode_tag)
        ];
    }); 
    
    return response()->json($result, 200);
}

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_alat' => 'required|string|max:255',
                'letak'     => 'required|string|max:255',
                'kode_tag'  => 'nullable|string|unique:alats,kode_tag',
                'jumlah'    => 'required|integer|min:1',
                'kondisi'   => 'required|in:baik,rusak',
            ]);

            if (!empty($validated['kode_tag'])) {
            if ($validated['jumlah'] > 1) {
                return response()->json([
                    'message' => 'Gagal tambah data',
                    'error'   => 'Alat dengan kode tag unik jumlahnya tidak boleh lebih dari 1.'
                ], 422);
            }
        }

            $kondisi = empty($validated['kode_tag']) ? 'baik' : $validated['kondisi'];
            
            $alat = Alat::create([
                'nama_alat' => strtoupper($validated['nama_alat']),
                'letak'     => $validated['letak'],
                'kode_tag'  => $validated['kode_tag'] ?? null, 
                'jumlah'    => $validated['jumlah'],
                'kondisi'   => $kondisi,
            ]);

            return response()->json(['message' => 'Data berhasil ditambahkan', 'data' => $alat], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal tambah data', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_alat' => 'required|string',
            'letak'     => 'required|string',
            'kode_tag'  => 'nullable|string|unique:alats,kode_tag,'.$id,
            'jumlah'    => 'required|integer|min:0',
            'kondisi'   => 'required|in:baik,rusak',
        ]);

        try {
            $alat = Alat::findOrFail($id);
            if (!empty($validated['kode_tag'])) {
                $validated['jumlah'] = 1;
            }
            $kondisi = empty($validated['kode_tag']) ? 'baik' : $validated['kondisi'];

            $alat->update([
                'nama_alat' => strtoupper($validated['nama_alat']),
                'letak'     => $validated['letak'],
                'kode_tag'  => $validated['kode_tag'],
                'jumlah'    => $validated['jumlah'],
                'kondisi'   => $kondisi,
            ]);

            return response()->json(['message' => 'Data berhasil diperbarui', 'data' => $alat]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal update data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
{
    try {
        $alat = Alat::findOrFail($id);

        // 1. Cek apakah alat sedang aktif dipinjam (status belum 'kembali')
        // Kita join tabel peminjaman__details dengan tabel peminjaman
        $sedangDipinjam = DB::table('peminjaman__details')
            ->join('peminjaman', 'peminjaman__details.peminjaman_id', '=', 'peminjaman.id')
            ->where('peminjaman__details.alat_id', $id)
            ->where('peminjaman.status', 'dipinjam') // Pastikan string 'dipinjam' sesuai dengan DB kamu
            ->exists();

        if ($sedangDipinjam) {
            return response()->json([
                'message' => 'Gagal! Alat sedang digunakan oleh mahasiswa dan belum dikembalikan.'
            ], 422);
        }

        // 2. Jika tidak sedang dipinjam, kita hapus riwayat lamanya dulu (agar tidak error constraint)
        // baru kemudian hapus alatnya.
        DB::table('peminjaman__details')->where('alat_id', $id)->delete();
        
        $alat->delete();

        return response()->json([
            'message' => 'Alat berhasil dihapus (Riwayat peminjaman lama telah dibersihkan).'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Gagal menghapus data',
            'error' => $e->getMessage()
        ], 500);
    }
}
}