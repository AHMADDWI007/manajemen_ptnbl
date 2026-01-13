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
     * Tampilkan data dari 'pengolahan_basah' dan hitung asal bokar sesuai filter tanggal
     */
    public function index(Request $request)
    {
        $filterTanggal = $request->input('filter_tanggal', Carbon::today()->toDateString());

        // Ambil semua bak maturasi
        $listBakMaturasi = Maturasi::all();

        $data = [];

        foreach ($listBakMaturasi as $maturasi) {
            $asalBokar = $this->getAsalBokarByFilterTanggal($maturasi->uraian, $filterTanggal);
            $data[] = [
                'bak' => $maturasi->uraian,
                'asal_bokar' => $asalBokar,
                'stok_awal' => $maturasi->stok_awal,
                'stok_akhir' => $maturasi->stok_akhir,
                'tgl_masuk' => $maturasi->tgl_masuk,
                // Tambahkan data lain yang diperlukan sesuai kebutuhan
            ];
        }

        // Ambil data 'pengolahan_basah' yang K3-nya masih KOSONG untuk dropdown
        $daftar_bak_belum_uji = PengolahanBasah::whereNull('k3')
                                    ->orderBy('tanggal', 'desc')
                                    ->get();

        return view('Pengolahan.hasil_uji_bokar_diolah', compact('data', 'daftar_bak_belum_uji', 'filterTanggal'));
    }

    /**
     * Fungsi untuk hitung asal bokar per bak maturasi sampai tanggal filter
     */
    private function getAsalBokarByFilterTanggal(string $bakMaturasi, string $filterTanggal): ?string
    {
        $maturasi = Maturasi::where('uraian', $bakMaturasi)->first();
        if (!$maturasi) {
            return null;
        }

        $dataMasuk = PengolahanMaturasi::where('maturasi_id', $maturasi->id)
                     ->where('tgl_laporan', '<=', $filterTanggal)
                     ->orderBy('tgl_laporan')
                     ->get();

        $stokPerAsal = [];

        foreach ($dataMasuk as $item) {
            $asal = $item->asal_bokar ?? 'UNKNOWN';

            if (!isset($stokPerAsal[$asal])) {
                $stokPerAsal[$asal] = 0;
            }

            // Hitung stok bersih per asal bokar
            $stokPerAsal[$asal] += $item->masuk_hi - $item->diolah - $item->mutasi;
        }

        // Filter stok > 0
        $stokAktif = array_filter($stokPerAsal, fn($stok) => $stok > 0);

        if (count($stokAktif) === 1) {
            // Hanya satu asal bokar stok > 0
            return array_key_first($stokAktif);
        } elseif (count($stokAktif) > 1) {
            // Lebih dari satu asal bokar stok > 0 berarti campuran
            return 'CMP';
        } else {
            // Semua stok habis, ambil asal bokar terakhir yang masuk
            $lastAsal = $dataMasuk->last()->asal_bokar ?? null;
            return $lastAsal;
        }
    }

    /**
     * Ini BUKAN store (create), tapi UPDATE K3
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'pengolahan_basah_id' => 'required|exists:pengolahan_basah,id',
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = PengolahanBasah::find($request->pengolahan_basah_id);

        if (!$data) {
             return redirect()->back()->withErrors(['error' => 'Data pengolahan basah tidak ditemukan.']);
        }

        $netto_basah = $data->netto_basah;
        $k3_value = $request->k3;
        $netto_kering = $netto_basah * ($k3_value / 100);

        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering,
        ]);

        HasilUjiBokarDiolah::create([
            'tanggal'       => $data->tanggal,
            'bak_maturasi'  => $data->bak_maturasi,
            'jenis'         => $data->jenis,
            'netto_basah'   => $netto_basah,
            'k3'            => $k3_value,
            'netto_kering'  => $netto_kering
        ]);

        // Ambil asal_bokar dari request (kalau ada) atau dari jenis pengolahan basah
        $asal_bokar = $request->input('asal_bokar', $data->jenis);

        // Panggil trigger update maturasi dengan asal bokar
        $this->updateMaturasiMasukHI($data->bak_maturasi, $netto_kering, $data->tanggal, $asal_bokar);

        return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data K3 berhasil disimpan dan Maturasi diupdate.');
    }

    // Fungsi show, edit, update, destroy mengarah ke PengolahanBasah

    public function show($id): JsonResponse
    {
        $data = PengolahanBasah::find($id);
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = PengolahanBasah::find($id);
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
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

        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering
        ]);

        HasilUjiBokarDiolah::where('bak_maturasi', $data->bak_maturasi)
                           ->where('tanggal', $data->tanggal)
                           ->update([
                               'k3' => $k3_value,
                               'netto_kering' => $netto_kering
                           ]);

        $asal_bokar = $request->input('asal_bokar', $data->jenis);

        $this->updateMaturasiMasukHI($data->bak_maturasi, $netto_kering, $data->tanggal, $asal_bokar);

        return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data K3 berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $data = PengolahanBasah::find($id);
        if ($data) {
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
        $nomor = (int) filter_var($bakNameFromBasah, FILTER_SANITIZE_NUMBER_INT);
        if ($nomor == 0) {
            Log::error("Trigger Gagal: Tidak bisa menemukan nomor Bak dari '$bakNameFromBasah'");
            return;
        }

        $uraianMaturasi = "Di Bak Maturasi-" . $nomor;

        $maturasi = Maturasi::where('uraian', $uraianMaturasi)->first();
        if (!$maturasi) { 
            Log::error("Trigger Gagal: Bak '$uraianMaturasi' tidak ditemukan di tabel maturasis.");
            return; 
        }

        $tanggalKejadian = Carbon::parse($tanggalInput);

        if ($maturasi->updated_at->isSameDay($tanggalKejadian)) {
            $maturasi->masuk_hi += $masuk_hi; 
            $maturasi->stok_akhir = $maturasi->stok_awal - $maturasi->diolah - $maturasi->mutasi + $maturasi->masuk_hi;
        } else {
            $maturasi->stok_awal = $maturasi->stok_akhir;
            $maturasi->diolah = 0;
            $maturasi->mutasi = 0;
            $maturasi->masuk_hi = $masuk_hi;
            $maturasi->stok_akhir = $maturasi->stok_awal + $masuk_hi;
        }

        if ($masuk_hi > 0) {
             $maturasi->tgl_masuk = $tanggalKejadian;
             $maturasi->umur = 0;
             $maturasi->keterangan = strtoupper(Carbon::parse($tanggalKejadian)->locale('en')->translatedFormat('d M Y'));
        }

        if (!empty($asal_bokar)) {
            if (empty($maturasi->asal_bokar)) {
                $maturasi->asal_bokar = $asal_bokar;
            } elseif ($maturasi->asal_bokar !== $asal_bokar && $maturasi->asal_bokar !== 'CMP') {
                $maturasi->asal_bokar = 'CMP';
            }
        }

        // Ini supaya update asal_bokar di tabel maturasi juga

        $maturasi->updated_at = $tanggalKejadian;
        $maturasi->save();

        PengolahanMaturasi::create([
            'maturasi_id' => $maturasi->id,
            'tgl_laporan' => $tanggalKejadian,
            'masuk_hi' => $masuk_hi,
            'asal_bokar' => $asal_bokar,
            'diolah' => 0,
            'mutasi' => 0,
            'keterangan' => 'Otomatis dari Uji Bokar'
        ]);

        Log::info("Trigger Berhasil: Maturasi '$uraianMaturasi' diupdate dengan 'masuk_hi' $masuk_hi");
    }
}
