<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiLabSIR20 extends Model
{
    use HasFactory;

    protected $table = 'hasil_uji_lab_sir_20';
    protected $primaryKey = 'id_hasil_uji_lab_sir_20';

    protected $fillable = [
        'tanggal', 'jenis_kemasan', 'no_palet', 'po', 'pa', 'pri', 'dirt', 'ash', 'vm', 'money', 'nitrogen'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}