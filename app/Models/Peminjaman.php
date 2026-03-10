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
        'alat_id',
        'tujuan_penggunaan',
        'waktu_pinjam',       
        'waktu_kembali',      
        'tanggal_diambil',    
        'tanggal_kembali',    
        'status',             // pending, approved, returned, rejected
        'foto_before',
        'foto_after',
        'kondisi_kembali',    // baik, rusak
        'deskripsi_kerusakan',
    ];

    protected $casts = [
        'waktu_pinjam' => 'datetime',
        'waktu_kembali' => 'datetime',
        'tanggal_diambil' => 'datetime',
        'tanggal_kembali' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }
}