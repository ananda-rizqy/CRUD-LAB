<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PeminjamanController extends Controller
{
    // MAHASISWA: Mengajukan Peminjaman (Proses Keranjang)
    public function store(Request $request)
    {
        $request->validate([
            'ruangan_lab' => 'required|string',
            'tujuan'      => 'required|string',
            'items'       => 'required|array|min:1', 
            'items.*.id'  => 'required|exists:alats,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Simpan Peminjaman
                $peminjaman = Peminjaman::create([
                    'user_id'           => Auth::id(), 
                    'ruangan_lab'       => $request->ruangan_lab,
                    'tujuan_penggunaan' => $request->tujuan, 
                    'waktu_pinjam'      => now(),
                    'status'            => 'pending',
                ]);

                // Simpan Detail (Isi Keranjang)
                foreach ($request->items as $item) {
                    PeminjamanDetails::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_id'       => $item['id'],
                        'jumlah_pinjam' => $item['qty'],
                    ]);
                }

                return response()->json([
                    'status'  => 'sukses',
                    'message' => 'Peminjaman berhasil diajukan! Menunggu persetujuan staff.',
                    'data'    => $peminjaman->load('details.alat')
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan peminjaman: ' . $e->getMessage()
            ], 500);
        }
    }

    // STAFF: Melihat semua daftar pinjaman
    public function index()
    {
        // Mengambil data peminjaman beserta detail alat di dalamnya
        $peminjaman = Peminjaman::with(['user', 'details.alat'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'ongoing', 'returned', 'rejected')")
            ->latest()
            ->get();

        return response()->json([
            'status' => 'sukses',
            'data'   => $peminjaman
        ]);
    }

    // STAFF: Menyetujui Pengajuan (Sekaligus Potong Stok Semua Alat)
    public function setujui($id)
    {
        return DB::transaction(function () use ($id) {
            $pinjam = Peminjaman::with('details.alat')->findOrFail($id);

            if ($pinjam->status !== 'pending') {
                return response()->json(['message' => 'Status bukan pending.'], 400);
            }

            // Cek stok untuk semua alat di keranjang sebelum menyetujui
            foreach ($pinjam->details as $detail) {
                if ($detail->alat->jumlah < $detail->jumlah_pinjam) {
                    return response()->json([
                        'message' => "Stok alat {$detail->alat->nama_alat} tidak mencukupi!"
                    ], 400);
                }
            }

            // Jika semua stok aman, update status dan potong stok
            $pinjam->update(['status' => 'approved']);

            foreach ($pinjam->details as $detail) {
                $detail->alat->decrement('jumlah', $detail->jumlah_pinjam);
            }

            return response()->json(['message' => 'Peminjaman disetujui. Stok alat telah dikurangi.']);
        });
    }

    // MAHASISWA: Upload Foto Sebelum (Ubah status ke Ongoing)
    public function uploadBefore(Request $request, $id)
{
    $request->validate([
        'foto_before' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $pinjam = Peminjaman::findOrFail($id);

    // Keamanan: Pastikan hanya peminjaman yang sudah disetujui yang bisa upload foto
    if ($pinjam->status !== 'approved') {
        return response()->json(['message' => 'Peminjaman belum disetujui staff atau sudah berjalan.'], 400);
    }

    if ($request->hasFile('foto_before')) {
        $file = $request->file('foto_before');
        $namaFile = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('peminjaman/before', $namaFile, 'public');

        $pinjam->update([
            'foto_before' => $path,
            'tanggal_diambil' => now(),
            'status' => 'ongoing' 
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Foto berhasil diunggah. Selamat praktikum!',
            'data' => $pinjam
        ]);
    }

    return response()->json(['message' => 'File tidak ditemukan.'], 400);
    }

    // MAHASISWA: Pengembalian (Status Kembali ke Tersedia)
    public function kembalikan(Request $request, $id)
    {
        $request->validate([
            'foto_after' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'kondisi_kembali' => 'required|string',
            'deskripsi_kerusakan' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $pinjam = Peminjaman::with('details.alat')->findOrFail($id);

            if ($pinjam->status !== 'ongoing') {
                return response()->json(['message' => 'Alat belum diambil atau sudah dikembalikan.'], 400);
            }

            $file = $request->file('foto_after');
            $path = $file->store('peminjaman/after', 'public');
            
            $pinjam->update([
                'status' => 'returned',
                'foto_after' => $path,
                'kondisi_kembali' => $request->kondisi_kembali,
                'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
                'waktu_kembali' => now(),
                'tanggal_kembali' => now(),
            ]);

            // Kembalikan stok alat
            foreach ($pinjam->details as $detail) {
                $detail->alat->increment('jumlah', $detail->jumlah_pinjam);
            }

            return response()->json(['message' => 'Alat berhasil dikembalikan. Stok bertambah otomatis.']);
        });
    }

    //Laporan Kerusakan Staff
   public function laporanRusak()
{
    try {
        $laporan = \App\Models\Peminjaman::with(['user', 'details.alat'])
            ->where('kondisi_kembali', 'rusak')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($pinjam) {
                // Ambil semua nama alat dalam tiket ini
                $daftarAlat = $pinjam->details->map(function($det) {
                    return $det->alat->nama_alat ?? 'Alat';
                })->implode(', ');

                $firstDetail = $pinjam->details->first();

                return [
                    'id' => $pinjam->id,
                    'nama_mahasiswa' => $pinjam->user->name ?? 'N/A',
                    'nama_alat' => $daftarAlat,
                    'kode_tag' => ($firstDetail && $firstDetail->alat) ? $firstDetail->alat->kode_tag : '-',
                    'ruangan_lab' => $pinjam->ruangan_lab, 
                    'deskripsi_kerusakan' => $pinjam->deskripsi_kerusakan ?? 'Tidak ada deskripsi',
                    'tanggal_kembali' => $pinjam->tanggal_kembali,
                    'foto_before' => $pinjam->foto_before ? asset('storage/' . $pinjam->foto_before) : null,
                    'foto_after' => $pinjam->foto_after ? asset('storage/' . $pinjam->foto_after) : null,
                ];
            });

        return response()->json([
            'status' => 'sukses',
            'data' => $laporan
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil laporan rusak: ' . $e->getMessage()
        ], 500);
    }
}

    // RIWAYAT MAHASISWA
    public function riwayatMahasiswa()
    {
        $riwayat = Peminjaman::with('details.alat')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'status' => 'sukses',
            'data'   => $riwayat
        ]);
    }
}