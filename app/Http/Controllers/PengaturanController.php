<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengaturan;

class PengaturanController extends Controller
{
    public function index()
    {
        // Ambil pengaturan API Bokar
        $apiBokar = Pengaturan::where('kunci', 'url_api_bokar')->first();
        
        return view('Pengaturan.pengaturan', compact('apiBokar'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'url_api_bokar' => 'required|url'
        ]);

        // Update data berdasarkan kunci
        Pengaturan::where('kunci', 'url_api_bokar')->update([
            'nilai' => $request->url_api_bokar
        ]);

        return redirect()->back()->with('success', 'URL API berhasil diperbarui!');
    }
}