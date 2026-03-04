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
                'message' => 'Token Google tidak valid atau kedaluwarsa.'
            ], 401);
        }

        $email = strtolower($payload['email']);
        $fullName = $payload['name']; 
        $parts = explode('@', $email);
        $prefix = $parts[0];
        $domain = $parts[1];

        $nimOnly = preg_replace('/[^0-9]/', '', $prefix);

        $role = null;

        if ($domain === 'dosen.polines.ac.id') {
            $role = 'dosen';
        } elseif ($domain === 'gmail.com') {
            $role = 'staff';
        } 

        // Filter Mahasiswa (Contoh NIM Polines)
        elseif (str_starts_with($nimOnly, '431') || str_starts_with($nimOnly, '333')) {
            $role = 'mahasiswa';
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'    => $fullName, 
                'nim_nip' => $nimOnly,
                'role'    => $role,
                'password' => null, 
            ]
        );

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