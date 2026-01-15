<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PenggunaanRuang;

class RuangController extends Controller
{

public function index() {
    // Mengambil semua data penggunaan ruang, diurutkan dari yang terbaru
    $riwayat = PenggunaanRuang::orderBy('created_at', 'desc')->get();
    
    return response()->json([
        'message' => 'Berhasil mengambil riwayat penggunaan ruang',
        'data' => $riwayat
    ]);
}
    // Tahap 1: Check-in (Masuk)
public function masuk(Request $request) {
    $request->validate([
        'nama_mahasiswa' => 'required',
        'nim'            => 'required',
        'laboratorium'   => 'required',
        'foto_before'    => 'required|image|mimes:jpeg,png,jpg|max:2048'
    ], [
        'foto_before.required' => 'Wajib mengunggah foto kondisi awal laboratorium!'
    ]);

    $data = $request->all();

    if ($request->hasFile('foto_before')) {
        $file = $request->file('foto_before');
        $nama_file = time() . '_ruang_before_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/ruangan'), $nama_file);
        $data['foto_before'] = $nama_file;
    }

    $log = PenggunaanRuang::create($data);
    return response()->json(['message' => 'Berhasil Check-in', 'data' => $log]);
}

// Tahap 2: Check-out (Keluar)
public function keluar(Request $request, $id) {
    // 1. Cari data berdasarkan ID
    $log = PenggunaanRuang::find($id);

    // 2. CEK: Jika data TIDAK ditemukan (Null)
    // Ini akan menangkap pengujian negatif Anda (ID yang belum check-in)
    if (!$log) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak ditemukan! Anda harus Check-in terlebih dahulu sebelum bisa melakukan Check-out.'
        ], 404);
    }

    // 3. Validasi input (Hanya dijalankan jika data $log ditemukan)
    $request->validate([
        'foto_after' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'kondisi_lab' => 'required|string'
    ], [
        'foto_after.required' => 'Wajib mengunggah foto kondisi akhir laboratorium sebelum check-out!',
        'kondisi_lab.required' => 'Mohon isi keterangan kondisi lab (contoh: Bersih)!'
    ]);

    // 4. Proses upload file
    if ($request->hasFile('foto_after')) {
        $file = $request->file('foto_after');
        $nama_file = time() . '_ruang_after_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/ruangan'), $nama_file);
        $log->foto_after = $nama_file;
    }

    // 5. Update sisa data dan simpan
    $log->kondisi_lab = $request->kondisi_lab;
    $log->waktu_keluar = now();
    $log->status = 'selesai';
    $log->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Berhasil Check-out', 
        'data' => $log
    ]);
}
}