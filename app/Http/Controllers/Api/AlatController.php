<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlatController extends Controller
{
    public function index(Request $request) 
    {
        $role = $request->query('role');
        $search = $request->query('search');

        if ($role === 'staff') {
            $query = Alat::query();

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_alat', 'like', "%{$search}%")
                      ->orWhere('letak', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
                });
            }

            return response()->json($query->get(), 200);
        } 

        // Menghitung total dan tersedia (kondisi Baik) secara Real-time
        $queryAgregasi = Alat::select(
                'nama_alat', 
                'letak',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN kondisi = 'Baik' THEN 1 ELSE 0 END) as tersedia")
            )
            ->groupBy('nama_alat', 'letak');

        if ($search) {
            $queryAgregasi->where(function($q) use ($search) { 
                $q->where('nama_alat', 'like', "%{$search}%")
                  ->orWhere('letak', 'like', "%{$search}%");
            });
        }
        
        return response()->json($queryAgregasi->get(), 200);
    }

    public function store(Request $request) 
    {
        $validated = $request->validate([
            'nama_alat' => 'required',
            'letak'     => 'required',
            'kode'      => 'nullable|string|unique:alats,kode',
            'kondisi'   => 'required|in:Baik,Rusak',
        ], [
            'kode.unique' => 'Kode alat ini sudah digunakan oleh unit lain!'
        ]);

        $alat = Alat::create($validated);
        
        return response()->json([
            'sukses' => true,
            'pesan'  => 'Unit alat berhasil didaftarkan ke sistem',
            'data'   => $alat
        ], 201);
    }

    public function show($id) 
    {
        $alat = Alat::findOrFail($id);
        return response()->json($alat, 200);
    }

    public function update(Request $request, $id) 
    {
        $alat = Alat::findOrFail($id);

        $validated = $request->validate([
            'nama_alat' => 'sometimes|required',
            'letak'     => 'sometimes|required',
            'kode'      => 'sometimes|required|string|unique:alats,kode,' . $id,
            'kondisi'   => 'sometimes|required|in:Baik,Rusak',
        ]);

        $alat->update($validated);

        return response()->json([
            'sukses' => true,
            'pesan'  => 'Status unit alat berhasil diperbarui',
            'data'   => $alat
        ], 200);
    }

    public function destroy($id) 
    {
        $alat = Alat::findOrFail($id);
        $alat->delete();

        return response()->json([
            'sukses' => true,
            'pesan' => 'Unit alat berhasil dihapus dari sistem'
        ], 200);
    }
}