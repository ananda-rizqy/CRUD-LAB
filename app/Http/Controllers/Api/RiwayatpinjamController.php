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
 
class RiwayatpinjamController extends Controller
{

public function riwayatDosen()
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
}