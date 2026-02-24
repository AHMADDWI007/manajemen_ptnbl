<?php

namespace App\Models;

use App\Models\HasilUjiLabBokarDiolah;
use App\Models\Maturasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengolahanBasah extends Model
{
    use HasFactory;
    protected $table = 'pengolahan_basah';
    protected $primaryKey = 'id_pengolahan_basah';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'berat_truck' => 'double', 'berat_timbang' => 'double', 
        'netto_basah' => 'double', 'k3' => 'double', 'netto_kering' => 'double',
    ];

    // =========================================================================
    // 🔥 OTOMATIS ROUND SAAT SIMPAN (MUTATORS)
    // =========================================================================

    public function setBeratTruckAttribute($value)
    {
        $this->attributes['berat_truck'] = round($value);
    }

    public function setBeratTimbangAttribute($value)
    {
        $this->attributes['berat_timbang'] = round($value);
    }

    public function setNettoBasahAttribute($value)
    {
        $this->attributes['netto_basah'] = round($value);
    }

    public function setNettoKeringAttribute($value)
    {
        $this->attributes['netto_kering'] = round($value);
    }

    public function maturasi(): BelongsTo
    {
        return $this->belongsTo(Maturasi::class, 'id_maturasi');
    }

    public function hasilUjiLabBokarDiolah(): HasOne
    {
        return $this->hasOne(HasilUjiLabBokarDiolah::class, 'id_pengolahan_basah');
    }
}