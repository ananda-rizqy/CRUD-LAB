<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PeminjamanController extends Controller
{
    // MAHASISWA: Mengajukan Peminjaman (Proses Keranjang)
    public function store(Request $request)
    {
        if ($request->has('items') && is_string($request->items)) {
            $request->merge(['items' => json_decode($request->items, true)]);
        }

        $request->validate([
            'ruangan_lab' => 'required|string',
            'tujuan'      => 'required|string',
            'items'       => 'required|array|min:1',
            'items.*.id'  => 'required|exists:alats,id',
            'foto_before' => 'required|image|max:5120',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                foreach ($request->items as $item) {

                    $alat = \App\Models\Alat::lockForUpdate()->find($item['id']);
                    if (!$alat) {
                        throw new \Exception("Alat tidak ditemukan.");
                    }

                    // ASET
                    if ($alat->is_aset) {

                        $tagsRequested = $item['kode_tag_list'] ?? [];

                        if (empty($tagsRequested)) {
                            throw new \Exception("Harap pilih unit untuk {$alat->nama_alat}");
                        }

                        if (count($tagsRequested) !== count(array_unique($tagsRequested))) {
                            throw new \Exception("Tidak boleh pilih unit yang sama!");
                        }

                        foreach ($tagsRequested as $tag) {

                            $unit = \App\Models\Alat::where('kode_tag', $tag)
                                ->where('kondisi', 'Baik') 
                                ->lockForUpdate()
                                ->first();

                            if (!$unit) {
                                throw new \Exception("Unit {$tag} tidak tersedia.");
                            }

                            $isBusy = \App\Models\PeminjamanDetails::where('alat_id', $unit->id)
                                ->whereHas('peminjaman', function ($q) {
                                    $q->whereIn('status', ['pending', 'approved', 'ongoing']);
                                })
                                ->exists();

                            if ($isBusy) {
                                throw new \Exception("Unit {$alat->nama_alat} ({$tag}) sedang dipinjam.");
                            }
                        }

                    } 
                    // NON ASET
                    else {

                        $qty = $item['qty'] ?? 1;

                        if ($alat->jumlah < $qty) {
                            throw new \Exception("Stok {$alat->nama_alat} tidak mencukupi!");
                        }
                    }
                }

                $pathFoto = $request->file('foto_before')->store('peminjaman/before', 'public');

                $peminjaman = \App\Models\Peminjaman::create([
                    'user_id'           => Auth::id(),
                    'ruangan_lab'       => $request->ruangan_lab,
                    'tujuan_penggunaan' => $request->tujuan,
                    'foto_before'       => $pathFoto,
                    'waktu_pinjam'      => now(),
                    'status'            => 'pending',
                ]);

                if (!$peminjaman || !$peminjaman->id) {
                    throw new \Exception("Gagal membuat peminjaman");
                }

                foreach ($request->items as $item) {

                    $alat = \App\Models\Alat::lockForUpdate()->find($item['id']);

                    if ($alat->is_aset) {

                        foreach ($item['kode_tag_list'] as $tag) {

                            $unit = \App\Models\Alat::where('kode_tag', $tag)->first();

                            \App\Models\PeminjamanDetails::create([
                                'peminjaman_id' => $peminjaman->id,
                                'alat_id'       => $unit->id,
                                'jumlah_pinjam' => 1,
                            ]);
                        }

                    } else {

                        $qty = $item['qty'];

                        \App\Models\PeminjamanDetails::create([
                            'peminjaman_id' => $peminjaman->id,
                            'alat_id'       => $alat->id,
                            'jumlah_pinjam' => $qty,
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'sukses',
                    'message' => 'Peminjaman berhasil diajukan'
                ], 201);

            });

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // STAFF: Melihat semua daftar pinjaman
    public function index()
    {
        // Mengambil data peminjaman beserta detail alat di dalamnya
        $peminjaman = Peminjaman::with(['user', 'details.alat'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'ongoing', 'returned', 'rejected')")
            ->latest()
            ->get();

        return response()->json([
            'status' => 'sukses',
            'data'   => $peminjaman
        ]);
    }

    // STAFF: Menyetujui Pengajuan (Sekaligus Potong Stok Semua Alat)
    public function setujui($id)
    {
        return DB::transaction(function () use ($id) {
            $pinjam = \App\Models\Peminjaman::with('details.alat')->findOrFail($id);

            if ($pinjam->status !== 'pending') {
                return response()->json(['message' => 'Status bukan pending.'], 400);
            }

            foreach ($pinjam->details as $detail) {
                $alat = $detail->alat;

                if (!$alat->is_aset) {
                    if ($alat->jumlah < $detail->jumlah_pinjam) {
                        throw new \Exception("Gagal! Stok fisik {$alat->nama_alat} di gudang tidak mencukupi.");
                    }
                }
            }

            // Update status menjadi 'ongoing'
            $pinjam->update([
                'status' => 'ongoing',
                'penerima_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'sukses',
                'message' => 'Peminjaman disetujui. Stok alat konsumsi telah dikurangi (jika ada).'
            ]);
        });
    }

    // MAHASISWA: Upload Foto Sebelum (Ubah status ke Ongoing)
    public function uploadBefore(Request $request, $id)
    {
        $request->validate([
            'foto_before' => 'required|image|mimes:jpeg,png,jpg',
        ]);

        $pinjam = Peminjaman::findOrFail($id);

        // Keamanan: Pastikan hanya peminjaman yang sudah disetujui yang bisa upload foto
        if ($pinjam->status !== 'approved') {
            return response()->json(['message' => 'Peminjaman belum disetujui staff atau sudah berjalan.'], 400);
        }

        if ($request->hasFile('foto_before')) {
            $file = $request->file('foto_before');
            $namaFile = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('peminjaman/before', $namaFile, 'public');

            $pinjam->update([
                'foto_before' => $path,
                'waktu_kembali' => now(),
                'status' => 'ongoing' 
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto berhasil diunggah. Selamat praktikum!',
                'data' => $pinjam
            ]);
    

        return response()->json(['message' => 'File tidak ditemukan.'], 400);
        }
    }

    // MAHASISWA: Pengembalian (Status Kembali ke Tersedia)
    public function kembalikan(Request $request, $id)
    {
            $request->validate([
                'foto_after' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'kondisi_kembali' => 'required|string',
                'deskripsi_kerusakan' => 'nullable|string',
            ]);

            return DB::transaction(function () use ($request, $id) {
                $pinjam = Peminjaman::with('details.alat')->findOrFail($id);

                if ($pinjam->status !== 'ongoing') {
                    return response()->json(['message' => 'Alat belum diambil atau sudah dikembalikan.'], 400);
                }

                $file = $request->file('foto_after');
                $path = $file->store('peminjaman/after', 'public');
                
                $pinjam->update([
                    'status' => 'returned',
                    'foto_after' => $path,
                    'kondisi_kembali' => $request->kondisi_kembali,
                    'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
                    'waktu_kembali' => now(),
                    'tanggal_kembali' => now(),
                ]);

                // Kembalikan stok alat
                foreach ($pinjam->details as $detail) {
                   $alat = $detail->alat;

                    if ($alat->is_aset) {
                        continue;
                    }
                }


                return response()->json(['message' => 'Alat berhasil dikembalikan. Stok bertambah otomatis.']);
            });
    }

    //Laporan Kerusakan Staff
    public function laporanRusak()
    {
            try {
                $laporan = \App\Models\Peminjaman::with(['user', 'details.alat'])
                    ->where('kondisi_kembali', 'rusak')
                    ->orderBy('updated_at', 'desc')
                    ->get()
                    ->map(function ($pinjam) {
                        // Ambil semua nama alat dalam tiket ini
                        $daftarAlat = $pinjam->details->map(function($det) {
                            return $det->alat->nama_alat ?? 'Alat';
                        })->implode(', ');

                        $firstDetail = $pinjam->details->first();

                        return [
                            'id' => $pinjam->id,
                            'nama_mahasiswa' => $pinjam->user->name ?? 'N/A',
                            'nama_alat' => $daftarAlat,
                            'kode_tag' => ($firstDetail && $firstDetail->alat) ? $firstDetail->alat->kode_tag : '-',
                            'ruangan_lab' => $pinjam->ruangan_lab, 
                            'deskripsi_kerusakan' => $pinjam->deskripsi_kerusakan ?? 'Tidak ada deskripsi',
                            'tanggal_kembali' => $pinjam->tanggal_kembali,
                            'foto_before' => $pinjam->foto_before ? asset('storage/' . $pinjam->foto_before) : null,
                            'foto_after' => $pinjam->foto_after ? asset('storage/' . $pinjam->foto_after) : null,
                        ];
                    });

                return response()->json([
                    'status' => 'sukses',
                    'data' => $laporan
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengambil laporan rusak: ' . $e->getMessage()
                ], 500);
            }
    }

    // RIWAYAT MAHASISWA
    public function riwayatMahasiswa()
    {
            $riwayat = Peminjaman::with('details.alat')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();

            return response()->json([
                'status' => 'sukses',
                'data'   => $riwayat
            ]);
    }
    }