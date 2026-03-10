<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class PenggunaanRuang extends Model
{
    use HasFactory;
    protected $table = 'penggunaan_ruang';

    protected $fillable = [
        'user_id',
        'laboratorium',
        'kondisi_masuk',
        'foto_before',
        'keperluan',
        'kondisi_keluar',
        'foto_after',
        'waktu_keluar',
        'jam_mulai',
        'jam_selesai',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}