<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_alat',
        'letak',
        'kode_tag', 
        'jumlah',   
        'kondisi',
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'alat_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alat) {
            if (empty($alat->kode_tag)) {
                $alat->kondisi = 'Baik';
            }
        });
    }

    // Accessor untuk format tanggal agar rapi di JSON
    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
}