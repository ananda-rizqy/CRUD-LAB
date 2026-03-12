<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeminjamanDetails extends Model
{
    use HasFactory;

    // Sesuaikan dengan nama tabel di migration kamu (perhatikan double underscore jika ada)
    protected $table = 'peminjaman__details';

    protected $fillable = [
        'peminjaman_id',
        'alat_id',
        'jumlah_pinjam',
    ];

    /**
     * Relasi balik ke Header Peminjaman
     */
    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    /**
     * Relasi ke data Alat
     */
    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id'); // Pastikan model alat kamu namanya Alats atau Alat
    }
}