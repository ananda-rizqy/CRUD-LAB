<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        $email = $request->email;
        $username = explode('@', strtolower($email))[0];

        // 1. Cek apakah user sudah ada di database
        $user = User::where('email', $email)->first();

        if ($user) {
            // HAPUS TOKEN LAMA: Agar setiap login hanya ada 1 token aktif
            $user->tokens()->delete(); 

            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => 'sukses', 
                'user' => $user, 
                'token' => $token
            ]);
        }

        // 2. Logika Auto-Register Mahasiswa (Regex)
        if (preg_match('/(431|333)(\d{2})(\d{1})(\d{2})/', $username, $matches)) {
            $prodiCode = $matches[1];
            $thnMasuk  = (int) $matches[2];
            $kodeKelas = (int) $matches[3];

            // Konfigurasi Jenjang
            if ($prodiCode == '431') {
                $label = "TE";
                $namaProdi = "D4 Telekomunikasi";
                $maxTingkat = 4;
            } else {
                $label = "TK";
                $namaProdi = "D3 Telekomunikasi";
                $maxTingkat = 3;
            }

            // Hitung Tingkat (Asumsi Tahun Ajaran 2025/2026)
            $tahunReferensi = 25; 
            $tingkat = ($tahunReferensi - $thnMasuk) + 1;

            // Batasi tingkat agar tidak overload (D4 maks 4, D3 maks 3)
            if ($tingkat > $maxTingkat) $tingkat = $maxTingkat;
            if ($tingkat < 1) $tingkat = 1;

            $abjad = chr(65 + $kodeKelas); 
            $kelasFinal = $label . "-" . $tingkat . $abjad;

            $newUser = User::create([
                'name' => $request->name,
                'email' => $email,
                'nim' => $username,
                'prodi' => $namaProdi,
                'role' => 'mahasiswa',
                'kelas' => $kelasFinal,
                'password' => Hash::make('polines123'),
            ]);

            $token = $newUser->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => 'sukses',
                'message' => 'Login Berhasil. Kelas otomatis: ' . $kelasFinal,
                'user' => $newUser,
                'token' => $token
            ]);
        }

        return response()->json([
            'status' => 'error', 
            'message' => 'Hanya untuk Mahasiswa Telkom.'
        ], 403);
    }
}