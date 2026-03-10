<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlatController extends Controller
{
    private function generateQrUrl($kode)
    {
        if (!$kode) return null;
        $params = [
            'text'    => $kode,
            'margin'  => 4,      
            'size'    => 300,    
            'ecLevel' => 'M',    
            'format'  => 'png'
        ];
        return "https://quickchart.io/qr?" . http_build_query($params);
    }

    public function index(Request $request) 
    {
        $role = $request->query('role');
        $search = $request->query('search');

        if ($role === 'staff') {
            $data = Alat::when($search, function($query) use ($search) {
                $query->where('nama_alat', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
            })->get()->map(function($item) {
                $item->qr_url = $this->generateQrUrl($item->kode);
                return $item;
            });
            return response()->json($data, 200);
        }

        $queryAgregasi = Alat::select(
                'nama_alat', 
                'letak',
                DB::raw('SUM(total) as total'), 
                DB::raw("SUM(tersedia) as tersedia")
            )
            ->groupBy('nama_alat', 'letak')
            ->when($search, function($query) use ($search) {
                $query->where('nama_alat', 'like', "%{$search}%");
            })
            ->get();
        
        return response()->json($queryAgregasi, 200);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_alat'    => 'required|string|max:255',
                'letak'        => 'required|string|max:255',
                'kode'         => 'nullable|string|unique:alats,kode',
                'kondisi'      => 'required|in:baik,rusak,Baik,Rusak',
            ]);

            $kondisi = ucfirst(strtolower($validated['kondisi'])); 
            
            $alat = Alat::create([
                'nama_alat' => $validated['nama_alat'],
                'letak'     => $validated['letak'],
                'kode'      => $validated['kode'] ?? null, 
                'kondisi'   => $kondisi,
                'total'     => 1,      
                'tersedia'  => ($kondisi === 'Baik') ? 1 : 0,       
                'qrcode_token' => Str::random(32),
            ]);

            return response()->json(['message' => 'Alat berhasil ditambahkan', 'data' => $alat], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal tambah data', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kondisi' => 'required|in:Baik,Rusak,baik,rusak',
            'nama_alat' => 'nullable|string',
            'total' => 'nullable|integer|min:0',
        ]);

        try {
            return DB::transaction(function () use ($request, $id) {
                $alat = Alat::lockForUpdate()->findOrFail($id);

                $kondisiBaru = ucfirst(strtolower($request->kondisi));
                $totalBaru = $request->has('total') ? (int)$request->total : (int)$alat->total;

                $alat->nama_alat = $request->nama_alat ?? $alat->nama_alat;
                $alat->total = $totalBaru;
                $alat->kondisi = $kondisiBaru;

                if ($kondisiBaru === 'Baik') {
                    $alat->tersedia = $totalBaru;
                } else {
                    $alat->tersedia = 0;
                }

                $alat->save();
                $alat->refresh(); 

                return response()->json([
                    'message' => 'Alat berhasil diperbarui', 
                    'data' => $alat
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal update data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $alat = Alat::findOrFail($id);
            if ($alat->peminjaman()->exists()) {
                $alat->peminjaman()->delete();
            }
            $alat->delete();
            return response()->json(['message' => 'Alat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }

    public function scanAlat($kode)
    {
        $alat = Alat::where('kode', trim($kode))->first();
        if (!$alat) {
            return response()->json(['sukses' => false, 'pesan' => "Unit tidak ditemukan."], 404);
        }
        return response()->json(['sukses' => true, 'data' => $alat], 200);
    }
}