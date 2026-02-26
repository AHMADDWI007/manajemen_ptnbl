<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Exports\HasilUjiBokarDiolahExport;
use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\PengolahanBasah;
use App\Traits\MaturasiSyncTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class HasilUjiBokarDiolahController extends Controller
{

    use MaturasiSyncTrait; // 🔥 Tambahkan baris ini

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
        $this->syncMaturasi($data->id_maturasi, $data->tanggal);

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
        $this->syncMaturasi($data->id_maturasi, $data->tanggal);

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
            $this->syncMaturasi($id_maturasi, $tanggal);

            return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 dihapus dan Stok Maturasi telah diperbarui.');
        }
        return redirect()->route('hasil-uji-bokar-diolah.index')->withErrors(['error' => 'Data tidak ditemukan.']);
    }

    // Tambahkan fungsi ini di dalam class
    public function exportExcel(Request $request)
    {
        if (ob_get_length()) { ob_end_clean(); }
        while (ob_get_level() > 0) { ob_end_clean(); }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $namaFile = "Laporan_Uji_Bokar_Diolah_" . ($startDate ? Carbon::parse($startDate)->format('d-m-Y') : 'Semua') . ".xlsx";

        return Excel::download(new HasilUjiBokarDiolahExport($startDate, $endDate), $namaFile);
    }
}