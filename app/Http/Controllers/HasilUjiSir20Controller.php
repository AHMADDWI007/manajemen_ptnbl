<?php

namespace App\Http\Controllers;

// Pastikan use statement ini ada
use App\Http\Controllers\Controller; 
use App\Models\HasilUjiSir20;
use Illuminate\Http\Request;

// Pastikan nama class ini sudah benar
class HasilUjiSir20Controller extends Controller
{
    public function index()
    {
        $data_sir_20 = HasilUjiSir20::latest()->get();
        
        // Pastikan nama folder dan file view ini sudah benar
        return view('Pengolahan.hasil_uji_sir_20', compact('data_sir_20'));
    }
}

