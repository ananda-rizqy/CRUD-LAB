<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman'; 

    protected $fillable = [
        'alat_id',
        'nama_mahasiswa',
        'nim',
        'laboratorium',
        'tujuan_penggunaan',
        'waktu_pinjam',
        'waktu_kembali',
        'kondisi_pinjam',
        'status',
        'foto_before',
        'tanggal_dikembalikan',
        'foto_after',
        'kondisi_kembali',
        'deskripsi_kerusakan',
    ];

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }

    // Accessor untuk timezone WIB
    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
}