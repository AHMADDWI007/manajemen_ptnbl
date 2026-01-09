<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiLabTroli extends Model
{
    use HasFactory;

    protected $table = 'hasil_uji_lab_troli';
    protected $primaryKey = 'id_hasil_uji_lab_troli';

    protected $fillable = [
        'tanggal', 'no_trolly', 'k3', 'po', 'pa', 'pri', 'jam_sample', 'lama_pengeringan',
    ];
}