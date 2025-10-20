<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maturasi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'maturasis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uraian',
        'stok_awal',
        'tgl_masuk',
        'umur',
        'diolah',
        'mutasi',
        'masuk_hi',
        'stok_akhir',
        'asal_bokar',
        'keterangan',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tgl_masuk' => 'date',
    ];
}

