<?php

namespace App\Models;

use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilUjiLabBokarDiolah extends Model
{
    use HasFactory;
    protected $table = 'hasil_uji_lab_bokar_diolah';
    protected $primaryKey = 'id_hasil_uji_lab_bokar_diolah';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'date',
        'netto_basah' => 'double',
        'k3' => 'double',
        'netto_kering' => 'double',
    ];

    // =========================================================================
    // 🔥 OTOMATIS ROUND SAAT SIMPAN (MUTATORS)
    // =========================================================================

    public function setNettoBasahAttribute($value)
    {
        // Bulatkan Netto Basah (0 desimal)
        $this->attributes['netto_basah'] = round($value);
    }

    public function setNettoKeringAttribute($value)
    {
        // Bulatkan Netto Kering (0 desimal)
        $this->attributes['netto_kering'] = round($value);
    }

    /**
     * CATATAN UNTUK K3:
     * Saya tidak menambahkan round() pada K3 agar tetap bisa menyimpan desimal 
     * (contoh: 60.5%). Jika K3 mau dibulatkan juga, tambahkan fungsi di bawah ini.
     */
    /*
    public function setK3Attribute($value)
    {
        $this->attributes['k3'] = round($value);
    }
    */

    // =========================================================================

    public function pengolahanBasah(): BelongsTo
    {
        return $this->belongsTo(PengolahanBasah::class, 'id_pengolahan_basah');
    }
    
    public function maturasi(): BelongsTo
    {
        return $this->belongsTo(Maturasi::class, 'id_maturasi');
    }
}