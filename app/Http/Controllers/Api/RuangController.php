<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PenggunaanRuang;
use Illuminate\Support\Facades\Storage;

class RuangController extends Controller
{
    public function laporMasuk(Request $request)
    {
        $request->validate([
            'laboratorium'  => 'required|string',
            'kondisi_masuk' => 'required|string',
            'keperluan'     => 'required|string',
            'jam_mulai'     => 'required|date',
            'jam_selesai'   => 'required|date|after:jam_mulai',
            ], [
            'jam_selesai.after' => 'Jam selesai harus lebih lama dari jam mulai pemakaian.',
            'foto_before'   => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

        $path = $request->file('foto_before')->store('ruang/before', 'public');
        $penggunaan = PenggunaanRuang::create([
            'user_id'       => $request->user()->id,
            'laboratorium'  => $request->laboratorium,
            'kondisi_masuk' => $request->kondisi_masuk,
            'keperluan'     => $request->keperluan,
            'jam_mulai'     => $request->jam_mulai,
            'jam_selesai'   => $request->jam_selesai,
            'foto_before'   => $path,
        ]);

        return response()->json([
            'message' => 'Laporan masuk berhasil disimpan!',
            'data'    => $penggunaan
        ], 201);
    }

    public function laporKeluar(Request $request, $id)
    {
        $request->validate([
            'kondisi_keluar' => 'required|string',
            'foto_after'     => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $penggunaan = PenggunaanRuang::findOrFail($id);

        $path = $request->file('foto_after')->store('ruang/after', 'public');

        $penggunaan->update([
            'kondisi_keluar' => $request->kondisi_keluar,
            'foto_after'     => $path,
            'waktu_keluar'   => now(), // Mengisi timestamp waktu keluar saat ini
        ]);

        return response()->json([
            'message' => 'Laporan keluar berhasil disimpan, terima kasih!',
            'data'    => $penggunaan
        ]);
    }
public function riwayatRuang()
{
    try {
        $userId = \Illuminate\Support\Facades\Auth::id();

        $riwayat = PenggunaanRuang::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'laboratorium' => $item->laboratorium,
                    'keperluan' => $item->keperluan,
                    'kondisi_masuk' => $item->kondisi_masuk,
                    'kondisi_keluar' => $item->kondisi_keluar ?? 'Belum Check-out',
                    'jam_mulai' => $item->jam_mulai ? \Carbon\Carbon::parse($item->jam_mulai)->format('d M, H:i') : '-',
                    'jam_selesai' => $item->jam_selesai ? \Carbon\Carbon::parse($item->jam_selesai)->format('d M, H:i') : '-',
                    'waktu_masuk' => $item->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                    'waktu_keluar' => $item->updated_at && $item->kondisi_keluar 
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
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil riwayat ruang: ' . $e->getMessage()
        ], 500);
    }
}
public function riwayatStaff()
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