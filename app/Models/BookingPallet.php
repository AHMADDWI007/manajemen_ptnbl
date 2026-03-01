<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPallet extends Model {
    protected $table = 'booking_pallet';
    protected $primaryKey = 'id_booking_pallet';
    protected $fillable = ['id_pallet', 'tanggal'];
}