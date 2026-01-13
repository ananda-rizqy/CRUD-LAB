<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Alat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_alat',
        'ruang_lab',
        'total',
        'tersedia',
        'kondisi'
    ];
    
    // Override accessor - ambil langsung dari database tanpa konversi
    public function getCreatedAtAttribute($value)
    {
        // Return raw value dari database (sudah WIB)
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        // Return raw value dari database (sudah WIB)
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
}