<?php

namespace App\Http\Controllers;

// ----- IMPORT SEMUA MODEL DAN CLASS YANG KITA BUTUHKAN -----
use App\Models\PengolahanBasah;
use App\Models\HasilUjiBokarDiolah; 
use App\Models\Maturasi;             // <-- DITAMBAHKAN
use App\Models\PengolahanMaturasi;   // <-- DITAMBAHKAN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller; // <-- DITAMBAHKAN
use Carbon\Carbon;                   // <-- DITAMBAHKAN
use Illuminate\Support\Facades\Log; // <-- DITAMBAHKAN
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HasilUjiBokarDiolahController extends Controller
{
    /**
     * Tampilkan data dari 'pengolahan_basah'
     */
    public function index()
    {
        // Ganti nama variabel agar sesuai dengan view
        $data_diolah = PengolahanBasah::orderBy('tanggal', 'desc')->get();
        
        // Ambil data 'pengolahan_basah' yang K3-nya masih KOSONG
        // untuk mengisi dropdown di modal tambah K3
        $daftar_bak_belum_uji = PengolahanBasah::whereNull('k3')
                                    ->orderBy('tanggal', 'desc')
                                    ->get();

        // Pastikan nama view ini benar: 'Pengolahan.hasil_uji_bokar_diolah'
        return view('Pengolahan.hasil_uji_bokar_diolah', compact('data_diolah', 'daftar_bak_belum_uji'));
    }

    /**
     * Ini BUKAN store (create), tapi UPDATE K3
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // Validasi id dari 'pengolahan_basah'
            'pengolahan_basah_id' => 'required|exists:pengolahan_basah,id',
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 1. Cari data pengolahan basah berdasarkan ID
        $data = PengolahanBasah::find($request->pengolahan_basah_id);

        if (!$data) {
             return redirect()->back()->withErrors(['error' => 'Data pengolahan basah tidak ditemukan.']);
        }

        // 2. Ambil Netto Basah-nya
        $netto_basah = $data->netto_basah;
        $k3_value = $request->k3;

        // 3. Hitung Netto Kering
        $netto_kering = $netto_basah * ($k3_value / 100);

        // 4. Update data tersebut (Tabel: pengolahan_basah)
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering,
        ]);
        


        // 5. Buat catatan/log di tabel 'hasil_uji_bokar_diolah'
        HasilUjiBokarDiolah::create([
            'tanggal'       => $data->tanggal,      // Ambil dari data basah
            'bak_maturasi'  => $data->bak_maturasi,  // Ambil dari data basah
            'jenis'         => $data->jenis,         // Ambil dari data basah
            'netto_basah'   => $netto_basah,
            'k3'            => $k3_value,
            'netto_kering'  => $netto_kering
        ]);
        
        // ==========================================================
        // 6. INI ADALAH TRIGGER (PEMICU) YANG HILANG KE FITUR 3
        //    (Sekarang akan berfungsi karena fungsinya sudah ada di bawah)
        // ==========================================================
     $this->updateMaturasiMasukHI($data->bak_maturasi, $netto_kering, $data->tanggal, $data->jenis);

        // ==========================================================

        return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data K3 berhasil disimpan dan Maturasi diupdate.');
    }

    // Fungsi show, edit, update, destroy sekarang mengarah ke PengolahanBasah
    
    public function show($id): JsonResponse
    {
        $data = PengolahanBasah::find($id); // Ganti ke PengolahanBasah
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = PengolahanBasah::find($id); // Ganti ke PengolahanBasah
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // Fungsi update di halaman ini sekarang MENGEDIT K3
        $validator = Validator::make($request->all(), [
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
             return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = PengolahanBasah::find($id);
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan.']);
        }
        
        $k3_value = $request->k3;
        $netto_kering = $data->netto_basah * ($k3_value / 100);

        // 1. Update 'pengolahan_basah'
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering
        ]);
        

        
        // 2. Update juga log 'hasil_uji_bokar_diolah'
        HasilUjiBokarDiolah::where('bak_maturasi', $data->bak_maturasi)
                           ->where('tanggal', $data->tanggal)
                           ->update([
                               'k3' => $k3_value,
                               'netto_kering' => $netto_kering
                           ]);
                           
        // 3. JALANKAN TRIGGER JUGA DI UPDATE
        $this->updateMaturasiMasukHI($data->bak_maturasi, $netto_kering, $data->tanggal, $data->jenis);


        return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data K3 berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        // Hati-hati! Ini akan menghapus data dari 'pengolahan_basah'
        $data = PengolahanBasah::find($id);
        if ($data) {
            
            // Hapus juga data di 'hasil_uji_bokar_diolah'
            HasilUjiBokarDiolah::where('bak_maturasi', $data->bak_maturasi)
                              ->where('tanggal', $data->tanggal)
                              ->delete();
            $data->delete();
            return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data berhasil dihapus.');
        }
        return redirect()->route('hasil_uji_bokar_diolah.index')->withErrors(['error' => 'Data tidak ditemukan.']);
    }
    
    
    // ===================================================================
    // FUNGSI TRIGGER OTOMATIS KE PENGOLAHAN MATURASI (YANG HILANG)
    // ===================================================================
    private function updateMaturasiMasukHI(string $bakNameFromBasah, float $masuk_hi, string $tanggalInput, ?string $asal_bokar = null): void
    {
        // $bakNameFromBasah adalah "Bak Maturasi 1" (dari tabel pengolahan_basah)
        
        // --- 1. TERJEMAHKAN NAMA (SESUAI DATABASE ANDA) ---
        $nomor = (int) filter_var($bakNameFromBasah, FILTER_SANITIZE_NUMBER_INT);
        if ($nomor == 0) {
            Log::error("Trigger Gagal: Tidak bisa menemukan nomor Bak dari '$bakNameFromBasah'");
            return;
        }
        // Buat nama yang benar: "Di Bak Maturasi-1" (sesuai tabel 'maturasis')
        $uraianMaturasi = "Di Bak Maturasi-" . $nomor;
        // --- AKHIR PERBAIKAN NAMA ---

        $maturasi = Maturasi::where('uraian', $uraianMaturasi)->first();
        if (!$maturasi) { 
            Log::error("Trigger Gagal: Bak '$uraianMaturasi' tidak ditemukan di tabel maturasis.");
            return; 
        }

        // Tentukan tanggal kejadian
        $tanggalKejadian = Carbon::parse($tanggalInput);

        // Cek apakah data di DB adalah data pada tanggal kejadian
        if ($maturasi->updated_at->isSameDay($tanggalKejadian)) {
            // Data sudah di-input/di-trigger hari ini, tambahkan 'masuk_hi'
            $maturasi->masuk_hi += $masuk_hi; 
            $maturasi->stok_akhir = $maturasi->stok_awal - $maturasi->diolah - $maturasi->mutasi + $maturasi->masuk_hi;
        } else {
            // Data masih data kemarin, lakukan "tutup buku"
            $maturasi->stok_awal = $maturasi->stok_akhir;
            $maturasi->diolah = 0;
            $maturasi->mutasi = 0;
            $maturasi->masuk_hi = $masuk_hi; // Set 'masuk_hi' baru
            $maturasi->stok_akhir = $maturasi->stok_awal + $masuk_hi;
        }
        
        // Jika ada 'masuk_hi' baru, reset tgl_masuk & umur
        if ($masuk_hi > 0) {
             $maturasi->tgl_masuk = $tanggalKejadian;
             $maturasi->umur = 0;
             $maturasi->keterangan = strtoupper(Carbon::parse($tanggalKejadian)->locale('en')->translatedFormat('d M Y'));
        }
        // 🔹 Tambahan ini: isi atau ubah asal bokar otomatis dari jenis pengolahan basah
if (!empty($asal_bokar)) {
    if (empty($maturasi->asal_bokar)) {
        // Kalau belum ada asal, isi langsung
        $maturasi->asal_bokar = $asal_bokar;
    } elseif ($maturasi->asal_bokar !== $asal_bokar && $maturasi->asal_bokar !== 'CMP') {
        // Kalau sudah ada tapi berbeda dan belum CMP, ubah jadi CMP
        $maturasi->asal_bokar = 'CMP';
    }
}

        // Aksi 1: Update tabel Status 'maturasis'
        $maturasi->updated_at = $tanggalKejadian;
        $maturasi->save();

        // Aksi 2: Buat Log di 'pengolahan_maturasi'
        PengolahanMaturasi::create([
            'maturasi_id' => $maturasi->id,
            'tgl_laporan' => $tanggalKejadian,
            'diolah' => 0, 'mutasi' => 0,
            'masuk_hi' => $masuk_hi,
            'keterangan' => 'Otomatis dari Uji Bokar'
        ]);
        
        Log::info("Trigger Berhasil: Maturasi '$uraianMaturasi' diupdate dengan 'masuk_hi' $masuk_hi");
    }
}