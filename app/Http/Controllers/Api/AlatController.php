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
        'Lab. TK Barat I/01'            => 'Gedung Elektronika',
        'Lab. TK Barat I/02'            => 'Gedung Elektronika',
        'Lab. TK Barat I/04'            => 'Gedung Elektronika',
        'Lab. TK Timur I/01'            => 'Gedung Telekomunikasi',
        'Lab. TK Timur I/02'            => 'Gedung Telekomunikasi',
        'Lab. TK Timur II/01'           => 'Gedung Telekomunikasi',
        'Lab. Komp. (MST lt 2)'         => 'Gedung Magister Terapan',
        'Lab. IoT (MST lt 3)'           => 'Gedung Magister Terapan',
        'Lab. BC 01 (UPT lt 3)'         => 'Gedung UPT Bahasa', 
        'Lab. BC 02 (UPT lt 3)'         => 'Gedung UPT Bahasa', 
    ];

    // Endpoint baru untuk Frontend mengambil daftar dropdown
    public function getRuanganList()
    {
        return response()->json(array_keys($this->daftarRuangan));
    }

    public function index(Request $request) 
    {
        $role = $request->query('role');
        $search = $request->query('search');
        $lab_group = $request->query('lab'); 
        $for_peminjaman = $request->query('for_peminjaman');

        $query = Alat::query(); 

        // 1. FILTER BERDASARKAN GEDUNG
        if ($lab_group) {
            $ruanganTerkait = array_keys($this->daftarRuangan, $lab_group);
            if (!empty($ruanganTerkait)) {
                $query->whereIn('letak', $ruanganTerkait);
            } else {
                $query->where('letak', 'like', "%{$lab_group}%");
            }
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

        // 4. Response untuk Staff/Dosen
        if ($role === 'staff' || $role === 'dosen' || ($role === 'mahasiswa' && !$lab_group)) {
            $data = $query->latest()->get();
            return response()->json($data, 200);
        }

        // 5. Response Agregasi (Pilihan Alat Mahasiswa)
        $dataAgregasi = Alat::where('kondisi', 'baik')
        ->when($lab_group, function($q) use ($ruanganTerkait, $lab_group) {
            return !empty($ruanganTerkait) ? $q->whereIn('letak', $ruanganTerkait) : $q->where('letak', 'like', "%{$lab_group}%");
        })
        ->when($search, function($q) use ($search) {
            return $q->where('nama_alat', 'like', "%{$search}%");
        })
        ->withCount([
            'peminjamanDetails as sedang_dipinjam_count' => function ($q) {
                $q->whereHas('peminjaman', function ($sub) {
                    $sub->whereIn(DB::raw('LOWER(status)'), ['pending', 'approved', 'ongoing', 'disetujui', 'dipinjam', 'booking']);
                });
            }
        ])
        ->withSum(['peminjamanDetails as total_qty_dipinjam' => function ($q) {
            $q->whereHas('peminjaman', function ($sub) {
                $sub->whereIn(DB::raw('LOWER(status)'), ['pending', 'approved', 'ongoing', 'disetujui', 'dipinjam', 'booking']);
        });
        }], 'jumlah_pinjam') 
        ->get();

        $result = $dataAgregasi
            ->groupBy(fn($item) => $item->nama_alat . '|' . $item->letak)
            ->map(function ($group) {

        $first = $group->first();

        // UNIT YANG TERSEDIA (tidak sedang dipinjam atau dibooking)
        $unitTersedia = $group->filter(fn($item) => $item->sedang_dipinjam_count == 0);

        if ($first->is_aset) {
            // ASET: hitung dari unit bebas
            $stokTersedia = $unitTersedia->count();

        } else {

            // NON ASET
            $totalFisik = $group->sum('jumlah');
            $totalDipinjam = $group->sum(function($item) {
                    return (int) $item->total_qty_dipinjam;
                });

            $stokTersedia = $totalFisik - $totalDipinjam;
        }

        return [
            'id'            => $first->id,
            'nama_alat'     => $first->nama_alat,
            'letak'         => $first->letak,
            'jumlah'        => max(0, (int) $stokTersedia), 
            'kode_tag_list' => $unitTersedia->pluck('kode_tag')->filter()->values()->all(),
            'is_aset'       => (bool) $first->is_aset
        ];
        })
        ->values();

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
            
            $isAset = !empty($validated['kode_tag']) ? 1 : 0;

            $alat = Alat::create([
                'nama_alat' => strtoupper($validated['nama_alat']),
                'letak'     => $validated['letak'],
                'kode_tag'  => $validated['kode_tag'] ?? null, 
                'jumlah'    => $validated['jumlah'],
                'kondisi'   => $kondisi,
                'is_aset'   => $isAset,
            ]);

            return response()->json(['message' => 'Data berhasil ditambahkan', 'data' => $alat], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Kode Tag Tidak Boleh Sama', 'error' => $e->getMessage()], 500);
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

        $sedangDipinjam = DB::table('peminjaman__details')
            ->join('peminjaman', 'peminjaman__details.peminjaman_id', '=', 'peminjaman.id')
            ->where('peminjaman__details.alat_id', $id)
            ->where('peminjaman.status', 'dipinjam') 
            ->exists();

        if ($sedangDipinjam) {
            return response()->json([
                'message' => 'Gagal! Alat sedang digunakan oleh mahasiswa dan belum dikembalikan.'
            ], 422);
        }

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