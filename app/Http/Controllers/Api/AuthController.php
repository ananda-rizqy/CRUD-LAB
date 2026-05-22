<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginAndSyncSSO(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($request->email === 'dummy.tendik' && $request->password === 'tendik123') {
            $user = User::updateOrCreate(
                ['nim_nip' => '197505032007011001'],
                [
                    'name' => 'ABDUL SUNARNO (DUMMY LOCAL)',
                    'email' => 'abdul.sunarno@polines.ac.id',
                    'role' => 'tendik',
                    'jurusan' => 'Teknik Elektro',
                    'gelar_belakang' => 'S.Kom',
                    'password' => bcrypt(Str::random(16)),
                ]
            );
            
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Login Dummy Sukses (Simulation Mode)',
                'token' => $user->createToken('labToken')->plainTextToken,
                'user' => $user
            ]);
        }

        try {
            $baseUrl = 'https://presensi.polines.ac.id/api/telekomunikasi';

            // 1. Jalankan Login Utama ke Kampus
            $loginResponse = Http::post("{$baseUrl}/login", [
                'email' => trim($request->email),
                'password' => $request->password,
            ]);

            if (!$loginResponse->successful() || !$loginResponse->json()['success']) {
                return response()->json(['success' => false, 'message' => 'Kredensial SIMADU Salah.'], 401);
            }

            $campusData = $loginResponse->json()['data'];
            $tokenKampus = $campusData['token'];
            $userKampus = $campusData['user'];
            $emailPolines = $userKampus['email_polines'];
            $identityNumber = $userKampus['email']; 

            $profileData = [
                'name' => explode('.', $emailPolines)[0], 
                'role' => 'mahasiswa',
                'kelas' => null, 'prodi' => null, 'jenjang' => null, 'jurusan' => 'Teknik Elektro',
                'gelar_depan' => null, 'gelar_belakang' => null
            ];

            if (Str::contains($emailPolines, 'mhs.polines.ac.id')) {
                $mhsRes = Http::withToken($tokenKampus)->get("{$baseUrl}/mahasiswa");
                
                if ($mhsRes->successful()) {
                    $cleanIdentity = str_replace('.', '', $identityNumber);
                    
                    $detail = collect($mhsRes->json()['data'])->first(function ($value) use ($identityNumber, $cleanIdentity) {
                        $cleanNimKampus = str_replace('.', '', $value['nim'] ?? '');
                        return $value['nim'] === $identityNumber || $cleanNimKampus === $cleanIdentity;
                    });

                    if ($detail) {
                        $profileData['name'] = $detail['nama'];
                        $profileData['role'] = 'mahasiswa'; 
                        $profileData['kelas'] = $detail['kelas'];
                        $profileData['prodi'] = $detail['prodi'];
                        $profileData['jenjang'] = $detail['jenjang'];
                        $profileData['jurusan'] = $detail['jurusan'];
                    }
                }
            } else {
                $dosenRes = Http::withToken($tokenKampus)->get("{$baseUrl}/dosen");
                $detailDosen = collect($dosenRes->json()['data'] ?? [])->firstWhere('nip', $identityNumber);

                if ($detailDosen) {
                    $profileData['name'] = $detailDosen['nama'];
                    $profileData['role'] = 'dosen';
                    $profileData['prodi'] = $detailDosen['prodi'];
                    $profileData['jenjang'] = $detailDosen['jenjang'];
                    $profileData['jurusan'] = $detailDosen['jurusan'];
                    $profileData['gelar_depan'] = $detailDosen['gelar_depan'];
                    $profileData['gelar_belakang'] = $detailDosen['gelar_belakang'];
                } else {
                    // Jika tidak ada di dosen, maka dipastikan dia adalah Tendik
                    $tendikRes = Http::withToken($tokenKampus)->get("{$baseUrl}/tendik");
                    $detailTendik = collect($tendikRes->json()['data'] ?? [])->firstWhere('nip', $identityNumber);
                    
                    if ($detailTendik) {
                        $profileData['name'] = $detailTendik['nama'];
                        $profileData['role'] = 'tendik';
                        $profileData['gelar_depan'] = $detailTendik['gelar_depan'];
                        $profileData['gelar_belakang'] = $detailTendik['gelar_belakang'];
                    }
                }
            }

            $localUser = User::updateOrCreate(
                ['nim_nip' => $identityNumber],
                [
                    'name' => $profileData['name'],
                    'email' => $emailPolines,
                    'role' => $profileData['role'],
                    'kelas' => $profileData['kelas'],
                    'prodi' => $profileData['prodi'],
                    'jenjang' => $profileData['jenjang'],
                    'jurusan' => $profileData['jurusan'],
                    'gelar_depan' => $profileData['gelar_depan'],
                    'gelar_belakang' => $profileData['gelar_belakang'],
                    'api_token_kampus' => $tokenKampus, 
                    'password' => bcrypt(Str::random(16)), 
                ]
            );

            $localUser->refresh(); 

            $localToken = $localUser->createToken('polinesLabToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Autentikasi terpusat dan sinkronisasi profil berhasil.',
                'token' => $localToken,
                'user' => $localUser
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung dengan server pusat SSO SIMADU.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}