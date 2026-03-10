<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Google\Client;

class AuthController extends Controller
{
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

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

        //Cari user berdasarkan email dulu
        $user = User::where('email', $email)->first();

        //Jika user BELUM ADA, tentukan role awal berdasarkan domain
        if (!$user) {
            $role = null;
            if ($domain === 'dosen.polines.ac.id') {
                $role = 'dosen';
            } elseif ($domain === 'gmail.com') {
                $role = 'staff';
            } elseif (str_starts_with($nimOnly, '431') || str_starts_with($nimOnly, '333')) {
                $role = 'mahasiswa';
            }

            $user = User::create([
                'email'    => $email,
                'name'     => $fullName, 
                'nim_nip'  => $nimOnly,
                'role'     => $role,
                'password' => null, 
            ]);
        } else {
            // Jika user SUDAH ADA, update data lain TAPI jangan timpa role-nya
            $user->update([
                'name'    => $fullName,
                'nim_nip' => $nimOnly,
                // 'role' sengaja tidak dimasukkan di sini agar tidak tertimpa
            ]);
        }

        $user->tokens()->delete(); 
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'sukses',
            'message' => "Berhasil masuk sebagai " . ucfirst($user->role), 
            'user'    => $user,
            'token'   => $token
        ]);
    }
}