<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PenggunaanRuang;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon; // Penting untuk urusan waktu

class RuangController extends Controller
{
    public function laporMasuk(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'laboratorium'  => 'required|string',
            'kondisi_masuk' => 'required|string',
            'keperluan'     => 'required|string',
            'jam_mulai'     => 'required',
            'jam_selesai'   => 'required',
            'foto_before'   => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih lama dari jam mulai pemakaian.',
        ]);

        try {
            // 2. Konversi format Datetime agar MySQL tidak error
            // Ini akan merubah '2026-03-12T13:51' menjadi '2026-03-12 13:51:00'
            $jamMulai = Carbon::parse($request->jam_mulai)->format('Y-m-d H:i:s');
            $jamSelesai = Carbon::parse($request->jam_selesai)->format('Y-m-d H:i:s');

            // 3. Simpan Foto
            $path = $request->file('foto_before')->store('ruang/before', 'public');

            // 4. Create Data
            $penggunaan = PenggunaanRuang::create([
                'user_id'       => $request->user()->id, // Lebih simpel pakai auth()->id()
                'laboratorium'  => $request->laboratorium,
                'kondisi_masuk' => $request->kondisi_masuk,
                'keperluan'     => $request->keperluan,
                'jam_mulai'     => $jamMulai,
                'jam_selesai'   => $jamSelesai,
                'foto_before'   => $path,
            ]);

            return response()->json([
                'status'  => 'sukses',
                'message' => 'Laporan masuk berhasil disimpan!',
                'data'    => $penggunaan
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal simpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function laporKeluar(Request $request, $id)
    {
        $request->validate([
            'kondisi_keluar' => 'required|string',
            'foto_after'     => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $penggunaan = PenggunaanRuang::findOrFail($id);

        try {
            $path = $request->file('foto_after')->store('ruang/after', 'public');

            $penggunaan->update([
                'kondisi_keluar' => $request->kondisi_keluar,
                'foto_after'     => $path,
                'waktu_keluar'   => now(), // Mengisi timestamp waktu keluar saat ini
            ]);

            return response()->json([
                'status'  => 'sukses',
                'message' => 'Laporan keluar berhasil disimpan, terima kasih!',
                'data'    => $penggunaan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal simpan laporan keluar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function riwayatRuang()
    {
        try {
            $riwayat = PenggunaanRuang::where('user_id', request()->user()->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'laboratorium' => $item->laboratorium,
                        'keperluan' => $item->keperluan,
                        'kondisi_masuk' => $item->kondisi_masuk,
                        'kondisi_keluar' => $item->kondisi_keluar ?? 'Belum Check-out',
                        'jam_mulai' => $item->jam_mulai ? Carbon::parse($item->jam_mulai)->format('d M, H:i') : '-',
                        'jam_selesai' => $item->jam_selesai ? Carbon::parse($item->jam_selesai)->format('d M, H:i') : '-',
                        'waktu_masuk' => $item->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                        'waktu_keluar' => ($item->waktu_keluar && $item->kondisi_keluar) 
                            ? Carbon::parse($item->waktu_keluar)->timezone('Asia/Jakarta')->format('d M Y, H:i') 
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
            $riwayat = PenggunaanRuang::with('user')
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
                        'jam_mulai' => $item->jam_mulai ? Carbon::parse($item->jam_mulai)->format('H:i') : '-',
                        'jam_selesai' => $item->jam_selesai ? Carbon::parse($item->jam_selesai)->format('H:i') : '-',
                        'waktu_masuk' => $item->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                        'waktu_keluar' => ($item->waktu_keluar) 
                            ? Carbon::parse($item->waktu_keluar)->timezone('Asia/Jakarta')->format('d M Y, H:i') 
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