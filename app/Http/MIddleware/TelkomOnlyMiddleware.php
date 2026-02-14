<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 

class TelkomOnlyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Gunakan Auth::user() alih-alih auth()->user()
        $user = Auth::user(); 

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $nim = $user->nim;

        // Filter NIM D3 Telkom (3.33) dan D4 Telkom (4.31)
        $isD3Telkom = preg_match('/^3\.33/', $nim);
        $isD4Telkom = preg_match('/^4\.31/', $nim);

        // Loloskan jika sesuai pola NIM atau jika role bukan mahasiswa (Dosen/Staff)
        if ($isD3Telkom || $isD4Telkom || $user->role !== 'mahasiswa') {
            return $next($request);
        }

        return response()->json([
            'message' => 'Akses ditolak. NIM ' . $nim . ' bukan dari prodi Telekomunikasi.'
        ], 403);
    }
}