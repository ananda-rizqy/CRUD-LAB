<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PenggunaanRuang;

class RiwayatruangController extends Controller
{

public function riwayatDosen()
{
    try {
        // Ambil semua data penggunaan ruang beserta data mahasiswanya
        $riwayat = \App\Models\PenggunaanRuang::with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_mahasiswa' => $item->user->name ?? 'N/A',
                    'nim_mahasiswa' => $item->user->nim_nip ?? '-',
                    'laboratorium' => $item->laboratorium,
                    'keperluan' => $item->keperluan,
                    'kondisi_masuk' => $item->kondisi_masuk,
                    'kondisi_keluar' => $item->kondisi_keluar ?? 'SEDANG DIGUNAKAN',
                    'jam_mulai' => $item->jam_mulai ? \Carbon\Carbon::parse($item->jam_mulai)->format('H:i') : '-',
                    'jam_selesai' => $item->jam_selesai ? \Carbon\Carbon::parse($item->jam_selesai)->format('H:i') : '-',
                    'waktu_masuk' => $item->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                    'waktu_keluar' => ($item->kondisi_keluar && $item->kondisi_keluar !== 'Belum Check-out') 
                        ? $item->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') 
                        : '-',
                    'foto_before' => $item->foto_before ? asset('storage/' . $item->foto_before) : null,
                    'foto_after' => $item->foto_after ? asset('storage/' . $item->foto_after) : null,
                ];
            });
 
        return response()->json([
            'status' => 'sukses',
            'data' => $riwayat
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
} 