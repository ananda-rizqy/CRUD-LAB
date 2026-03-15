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
        $laboratorium = $request->query('lab'); 

        $query = Alat::query(); 

        if ($laboratorium) {
            $query->where('letak', 'like', "%{$laboratorium}%");
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_alat', 'like', "%{$search}%")
                  ->orWhere('kode_tag', 'like', "%{$search}%")
                  ->orWhere('letak', 'like', "%{$search}%");
            });
        }

        if ($role === 'staff' || ($role === 'mahasiswa' && !$laboratorium)) {
            $data = $query->latest()->get();
            return response()->json($data, 200);
        }

        $queryAgregasi = $query->select(
                DB::raw('MIN(id) as id'), 
                'nama_alat', 
                'letak',
                DB::raw('SUM(jumlah) as jumlah'),
                'kondisi',
                DB::raw('GROUP_CONCAT(CASE WHEN kode_tag NOT LIKE "%KONSUMSI%" THEN kode_tag END SEPARATOR ", ") as kode_tag')
            )
            ->where('kondisi', 'baik') 
            ->groupBy('nama_alat', 'letak', 'kondisi')
            ->get();
        
        return response()->json($queryAgregasi, 200);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_alat' => 'required|string|max:255',
                'letak'     => 'required|string|max:255',
                'kode_tag'  => 'nullable|string|unique:alats,kode_tag',
                'jumlah'    => 'required|integer|min:1',
                'kondisi'   => 'required|in:baik,rusak',
            ]);

            $kondisi = empty($validated['kode_tag']) ? 'baik' : $validated['kondisi'];
            
            $alat = Alat::create([
                'nama_alat' => strtoupper($validated['nama_alat']),
                'letak'     => $validated['letak'],
                'kode_tag'  => $validated['kode_tag'] ?? null, 
                'jumlah'    => $validated['jumlah'],
                'kondisi'   => $kondisi,
            ]);

            return response()->json(['message' => 'Data berhasil ditambahkan', 'data' => $alat], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal tambah data', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_alat' => 'required|string',
            'letak'     => 'required|string',
            'kode_tag'  => 'nullable|string|unique:alats,kode_tag,'.$id,
            'jumlah'    => 'required|integer|min:0',
            'kondisi'   => 'required|in:baik,rusak',
        ]);

        try {
            $alat = Alat::findOrFail($id);
            $kondisi = empty($validated['kode_tag']) ? 'baik' : $validated['kondisi'];

            $alat->update([
                'nama_alat' => strtoupper($validated['nama_alat']),
                'letak'     => $validated['letak'],
                'kode_tag'  => $validated['kode_tag'],
                'jumlah'    => $validated['jumlah'],
                'kondisi'   => $kondisi,
            ]);

            return response()->json(['message' => 'Data berhasil diperbarui', 'data' => $alat]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal update data', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $alat = Alat::findOrFail($id);
            if (method_exists($alat, 'peminjaman') && $alat->peminjaman()->exists()) {
                $alat->peminjaman()->delete();
            }
            $alat->delete();
            return response()->json(['message' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data', 'error' => $e->getMessage()], 500);
        }
    }
}