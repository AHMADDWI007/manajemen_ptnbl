<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiLabBokar extends Model
{
    use HasFactory;

    protected $table = 'hasil_uji_lab_bokar';

    protected $fillable = [
        'tanggal',
        'no_kamar',
        'hasil_uji',
        'status'
    ];
}
