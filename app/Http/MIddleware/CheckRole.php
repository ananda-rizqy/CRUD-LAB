<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Menggunakan Auth:: langsung lebih aman dan jelas bagi Intelephense
        if (!Auth::check() || !in_array(Auth::user()->role, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Role Anda tidak diizinkan.'
            ], 403);
        }

        return $next($request);
    }
}