<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiSir20 extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     */
    protected $table = 'hasil_uji_sir_20';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Sesuai dengan kolom baru yang diminta.
     */
    protected $fillable = [
        'no_palet', // 'no.palet' diubah menjadi snake_case
        'po',
        'pa',
        'pri',
        'dirt',
        'ask', // 'ask' akan kita asumsikan sebagai 'ash' (kadar abu)
        'vm',  // 'vm' akan kita asumsikan sebagai 'volatile_matter'
        'money',
        'nitrogen',
    ];
}