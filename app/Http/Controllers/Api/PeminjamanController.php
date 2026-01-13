<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    /**
     * Fitur: Melihat semua riwayat peminjaman (Staff/Dosen)
     */
    public function index()
    {
        return response()->json(Peminjaman::with('alat')->get());
    }

    /**
     * Fitur: Mahasiswa mengajukan peminjaman awal
     * Status default adalah 'pending'
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alat_id'           => 'required|exists:alats,id',
            'nama_mahasiswa'    => 'required|string', 
            'nim'               => 'required|string', 
            'laboratorium'      => 'required|string',
            'tujuan_penggunaan' => 'required|string',
            'waktu_pinjam'      => 'required|date_format:Y-m-d H:i:s',
            'waktu_kembali'     => 'required|date_format:Y-m-d H:i:s|after:waktu_pinjam',
        ]);

        $peminjaman = Peminjaman::create($validated);
        
        // Refresh untuk apply accessor timezone
        $peminjaman = $peminjaman->fresh();

        return response()->json([
            'message' => 'Permohonan peminjaman berhasil dikirim ke Staff',
            'data'    => $peminjaman
        ], 201);
    }

    /**
     * Fitur: Staff Menyetujui Peminjaman
     * Efek: Status menjadi 'disetujui' dan stok fisik berkurang otomatis
     */
    public function setujui($id)
    {
        return DB::transaction(function () use ($id) {
            $pinjam = Peminjaman::findOrFail($id);
            $alat = Alat::findOrFail($pinjam->alat_id);

            if ($alat->tersedia <= 0) {
                return response()->json(['message' => 'Maaf, stok alat sedang kosong'], 400);
            }

            $alat->decrement('tersedia');
            $pinjam->update(['status' => 'disetujui']);
            
            // Refresh untuk apply accessor timezone
            $pinjam = $pinjam->fresh();

            return response()->json([
                'message' => 'Peminjaman disetujui, stok alat otomatis berkurang',
                'detail' => [
                    'nama_alat' => $alat->nama_alat,
                    'sisa_stok' => $alat->tersedia
                ],
                'data' => $pinjam
            ]);
        });
    }

    /**
     * Fitur: Mahasiswa Upload Foto Before (Kondisi Awal)
     * Syarat: Hanya bisa dilakukan jika status sudah 'disetujui'
     */
    public function uploadFotoBefore(Request $request, $id)
    {
        $pinjam = Peminjaman::findOrFail($id);

        if ($pinjam->status !== 'disetujui') {
            return response()->json(['message' => 'Tunggu persetujuan Staff sebelum upload foto'], 403);
        }

        $request->validate([
            'foto_before' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto_before')) {
            $file = $request->file('foto_before');
            $nama_file = time() . '_before_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/peminjaman'), $nama_file);
            
            $pinjam->update(['foto_before' => $nama_file]);
        }

        // Refresh untuk apply accessor timezone
        $pinjam = $pinjam->fresh();

        return response()->json([
            'message' => 'Foto kondisi awal berhasil diunggah',
            'data' => $pinjam
        ]);
    }

    /**
     * Fitur: Staff Mengonfirmasi Pengembalian Alat
     * Skenario: 
     * - Jika kondisi Baik: stok alat bertambah, status 'selesai'
     * - Jika kondisi Rusak: WAJIB mengisi deskripsi kerusakan untuk aduan
     * Efek: Stok alat kembali bertambah (+1) dan status menjadi 'selesai'
     */
    public function kembalikan(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $pinjam = Peminjaman::findOrFail($id);
            $alat = Alat::findOrFail($pinjam->alat_id);

            // Verifikasi bahwa alat memang dalam posisi sedang dipinjam
            if ($pinjam->status !== 'disetujui') {
                return response()->json([
                    'message' => 'Hanya peminjaman yang sudah disetujui yang bisa dikembalikan'
                ], 400);
            }

            // Validasi: Accept both lowercase and uppercase
            $request->validate([
                'foto_after' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'kondisi_kembali' => 'required|in:Baik,Rusak,baik,rusak',
                'deskripsi_kerusakan' => 'required_if:kondisi_kembali,Rusak,rusak|nullable|string|min:5',
            ], [
                'deskripsi_kerusakan.required_if' => 'Laporan aduan kerusakan WAJIB diisi jika alat dikembalikan dalam kondisi Rusak.',
                'deskripsi_kerusakan.min' => 'Deskripsi kerusakan minimal 5 karakter.',
            ]);

            // Normalize kondisi: rusak → Rusak, baik → Baik
            $kondisiKembali = ucfirst(strtolower($request->kondisi_kembali));

            // Simpan Bukti Foto Setelah Dipakai
            if ($request->hasFile('foto_after')) {
                $file = $request->file('foto_after');
                $nama_file = time() . '_after_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/peminjaman'), $nama_file);
                $pinjam->foto_after = $nama_file;
            }

            // Kembalikan stok ke inventori
            $alat->increment('tersedia'); 

            // Update dengan nilai yang sudah dinormalisasi
            $pinjam->update([
                'status' => 'selesai',
                'kondisi_kembali' => $kondisiKembali, //  Gunakan yang sudah dinormalisasi
                'deskripsi_kerusakan' => $kondisiKembali === 'Rusak' ? $request->deskripsi_kerusakan : null, // ✅ Check dengan normalized
                'tanggal_dikembalikan' => now()
            ]);
            
            // Refresh untuk apply accessor timezone
            $pinjam = $pinjam->fresh();

            // Response dengan nilai normalized
            $message = $kondisiKembali === 'Rusak' 
                ? 'Alat berhasil dikembalikan dengan laporan aduan kerusakan. Staff akan menindaklanjuti laporan Anda.' 
                : 'Alat berhasil dikembalikan dalam kondisi baik. Terima kasih telah merawat alat dengan baik.';

            return response()->json([
                'message' => $message,
                'detail' => [
                    'nama_alat' => $alat->nama_alat,
                    'sisa_stok_sekarang' => $alat->tersedia,
                    'kondisi_kembali' => $kondisiKembali, //  Gunakan normalized
                    'ada_laporan_kerusakan' => $kondisiKembali === 'Rusak' //  Check dengan normalized
                ],
                'data' => $pinjam
            ]);
        });
    }
}