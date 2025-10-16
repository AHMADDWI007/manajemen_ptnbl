<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutuPrima extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'mutu_prima';

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'uraian',
        'kg',
        'pallet',
        'keterangan',
    ];
}
