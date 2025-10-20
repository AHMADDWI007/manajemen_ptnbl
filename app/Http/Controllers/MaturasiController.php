<?php

namespace App\Http\Controllers;

use App\Models\Maturasi; // Menggunakan Model yang Anda berikan
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MaturasiController extends Controller
{
    /**
     * Menampilkan semua data maturasi (agar bisa difilter).
     */
    public function index()
    {
        // Mengambil semua data, diurutkan dari yang terbaru, untuk ditampilkan di tabel
        $data_maturasi = Maturasi::orderBy('created_at', 'desc')->get();
        return view('Pengolahan.data_maturasi', compact('data_maturasi'));
    }

    /**
     * API untuk mengambil data terakhir dari sebuah 'bak maturasi'.
     */
    public function getLatestDataForUraian(Request $request)
    {
        $request->validate(['uraian' => 'required|string']);

        $latestData = Maturasi::where('uraian', $request->uraian)
                              ->orderBy('created_at', 'desc')
                              ->first();

        if ($latestData && $latestData->tgl_masuk) {
            // Umur dihitung dari tanggal masuk stok sampai hari ini
            $umur = $latestData->tgl_masuk->diffInDays(Carbon::today());
            $latestData->umur_hari_ini = $umur;
            return response()->json($latestData);
        }
        
        return response()->json(null);
    }
    
    /**
     * Menyimpan data baru dengan logika kalkulasi otomatis.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'uraian' => 'required|string',
            'stok_awal' => 'required|numeric',
            'tgl_masuk_hidden' => 'nullable|date',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string',
        ]);
        
        $diolah = $validated['diolah'] ?? 0;
        $mutasi = $validated['mutasi'] ?? 0;
        $masuk_hi = $validated['masuk_hi'] ?? 0;

        $stok_akhir = $validated['stok_awal'] - $diolah - $mutasi + $masuk_hi;

        $tgl_masuk_lama = $validated['tgl_masuk_hidden'] ? Carbon::parse($validated['tgl_masuk_hidden']) : null;
        $tgl_masuk_baru = $tgl_masuk_lama;
        $keterangan_baru = $tgl_masuk_lama ? $tgl_masuk_lama->isoFormat('D MMMM') : 'KOSONG';

        if ($stok_akhir <= 0) {
            $tgl_masuk_baru = null;
            $keterangan_baru = 'KOSONG';
        } elseif ($masuk_hi > 0) {
            $tgl_masuk_baru = Carbon::today();
            $keterangan_baru = $tgl_masuk_baru->isoFormat('D MMMM');
        }

        $umur_simpan = $tgl_masuk_baru ? $tgl_masuk_baru->diffInDays(Carbon::today()) : 0;

        Maturasi::create([
            'uraian' => $validated['uraian'],
            'stok_awal' => $validated['stok_awal'],
            'tgl_masuk' => $tgl_masuk_baru,
            'umur' => $umur_simpan,
            'diolah' => $diolah,
            'mutasi' => $mutasi,
            'masuk_hi' => $masuk_hi,
            'stok_akhir' => $stok_akhir,
            'asal_bokar' => $validated['asal_bokar'],
            'keterangan' => $keterangan_baru,
        ]);

        return redirect()->route('maturasi.index')->with('success', 'Data berhasil disimpan!');
    }

    /**
     * FUNGSI BARU: Mengambil data untuk modal Detail.
     */
    public function show($id)
    {
        $data = Maturasi::findOrFail($id);
        return response()->json($data);
    }

    /**
     * FUNGSI BARU: Mengambil data untuk modal Edit.
     */
    public function edit($id)
    {
        $data = Maturasi::findOrFail($id);
        return response()->json($data);
    }

    /**
     * FUNGSI BARU: Memperbarui data yang ada.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'stok_awal' => 'required|numeric',
            'tgl_masuk' => 'nullable|date',
            'diolah' => 'nullable|numeric',
            'mutasi' => 'nullable|numeric',
            'masuk_hi' => 'nullable|numeric',
            'asal_bokar' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $validated = $validator->validated();
        
        // Kalkulasi ulang stok akhir berdasarkan data yang diedit
        $stok_akhir = ($validated['stok_awal'] ?? 0) - ($validated['diolah'] ?? 0) - ($validated['mutasi'] ?? 0) + ($validated['masuk_hi'] ?? 0);
        $validated['stok_akhir'] = $stok_akhir;

        $maturasi = Maturasi::findOrFail($id);
        $maturasi->update($validated);

        return redirect()->route('maturasi.index')->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * FUNGSI BARU: Menghapus data.
     */
    public function destroy($id)
    {
        $maturasi = Maturasi::findOrFail($id);
        $maturasi->delete();
        return redirect()->route('maturasi.index')->with('success', 'Data berhasil dihapus!');
    }
}

