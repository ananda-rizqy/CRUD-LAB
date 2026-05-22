<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class RandomizerController extends Controller
{
    public function index()
    {
        $userAktif = Auth::user();
        $tokenKampus = $userAktif ? $userAktif->api_token_kampus : null;
        $baseUrl = 'https://presensi.polines.ac.id/api/telekomunikasi';

        if (!$tokenKampus) {
            return response()->json(['success' => false, 'message' => 'Token API Kampus tidak ditemukan di session Anda.'], 401);
        }

        $response = Http::timeout(15)
            ->withoutVerifying()
            ->withToken($tokenKampus)
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$baseUrl}/jadwal");

        if ($response->successful()) {
            return response()->json($response->json(), 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil jadwal langsung dari server pusat Polines.',
            'status_code' => $response->status()
        ], $response->status());
    }

    /**
     * MURNI 100% GET MAHASISWA DARI API KAMPUS POLINES
     */
    public function getMahasiswaKampus()
    {
        $userAktif = Auth::user();
        $tokenKampus = $userAktif ? $userAktif->api_token_kampus : null;
        $baseUrl = 'https://presensi.polines.ac.id/api/telekomunikasi';

        if (!$tokenKampus) {
            return response()->json(['success' => false, 'message' => 'Token API Kampus tidak ditemukan di session Anda.'], 401);
        }

        $response = Http::timeout(15)
            ->withoutVerifying()
            ->withToken($tokenKampus)
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$baseUrl}/mahasiswa");

        if ($response->successful()) {
            return response()->json($response->json(), 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data mahasiswa langsung dari server pusat Polines.',
            'status_code' => $response->status()
        ], $response->status());
    }
}