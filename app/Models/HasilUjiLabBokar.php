<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiLabBokar extends Model
{
    use HasFactory;

    protected $table = 'hasil_uji_lab_bokar';
    protected $primaryKey = 'id_hasil_uji_lab_bokar';

    protected $fillable = [
        'tanggal', 'suplier', 'no_sampel', 'k3', 'dirt', 'ask', 'po', 'pa', 'pri',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'k3'      => 'decimal:2',
        'dirt'    => 'decimal:2',
        'ask'     => 'decimal:2',
        'po'      => 'decimal:2',
        'pa'      => 'decimal:2',
        'pri'     => 'decimal:2',
    ];
}