<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilUjiLabBokarDiolah extends Model
{
    use HasFactory;
    protected $table = 'hasil_uji_lab_bokar_diolah';
    protected $primaryKey = 'id_hasil_uji_lab_bokar_diolah';
    protected $guarded = [];
    protected $casts = ['tanggal' => 'date'];

    public function pengolahanBasah(): BelongsTo
    {
        return $this->belongsTo(PengolahanBasah::class, 'id_pengolahan_basah');
    }
    
    public function maturasi(): BelongsTo
    {
        return $this->belongsTo(Maturasi::class, 'id_maturasi');
    }
}