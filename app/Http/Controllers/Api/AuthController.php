<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Handle Login Simulasi via Email (Tanpa Password/Nama).
     */
    public function ssoLogin(Request $request)
    {
        // 1. Validasi HANYA email (Hapus 'name' => 'required')
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->email);
        
        // Cek apakah email mengandung karakter '@'
        if (!str_contains($email, '@')) {
            return response()->json(['status' => 'error', 'message' => 'Format email salah.'], 400);
        }

        $parts = explode('@', $email);
        $prefix = $parts[0];
        $domain = $parts[1];

        // Ekstrak angka saja (NIM/NIP)
        $nimOnly = preg_replace('/[^0-9]/', '', $prefix);

        // --- LOGIKA FILTER ROLE & DOMAIN ---
        $role = null;

        if ($domain === 'dosen.polines.ac.id') {
            $role = 'dosen';
        } elseif ($domain === 'staff.polines.ac.id') {
            $role = 'staff';
        } 
        // Filter Mahasiswa: Misal NIM diawali 431/333
        elseif (str_starts_with($nimOnly, '431') || str_starts_with($nimOnly, '333')) {
            $role = 'mahasiswa';
        }

        // Jika tidak lolos filter institusi
        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Gunakan email institusi Polines yang valid.'
            ], 403);
        }

        // --- PROSES DATABASE ---
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Jika user baru, buat data otomatis
            $user = User::create([
                // Karena simulasi tanpa input nama, kita ambil nama dari prefix email
                'name'    => ucwords(str_replace('.', ' ', $prefix)), 
                'email'   => $email,
                'nim_nip' => $nimOnly,
                'role'    => $role,
                'password' => null, 
            ]);
        }

        // --- GENERATE TOKEN ---
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'sukses',
            'message' => "Berhasil masuk sebagai " . ucfirst($role),
            'user'    => $user,
            'token'   => $token
        ]);
    }
}