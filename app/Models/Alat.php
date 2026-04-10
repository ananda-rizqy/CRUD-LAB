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
        'is_aset',
    ];

    public function peminjaman()
    {
        return $this->belongsToMany(Peminjaman::class, 'peminjaman__details', 'alat_id', 'peminjaman_id');
    }

    public function peminjamanDetails()
    {
    return $this->hasMany(
        \App\Models\PeminjamanDetails::class,
        'alat_id'
    );
    }

    protected static function boot()
    {
    parent::boot();

    // Saat create
    static::creating(function ($alat) {
        if (empty($alat->kode_tag)) {
            $alat->kondisi = 'Baik';
        }
    });

    
    static::saving(function ($alat) {
        if ($alat->is_aset) {
            $alat->jumlah = 1; // paksa aset selalu 1
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