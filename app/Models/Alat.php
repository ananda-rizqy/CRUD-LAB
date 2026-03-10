<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_alat',
        'kode',
        'total',
        'tersedia',
        'letak',
        'kondisi',
        'qrcode_token',
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'alat_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alat) {
            if (empty($alat->kode)) {
                // Ambil 3 huruf awal (Contoh: Splicer -> SPL)
                $prefix = strtoupper(substr($alat->nama_alat, 0, 3));

                $maxCode = static::where('kode', 'like', $prefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING(kode, 4) AS UNSIGNED)) as max_num")
                    ->first();

                $newNumber = ($maxCode && $maxCode->max_num) ? $maxCode->max_num + 1 : 1;

                // Generate kode dengan padding 3 digit (Contoh: SPL001, SPL002)
                $alat->kode = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
} 