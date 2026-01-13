<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenggunaanRuang extends Model
{
    use HasFactory;

    // Nama tabel harus sama dengan yang ada di migration
    protected $table = 'penggunaan_ruang';

    protected $fillable = [
        'nama_mahasiswa',
        'nim',
        'laboratorium',
        'keperluan',
        'foto_before',
        'foto_after',
        'waktu_masuk',
        'waktu_keluar',
        'status',
        'kondisi_lab'
    ];
    protected $casts = [
        'waktu_masuk'  => 'datetime:Y-m-d H:i:s',
        'waktu_keluar' => 'datetime:Y-m-d H:i:s',
    ];
    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
}