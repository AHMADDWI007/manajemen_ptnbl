<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilUjiLabMaturasi extends Model
{
    use HasFactory;
    protected $table = 'hasil_uji_lab_maturasi';
    protected $primaryKey = 'id_hasil_uji_lab_maturasi';
    protected $guarded = [];
    protected $casts = ['tanggal' => 'date'];

    public function maturasi(): BelongsTo
    {
        return $this->belongsTo(Maturasi::class, 'id_maturasi');
    }
}