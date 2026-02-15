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
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower($request->email);
        $prefix = explode('@', $email)[0]; // Bagian depan email
        $domain = explode('@', $email)[1]; // Bagian domain email

        // --- PERBAIKAN: Definisi $nimOnly ---
        // Menghapus semua karakter kecuali angka (abu.1234567 -> 1234567)
        $nimOnly = preg_replace('/[^0-9]/', '', $prefix);

        // --- LOGIKA FILTER ROLE & DOMAIN ---
        $role = null;

        if ($domain === 'dosen.polines.ac.id') {
            $role = 'dosen';
        } elseif ($domain === 'staff.polines.ac.id') {
            $role = 'staff';
        } elseif (preg_match('/(431|333|433)/', $nimOnly)) {
            // Ditambahkan 433 jika prodi Anda termasuk dalam filter
            $role = 'mahasiswa';
        }

        // Jika tidak memenuhi kriteria di atas, tolak akses
        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Hanya mahasiswa telekomunikasi.'
            ], 403);
        }

        // --- PROSES AUTHENTIKASI ---
        $user = User::where('email', $email)->first();

        // Auto-Register jika user belum ada
        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'nim_nip' => $nimOnly, // Sekarang variabel ini sudah didefinisikan di atas
                'role' => $role,
                'password' => Hash::make($request->password),
            ]);
        }

        // Verifikasi Password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah.'
            ], 401);
        }

        // Generate Token (Single Session)
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'sukses',
            'message' => "Login Berhasil sebagai " . ucfirst($role),
            'user' => $user,
            'token' => $token
        ]);
    }
}