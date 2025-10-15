<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiLabBokar extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'hasil_uji_lab_bokar';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal',
        'suplier',
        'no_sampel', // Diubah dari 'no sampel' menjadi snake_case
        'k3',
        'dirt',
        'ask',
    ];
}