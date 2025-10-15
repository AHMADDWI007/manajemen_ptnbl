<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiTroli;

class HasilUjiTroliController extends Controller
{
    public function index()
    {
        $data_troli = HasilUjiTroli::orderBy('tanggal', 'desc')->get();
        return view('Pengolahan.hasil_uji_troli', compact('data_troli'));
    }
}
