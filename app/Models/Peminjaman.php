<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman'; 

    protected $fillable = [
        'user_id',            
        'ruangan_lab',
        'tujuan_penggunaan',
        'status',             // pending, approved, ongoing, returned
        'kondisi_kembali',
        'deskripsi_kerusakan',
        'foto_before',
        'foto_after',
        'waktu_pinjam',       
        'waktu_kembali',      
        'tanggal_diambil',    
        'tanggal_kembali',    
    ];

    protected $casts = [
        'waktu_pinjam' => 'datetime',
        'waktu_kembali' => 'datetime',
        'tanggal_diambil' => 'datetime',
        'tanggal_kembali' => 'datetime',
    ];

    /**
     * Relasi ke User (Siapa yang meminjam)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(PeminjamanDetails::class, 'peminjaman_id');
    }
}