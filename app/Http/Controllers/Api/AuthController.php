<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle Login & Register otomatis via SSO Google.
     * Fungsi ini dipanggil setelah Frontend mendapatkan data dari Google.
     */
    public function ssoLogin(Request $request)
    {
        // Data yang dikirim dari Frontend setelah User klik "Login Google"
        $request->validate([
            'name'  => 'required|string',
            'email' => 'required|email',
        ]);

        $email = strtolower($request->email);
        $prefix = explode('@', $email)[0];
        $domain = explode('@', $email)[1];

        // Ekstrak angka NIM saja (misal: abu.12345678 -> 12345678)
        $nimOnly = preg_replace('/[^0-9]/', '', $prefix);

        // --- LOGIKA FILTER ROLE & DOMAIN ---
        $role = null;

        if ($domain === 'dosen.polines.ac.id') {
            $role = 'dosen';
        } elseif ($domain === 'staff.polines.ac.id') {
            $role = 'staff';
        } 
        // Filter Mahasiswa: Diawali 431/333 dan Total 8 Digit
        elseif (preg_match('/^(431|333)\d{5}$/', $nimOnly)) {
            $role = 'mahasiswa';
        }

        // Jika tidak lolos filter institusi
        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Hanya mahasiswa telekomunikasi.'
            ], 403);
        }

        // --- PROSES DATABASE (AUTOMATIC REGISTER/LOGIN) ---
        // Cari user berdasarkan email, jika tidak ada, buat baru
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Cek duplikasi NIM (jika mahasiswa ganti email tapi NIM sama)
            if ($role === 'mahasiswa' && User::where('nim_nip', $nimOnly)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'NIM ini sudah terdaftar dengan email lain.'
                ], 409);
            }

            // Pendaftaran otomatis (tanpa password karena full SSO)
            $user = User::create([
                'name'    => $request->name,
                'email'   => $email,
                'nim_nip' => $nimOnly,
                'role'    => $role,
                'password' => null, // Dikosongkan karena login via Google
            ]);
        }

        // --- GENERATE TOKEN (LOGIN) ---
        // Hapus token lama agar hanya bisa login di satu perangkat (opsional)
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