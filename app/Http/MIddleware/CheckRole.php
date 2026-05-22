<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Menangani request masuk untuk memastikan hak akses role pengguna sah.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi autentikasi tidak valid atau telah berakhir.'
            ], 401);
        }

        $userRole = strtolower($user->role);

        if (in_array($userRole, array_map('strtolower', $roles))) {
            return $next($request);
        }

        // Jika tidak memiliki akses, lempar respon Forbidden 403
        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak! Akun Anda tidak memiliki otoritas untuk fitur ini.'
        ], 403);
    }
}