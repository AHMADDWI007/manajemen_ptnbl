<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    use HasFactory;

    protected $table = 'catatan';
    protected $primaryKey = 'id';

    // Laravel secara default mencari kolom 'created_at' dan 'updated_at'.
    // Jika di tabel 'catatan' Anda tidak ada kolom tersebut, tambahkan ini:
    public $timestamps = false; 

    protected $fillable = [
        'tanggal', 'isi_catatan', 'status'
    ];

    protected $casts = [
        'tanggal' => 'datetime', // Ubah dari 'date' ke 'datetime' agar jam tidak hilang
    ];
}