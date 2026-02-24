<?php

namespace App\Http\Controllers\DataLaboratorium;

use Carbon\Carbon;
use App\Models\Maturasi;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah;
use Illuminate\Http\JsonResponse;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\HasilUjiLabBokarDiolah;

class HasilUjiBokarDiolahController extends Controller
{
    public function index()
    {
        $data_diolah = PengolahanBasah::with('maturasi')
                                      ->whereNotNull('k3')
                                      ->orderBy('tanggal', 'desc')
                                      ->get();
        
        $daftar_bak_belum_uji = PengolahanBasah::with('maturasi')
                                      ->whereNull('k3')
                                      ->orderBy('tanggal', 'desc')
                                      ->get();

        return view('DataLaboratorium.hasil-uji-bokar-diolah', compact('data_diolah', 'daftar_bak_belum_uji'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'id_pengolahan_basah' => 'required|exists:pengolahan_basah,id_pengolahan_basah',
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = PengolahanBasah::find($request->id_pengolahan_basah);
        if (!$data) {
             return redirect()->back()->withErrors(['error' => 'Data pengolahan basah tidak ditemukan.']);
        }

        $netto_basah = $data->netto_basah;
        $k3_value = $request->k3;
        $netto_kering = $netto_basah * ($k3_value / 100);

        // 1. Update data pengolahan_basah
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering,
        ]);
        
        // 🔥 PERBAIKAN: Gunakan updateOrCreate agar kebal terhadap duplikat / data lama
        HasilUjiLabBokarDiolah::updateOrCreate(
            ['id_pengolahan_basah' => $data->id_pengolahan_basah], // Cari berdasarkan ID ini
            [
                'id_maturasi'       => $data->id_maturasi,
                'tanggal'           => $data->tanggal,
                'jenis'             => $data->jenis,
                'netto_basah'       => $netto_basah,
                'k3'                => $k3_value,
                'netto_kering'      => $netto_kering
            ]
        );
        
        $namaBak = $data->maturasi ? $data->maturasi->uraian : 'Unknown Bak';
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
        
        // 🔥 PERBAIKAN: Gunakan updateOrCreate untuk menyelamatkan data lama yang belum punya log
        HasilUjiLabBokarDiolah::updateOrCreate(
            ['id_pengolahan_basah' => $data->id_pengolahan_basah],
            [
                'id_maturasi'       => $data->id_maturasi,
                'tanggal'           => $data->tanggal,
                'jenis'             => $data->jenis,
                'netto_basah'       => $data->netto_basah,
                'k3'                => $k3_value,
                'netto_kering'      => $netto_kering_baru
            ]
        );
                            
        $namaBak = $data->maturasi ? $data->maturasi->uraian : 'Unknown Bak';
        $this->updateMaturasiMasukHI($data->id_maturasi, $netto_kering_baru, $data->tanggal, $data->jenis, $namaBak);

        return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $data = PengolahanBasah::find($id);
        if ($data) {
            $id_maturasi = $data->id_maturasi;
            $tanggal     = $data->tanggal;

            // 1. Hapus Log History Lab
            HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $data->id_pengolahan_basah)->delete();
            
            // 2. Kosongkan Nilai Lab di Data Utama
            $data->update(['k3' => null, 'netto_kering' => null]);
            
            // 🔥 3. SYNC MATURASI: Panggil fungsi sinkronisasi
            // Kita gunakan fungsi updateMaturasiMasukHI yang sudah ada di controller ini
            // Kita kirim angka 0 (karena K3 dihapus), fungsi tersebut akan menghitung sum otomatis
            $this->updateMaturasiMasukHI($id_maturasi, 0, $tanggal);

            return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 dihapus dan Stok Maturasi telah diperbarui.');
        }
        return redirect()->route('hasil-uji-bokar-diolah.index')->withErrors(['error' => 'Data tidak ditemukan.']);
    }
    
    // ===================================================================
    // FUNGSI TRIGGER OTOMATIS KE PENGOLAHAN MATURASI (UPDATED)
    // ===================================================================
    private function updateMaturasiMasukHI(int $id_maturasi, float $masuk_hi, string $tanggalInput, ?string $asal_bokar_baru = null, string $uraianMaturasi = ''): void
    {
        $maturasi = Maturasi::find($id_maturasi);
        if (!$maturasi) return;

        $tgl = Carbon::parse($tanggalInput);
        
        $log = PengolahanMaturasi::firstOrNew([
            'id_maturasi' => $id_maturasi,
            'tgl_laporan' => $tgl
        ]);

        $totalMasukReal = PengolahanBasah::where('id_maturasi', $id_maturasi)
            ->whereDate('tanggal', $tgl)
            ->sum('netto_kering');
            
        if ($totalMasukReal <= 0) $totalMasukReal = $masuk_hi;

        $log->masuk_hi = $totalMasukReal;
        
        if (empty($log->keterangan)) {
            $log->keterangan = 'Fisik Bokar Masuk';
        }
        $log->save();

        $stokAwalH1 = round(PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $tgl)
            ->sum(DB::raw('masuk_hi - diolah - mutasi')), 2);

        $masuk  = $log->masuk_hi;
        $keluar = $log->diolah + $log->mutasi;
        
        $maturasi->stok_awal  = $stokAwalH1;
        $maturasi->stok_akhir = $stokAwalH1 + $masuk - $keluar;
        $maturasi->updated_at = $tgl;
        
        if ($maturasi->stok_akhir > 0) {
            $listJenis = PengolahanBasah::where('id_maturasi', $id_maturasi)
                        ->where('tanggal', $tanggalInput)
                        ->pluck('jenis')
                        ->filter(function ($value) { return !is_null($value) && $value !== '' && $value !== 'PENDING'; }) 
                        ->unique()
                        ->sort()
                        ->values();

            $labelAkhir = '';
            if ($listJenis->count() > 1) {
                $rincian = $listJenis->implode(', ');
                $labelAkhir = "CMP ($rincian)";
            } 
            elseif ($listJenis->count() == 1) {
                $labelAkhir = $listJenis->first();
            } 
            else {
                $labelAkhir = $maturasi->asal_bokar; 
            }

            $maturasi->asal_bokar = $labelAkhir; 
            
            // 🔥 PERBAIKAN: Hapus baris yang memaksa $maturasi->umur = 0 dan update keterangan di sini!
            // Biarkan sistem yang menghitungnya saat data ditampilkan.

        } else {
            $maturasi->keterangan = 'KOSONG'; 
            $maturasi->asal_bokar = null;
        }

        $maturasi->save();
    }
}