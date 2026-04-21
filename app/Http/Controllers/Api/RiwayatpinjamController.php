<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
 
class RiwayatpinjamController extends Controller
{

//khusus riwayat peminjaman dosen
public function riwayatDosen()
{
    try {
        // Ambil relasi details.alat karena alat ada di dalam detail peminjaman
        $riwayat = Peminjaman::with(['user', 'details.alat', 'penerima'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_mahasiswa' => $item->user->name ?? 'User Tidak Ada',
                    'nim' => $item->user->nim_nip ?? '-',
                    
                    'details' => $item->details->map(function ($detail) {
                        return [
                            'alat' => [
                                'nama_alat' => $detail->alat->nama_alat ?? 'Alat Terhapus',
                                'kode_tag' => $detail->alat->kode_tag ?? '-',
                            ]
                        ];
                    }),

                    'tujuan_penggunaan' => $item->tujuan_penggunaan,
                    'status' => $item->status,    
                    'kondisi_kembali' => $item->kondisi_kembali,
                    'created_at' => $item->created_at, 
                    'waktu_pinjam' => $item->waktu_pinjam, 
                    'waktu_kembali' => $item->waktu_kembali, 
                    'foto_before' => $item->foto_before, 
                    'foto_after' => $item->foto_after,
                    'penerima' => $item->penerima,
                    'alasan_penolakan' => $item->alasan_penolakan,
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
}