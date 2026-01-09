<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alat extends Model
{
    use HasFactory;

    // Tambahkan fillable agar data bisa disimpan melalui API
    protected $fillable = [
        'nama_alat',      // 
        'ruang_lab', // 
        'total',          // 
        'tersedia',       // 
        'kondisi'         // 
    ];
}