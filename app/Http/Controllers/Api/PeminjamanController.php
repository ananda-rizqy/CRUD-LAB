<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Alat;
use App\Models\PenggunaanRuang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeminjamanController extends Controller
{
    //STAFF: Update data alat (sinkronisasi stok)//  
    public function updateAlat(Request $request, $id)
    {
        $request->validate([
            'kondisi' => 'required|in:Baik,Rusak,baik,rusak',
            'nama_alat' => 'nullable|string',
            'total' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $alat = Alat::findOrFail($id);

            $kondisiLama = ucfirst(strtolower($alat->kondisi));
            $kondisiBaru = ucfirst(strtolower($request->kondisi));

            $alat->update($request->all());

            if ($kondisiLama === 'Rusak' && $kondisiBaru === 'Baik') {
                $alat->update(['tersedia' => $alat->total]);
            } 
            else if ($kondisiLama === 'Baik' && $kondisiBaru === 'Rusak') {
                $alat->update(['tersedia' => 0]);
            }

            return response()->json([
                'status' => 'sukses',
                'message' => 'Kondisi alat diperbarui dan stok telah disinkronkan!',
                'data' => $alat
            ]);
        });
    }

    //MAHASISWA: Mengajukan Peminjaman//  
    public function store(Request $request)
    {
        $request->validate([
            'alat_id' => 'required|exists:alats,id',
            'tujuan'  => 'required|string|max:255',
        ]);

        try {
            $peminjaman = Peminjaman::create([
                'user_id'           => Auth::id(), 
                'alat_id'           => $request->alat_id,
                'tujuan_penggunaan' => $request->tujuan, 
                'waktu_pinjam'      => now()->toTimeString(),
                'status'            => 'pending',
            ]);

            return response()->json([
                'status'  => 'sukses',
                'message' => 'Peminjaman berhasil diajukan!',
                'data'    => $peminjaman
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    //STAFF: Melihat semua daftar pinjaman//  
    public function index()
    {
        $peminjaman = Peminjaman::with(['user', 'alat'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'sukses',
            'data'   => $peminjaman
        ]);
    }

    //STAFF: Menyetujui Pengajuan//  
    public function setujui($id)
    {
        return DB::transaction(function () use ($id) {
            $pinjam = Peminjaman::findOrFail($id);
            $alat = Alat::where('id', $pinjam->alat_id)->first();

            if ($pinjam->status !== 'pending') {
                return response()->json(['message' => 'Status peminjaman bukan pending.'], 400);
            }

            if ($alat->tersedia <= 0) {
                return response()->json([
                    'message' => "Stok alat {$alat->nama_alat} kosong di database (Tersedia: {$alat->tersedia})",
                ], 400);
            }

            $pinjam->update(['status' => 'approved']);
            $alat->decrement('tersedia');

            return response()->json(['message' => 'Peminjaman disetujui. Mahasiswa wajib upload foto kondisi awal.']);
        });
    }

    //MAHASISWA: Upload Foto Sebelum Ambil Alat//  
    public function uploadBefore(Request $request, $id)
    {
        $request->validate([
            'foto_before' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $pinjam = Peminjaman::findOrFail($id);

        if ($pinjam->user_id !== Auth::id()) {
            return response()->json(['message' => 'Bukan akses Anda.'], 403);
        }

        if ($pinjam->status !== 'approved') {
            return response()->json(['message' => 'Peminjaman belum disetujui staff.'], 400);
        }

        $file = $request->file('foto_before');
        $namaFile = time() . '_before_' . $id . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('peminjaman/before', $namaFile, 'public');
        
        $pinjam->update([
            'foto_before' => $path,
            'tanggal_diambil' => now(),
            'status' => 'ongoing'
        ]);

        return response()->json(['message' => 'Foto berhasil diunggah. Alat resmi dipinjam!']);
    }

    //MAHASISWA: Proses Pengembalian//  
    public function kembalikan(Request $request, $id)
    {
        $request->validate([
            'foto_after' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'kondisi_kembali' => 'required|in:Baik,Rusak,baik,rusak',
            'deskripsi_kerusakan' => 'required_if:kondisi_kembali,Rusak,rusak',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $pinjam = Peminjaman::findOrFail($id);
            $alat = $pinjam->alat;

            if ($pinjam->status === 'returned') {
                return response()->json(['message' => 'Alat sudah pernah dikembalikan.'], 400);
            }

            $file = $request->file('foto_after');
            $namaFile = time() . '_after_' . $id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('peminjaman/after', $namaFile, 'public');
            
            $kondisi = ucfirst(strtolower($request->kondisi_kembali));

            $pinjam->update([
                'status' => 'returned',
                'foto_after' => $path,
                'kondisi_kembali' => $kondisi,
                'deskripsi_kerusakan' => $kondisi === 'Rusak' ? $request->deskripsi_kerusakan : null,
                'tanggal_kembali' => now()->toDateString(),
                'waktu_kembali'     => now()->toTimeString(),
            ]);

            if ($kondisi === 'Baik') {
                $alat->increment('tersedia');
            } else {
                $alat->update(['kondisi' => 'Rusak']);
            }

            return response()->json(['message' => 'Alat berhasil dikembalikan. Terima kasih!']);
        });
    }
    
    public function riwayat()
{
    try {
        $riwayat = Peminjaman::with(['user', 'alat'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_mahasiswa' => $item->user->name ?? 'User Tidak Ada',
                    'nim' => $item->user->nim_nip ?? '-', 
                    'nama_alat' => $item->alat->nama_alat ?? 'Alat Terhapus',
                    'kode_alat' => $item->alat->kode ?? '-',
                    'tujuan_penggunaan' => $item->tujuan_penggunaan,
                    'status' => $item->status,    
                    'kondisi_kembali' => $item->kondisi_kembali,
                    'foto_before' => $item->foto_before ? asset('storage/' . $item->foto_before) : null,
                    'foto_after' => $item->foto_after ? asset('storage/' . $item->foto_after) : null,
                ];
            });

        return response()->json([
            'status' => 'sukses',
            'data' => $riwayat
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal Sinkronisasi: ' . $e->getMessage()
        ], 500);
    }
}
public function laporanRusak()
{
    try {
        $data = Peminjaman::with(['user', 'alat'])
            ->where('kondisi_kembali', 'rusak') 
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_mahasiswa' => $item->user->name ?? 'N/A',
                    'nama_alat' => $item->alat->nama_alat ?? 'N/A',
                    'kode_alat' => $item->alat->kode ?? '-',
                    'deskripsi_kerusakan' => $item->deskripsi_kerusakan ?? 'Tidak ada deskripsi',
                    'tanggal_kembali' => $item->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                    'foto_before' => $item->foto_before,
                    'foto_after' => $item->foto_after,
                ];
            });

            return response()->json(['status' => 'sukses', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

public function riwayatMahasiswa()
{
    try {
        $userId = Auth::id();
        $riwayat = Peminjaman::with(['alat'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_alat' => $item->alat->nama_alat ?? 'Alat Terhapus',
                    'kode_alat' => $item->alat->kode ?? '-',
                    'status' => $item->status,
                    'tujuan' => $item->tujuan_penggunaan,
                    'kondisi_kembali' => $item->kondisi_kembali ?? '-',
                    'tanggal_pinjam' => $item->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                    'tanggal_kembali' => $item->tanggal_kembali 
                    ? \Carbon\Carbon::parse($item->tanggal_kembali)->timezone('Asia/Jakarta')->format('d M Y, H:i') 
                    : null,
                    'foto_before' => $item->foto_before ? asset('storage/' . $item->foto_before) : null,
                    'foto_after' => $item->foto_after ? asset('storage/' . $item->foto_after) : null,
                ];
            });

        return response()->json([
            'status' => 'sukses',
            'data' => $riwayat
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil data: ' . $e->getMessage()
        ], 500);
        }
    }
}