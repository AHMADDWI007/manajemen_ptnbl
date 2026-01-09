<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Http\Controllers\Controller;
use App\Models\PengolahanBasah;
use App\Models\HasilUjiLabBokarDiolah; // 🔥 [PERBAIKAN] Nama Model Baru
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class HasilUjiBokarDiolahController extends Controller
{
    /**
     * Tampilkan data dari 'pengolahan_basah'
     */
    public function index()
    {
        // PERBAIKAN: Gunakan Eager Loading 'maturasi' agar nama bak bisa diambil di View
        $data_diolah = PengolahanBasah::with('maturasi')
                                      ->whereNotNull('k3') // Menampilkan history yang sudah diisi
                                      ->orderBy('tanggal', 'desc')
                                      ->get();
        
        // Ambil data 'pengolahan_basah' yang K3-nya masih KOSONG
        // untuk mengisi dropdown di modal tambah K3
        $daftar_bak_belum_uji = PengolahanBasah::with('maturasi')
                                      ->whereNull('k3')
                                      ->orderBy('tanggal', 'desc')
                                      ->get();

        return view('DataLaboratorium.hasil-uji-bokar-diolah', compact('data_diolah', 'daftar_bak_belum_uji'));
    }

    /**
     * Ini BUKAN store (create), tapi UPDATE K3 pada Transaksi & Create Log
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // 🔥 [PERBAIKAN] Validasi FK ke 'id_pengolahan_basah'
            'id_pengolahan_basah' => 'required|exists:pengolahan_basah,id_pengolahan_basah',
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 1. Cari data pengolahan basah berdasarkan ID
        $data = PengolahanBasah::find($request->id_pengolahan_basah);

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
        
        // 5. Buat catatan/log di tabel 'hasil_uji_lab_bokar_diolah'
        // 🔥 PERBAIKAN: Gunakan Model Baru & FK yang Sesuai
        HasilUjiLabBokarDiolah::create([
            'id_pengolahan_basah' => $data->id_pengolahan_basah,
            'id_maturasi'       => $data->id_maturasi, // Ambil dari data basah
            'tanggal'           => $data->tanggal,
            'jenis'             => $data->jenis,
            'netto_basah'       => $netto_basah,
            'k3'                => $k3_value,
            'netto_kering'      => $netto_kering
        ]);
        
        // 6. TRIGGER UPDATE STOK MATURASI
        // Kita panggil fungsi update stok agar saldo maturasi bertambah otomatis
        // Note: Kita butuh nama bak untuk log, ambil dari relasi
        $namaBak = $data->maturasi ? $data->maturasi->uraian : 'Unknown Bak';
        
        // Panggil helper update (Diadaptasi ke ID)
        $this->updateMaturasiMasukHI($data->id_maturasi, $netto_kering, $data->tanggal, $data->jenis, $namaBak);

        return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 berhasil disimpan dan Stok Maturasi bertambah.');
    }

    public function show($id): JsonResponse
    {
        $data = PengolahanBasah::with('maturasi')->find($id);
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = PengolahanBasah::with('maturasi')->find($id);
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // Fungsi update di halaman ini sekarang MENGEDIT K3 yang salah input
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
        $netto_kering_baru = $data->netto_basah * ($k3_value / 100);

        // 1. Update 'pengolahan_basah'
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering_baru
        ]);
        
        // 2. Update juga log 'hasil_uji_bokar_diolah'
        // 🔥 PERBAIKAN: Gunakan id_pengolahan_basah & Model Baru
        HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $data->id_pengolahan_basah)->update([
             'k3' => $k3_value,
             'netto_kering' => $netto_kering_baru
        ]);
                            
        // 3. JALANKAN TRIGGER UPDATE STOK ULANG
        // (Logic sederhana: Timpa ulang masuk_hi di log maturasi)
        $namaBak = $data->maturasi ? $data->maturasi->uraian : 'Unknown Bak';
        $this->updateMaturasiMasukHI($data->id_maturasi, $netto_kering_baru, $data->tanggal, $data->jenis, $namaBak);

        return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        // Hapus data K3 (kembalikan ke status belum uji)
        $data = PengolahanBasah::find($id);
        if ($data) {
            
            // Hapus log di 'hasil_uji_bokar_diolah'
            // 🔥 PERBAIKAN: Gunakan id_pengolahan_basah & Model Baru
            HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $data->id_pengolahan_basah)->delete();
            
            // Reset kolom K3 di tabel induk
            $data->update(['k3' => null, 'netto_kering' => null]);
            
            // Reset Stok Maturasi (Kurangi stok yang sudah masuk)
            // Note: Ini logic kompleks, sederhananya kita set masuk_hi jadi 0 untuk transaksi ini di log maturasi
            // Tapi karena log maturasi itu agregat harian, menghapus satu transaksi butuh hitung ulang.
            // Untuk amannya, kita biarkan user memperbaiki manual lewat menu Maturasi jika perlu,
            // atau set 0 jika yakin ini satu-satunya transaksi hari itu.
            
            return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 dihapus. Silakan input ulang.');
        }
        return redirect()->route('hasil-uji-bokar-diolah.index')->withErrors(['error' => 'Data tidak ditemukan.']);
    }
    
    // ===================================================================
    // FUNGSI TRIGGER OTOMATIS KE PENGOLAHAN MATURASI
    // ===================================================================
    private function updateMaturasiMasukHI(int $id_maturasi, float $masuk_hi, string $tanggalInput, ?string $asal_bokar = null, string $uraianMaturasi = ''): void
    {
        $maturasi = Maturasi::find($id_maturasi);
        
        if (!$maturasi) { 
            Log::error("Trigger Gagal: ID Bak '$id_maturasi' tidak ditemukan.");
            return; 
        }

        $tanggalKejadian = Carbon::parse($tanggalInput);

        // Update Master
        // Logic: Tambahkan ke stok akhir.
        // (Perhatian: Ini simplifikasi. Idealnya hitung ulang dari semua log, tapi untuk performa kita increment/replace)
        
        // Cek Log Harian
        $logHarian = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tgl_laporan', $tanggalKejadian)
            ->first();

        if ($logHarian) {
            // Jika sudah ada log hari itu, kita tambah/update
            // Karena ini dari Edit/Input K3, sebaiknya kita hitung ulang total masuk hari itu dari tabel HasilUjiBokarDiolah
            // 🔥 PERBAIKAN: Gunakan Model Baru & id_maturasi
            $totalMasukReal = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', $tanggalKejadian)
                ->sum('netto_kering');
                
            $logHarian->update(['masuk_hi' => $totalMasukReal]);
            $masuk_hi = $totalMasukReal; // Pakai total real untuk update master
        } else {
            // Buat log baru
            // 🔥 PERBAIKAN: Gunakan id_maturasi
            PengolahanMaturasi::create([
                'id_maturasi' => $maturasi->id_maturasi,
                'tgl_laporan' => $tanggalKejadian,
                'diolah' => 0, 'mutasi' => 0,
                'masuk_hi' => $masuk_hi,
                'keterangan' => 'Otomatis dari Uji Bokar'
            ]);
        }
        
        // Recalculate Master Stok (Biar aman)
        // Ambil stok awal H-1
        // 🔥 PERBAIKAN: Gunakan id_maturasi
        $stokAwal = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tgl_laporan', '<', $tanggalKejadian)
            ->selectRaw('SUM(masuk_hi) - SUM(diolah) - SUM(mutasi) as stok')->value('stok') ?? 0;
            
        // Ambil transaksi hari ini
        // 🔥 PERBAIKAN: Gunakan id_maturasi
        $transaksiHariIni = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tgl_laporan', $tanggalKejadian)
            ->first();
            
        $masuk = $transaksiHariIni->masuk_hi ?? 0;
        $keluar = ($transaksiHariIni->diolah ?? 0) + ($transaksiHariIni->mutasi ?? 0);
        
        // Update Master
        $maturasi->stok_akhir = $stokAwal + $masuk - $keluar;
        $maturasi->stok_awal = $stokAwal; // Opsional, update stok awal master ke posisi hari ini
        
        // Update Tgl Masuk & Asal
        if ($maturasi->stok_akhir > 0) {
             if ($masuk > 0) {
                 $maturasi->setAttribute('updated_at', $tanggalKejadian);;
                 $maturasi->umur = 0;
                 $maturasi->keterangan = strtoupper($tanggalKejadian->translatedFormat('d M Y'));
             }
             
             if (!empty($asal_bokar)) {
                if (empty($maturasi->asal_bokar) || $maturasi->stok_awal <= 0) {
                    $maturasi->asal_bokar = $asal_bokar;
                } elseif ($maturasi->asal_bokar !== $asal_bokar && $maturasi->asal_bokar !== 'CMP') {
                    $maturasi->asal_bokar = 'CMP';
                }
             }
        } else {
             $maturasi->keterangan = 'KOSONG';
             $maturasi->asal_bokar = null;
        }
        
        $maturasi->updated_at = $tanggalKejadian;
        $maturasi->save();
        
        Log::info("Trigger Berhasil: Maturasi ID $id_maturasi diupdate.");
    }
}