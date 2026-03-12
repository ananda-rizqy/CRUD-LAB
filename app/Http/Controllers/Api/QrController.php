<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QrController extends Controller
{
    public function generatePintuMasuk(): JsonResponse
    {
        // Arahkan ke Login Page
        $targetUrl = "http://localhost:5173/login?from=pintu_lab";
        
        $quickChartUrl = "https://quickchart.io/qr?text=" . urlencode($targetUrl) . "&format=svg&size=300";

        return response()->json([
            'status' => 'success',
            'qr_code_url' => $quickChartUrl
        ]);
    }
}