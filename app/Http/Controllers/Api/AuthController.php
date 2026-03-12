<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Google\Client;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        try {
            $client = new Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
            $payload = $client->verifyIdToken($request->token);

            if (!$payload) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Token Google tidak valid.'
                ], 401);
            }

            $email = strtolower($payload['email']);
            $fullName = $payload['name']; 
            $parts = explode('@', $email);
            $prefix = $parts[0];
            $domain = $parts[1];
            $nimOnly = preg_replace('/[^0-9]/', '', $prefix);

            // 1. Cari user berdasarkan email
            $user = User::where('email', $email)->first();

            // 2. Logika Penentuan Role (Hanya jika user BARU)
            if (!$user) {
                $role = null;

                // Cek Dosen berdasarkan domain kampus
                if ($domain === 'dosen.polines.ac.id') {
                    $role = 'dosen';
                } 
                // Cek Staff (Misal menggunakan gmail umum sesuai kodinganmu)
                elseif ($domain === 'gmail.com') {
                    $role = 'staff';
                } 
                // Cek Mahasiswa berdasarkan prefix NIM
                elseif (str_starts_with($nimOnly, '431') || str_starts_with($nimOnly, '333')) {
                    $role = 'mahasiswa';
                }

                // Proteksi: Jika role tidak terdeteksi, tolak akses (agar tidak error di frontend)
                if (!$role) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email Anda tidak terdaftar dalam kategori Civitas Akademika.'
                    ], 403);
                }

                $user = User::create([
                    'email'    => $email,
                    'name'     => $fullName, 
                    'nim_nip'  => $nimOnly,
                    'role'     => $role,
                    'password' => null, 
                ]);
            } else {
                // Jika user SUDAH ADA, update nama/nim tapi JANGAN timpa role-nya
                $user->update([
                    'name'    => $fullName,
                    'nim_nip' => $nimOnly,
                ]);
            }

            // 3. Generate Token Baru
            $user->tokens()->delete(); 
            $token = $user->createToken('auth_token')->plainTextToken;

            // 4. Return Response Final
            return response()->json([
                'status'  => 'sukses',
                'message' => "Selamat Datang, " . $user->name,
                'user'    => [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'role'    => $user->role, // Penting untuk frontend
                    'nim_nip' => $user->nim_nip
                ],
                'token'   => $token
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }
}