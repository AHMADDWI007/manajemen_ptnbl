<?php

namespace App\Http\Controllers;

use App\Models\PengolahanBasah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PengolahanBasahController extends Controller
{
   public function index()
    {
        $data_pengolahan = PengolahanBasah::orderBy('tanggal', 'desc')->get();
        $today = Carbon::today();

        // ---------------------------------------------------------------------
        // TODO: LOGIKA UNTUK TABEL RINGKASAN (ATAS)
        // (Ini masih sama seperti sebelumnya, sesuaikan jika perlu)
        // ---------------------------------------------------------------------
        $stok_awal = 1500.00; // Contoh placeholder
        $bokar_masuk_hi = 300.00; 
        $bokar_masuk_sdhi = 3000.00;
        $bokar_diolah_hi = $data_pengolahan->where('tanggal', $today)->sum('netto_basah');
        $bokar_diolah_sdhi = $data_pengolahan->sum('netto_basah');
        $stok_akhir = ($stok_awal + $bokar_masuk_sdhi) - $bokar_diolah_sdhi;

        $summary_data = [
            'stok_awal' => $stok_awal, 'masuk_hi' => $bokar_masuk_hi, 'masuk_sdhi' => $bokar_masuk_sdhi,
            'diolah_hi' => $bokar_diolah_hi, 'diolah_sdhi' => $bokar_diolah_sdhi, 'stok_akhir' => $stok_akhir,
        ];

        // ---------------------------------------------------------------------
        // TODO: LOGIKA BARU UNTUK TOTAL FOOTER (BAWAH)
        // ---------------------------------------------------------------------

        // Asumsi: 'MB5' adalah 'PT' dan 'SW' adalah 'DS'
        // (Silakan ganti 'MB5' atau 'SW' jika asumsi saya salah)
        
        
        // 1. Hitung Total Netto Kering untuk 'PT' (misal: PT)
        $total_pt_netto_kering = $data_pengolahan->where('jenis', 'PT')->sum('netto_kering');
        
        // 2. Hitung Total Netto Kering untuk 'DS' (misal: SW)
        $total_ds_netto_kering = $data_pengolahan->where('jenis', 'DS')->sum('netto_kering');
        
        // 3. Hitung Jumlah (Total PT + Total DS)
        $jumlah_netto_kering = $total_pt_netto_kering + $total_ds_netto_kering;

        $total_data = [
            'total_pt_netto_kering' => $total_pt_netto_kering, // Data baru
            'total_ds_netto_kering' => $total_ds_netto_kering, // Data baru
            'jumlah_netto_kering' => $jumlah_netto_kering,   // Data baru
        ];
        // ---------------------------------------------------------------------

        return view('Pengolahan.pengolahan_basah', compact('data_pengolahan', 'summary_data', 'total_data'));
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'tanggal' => 'required|date',
        'bak_maturasi' => 'required|string',
        'jenis' => 'required|string|in:PT,DS,INHUT',
        'berat_truck' => 'required|numeric|min:0',
        'berat_timbang' => 'required|numeric|min:' . $request->input('berat_truck', 0),
    ], [
        'berat_timbang.min' => 'Berat Timbang harus lebih besar dari Berat Truck.'
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $data = $validator->validated();
    $netto_basah = $data['berat_timbang'] - $data['berat_truck'];

    // Simpan data di pengolahan basah
    $basah = PengolahanBasah::create(array_merge($data, [
        'netto_basah' => $netto_basah,
        'k3' => null,
        'netto_kering' => null,
    ]));

    /**
     * =============================
     * LOGIKA OTOMATIS KE MATURASI
     * =============================
     */
    $bak = $data['bak_maturasi'];
    $jenis = strtoupper($data['jenis']);

    // Cari data maturasi berdasarkan uraian bak (misal: "Di Bak Maturasi-1")
    $maturasi = \App\Models\Maturasi::where('uraian', 'LIKE', "%$bak%")->first();

    if ($maturasi) {
        // Cek asal bokar terakhir
        $asalSebelumnya = $maturasi->asal_bokar;

        if (!$asalSebelumnya) {
            // Belum ada asal bokar → set langsung sesuai jenis baru
            $asalBaru = $jenis;
        } else {
            // Kalau beda jenis dengan sebelumnya, jadikan CMP
            if ($asalSebelumnya !== $jenis && $asalSebelumnya !== 'CMP') {
                $asalBaru = 'CMP';
            } else {
                $asalBaru = $asalSebelumnya;
            }
        }

        // Update asal bokar di maturasi
        $maturasi->update(['asal_bokar' => $asalBaru]);

        // Catat juga masuk_hi ke tabel pengolahan maturasi
        \App\Models\PengolahanMaturasi::create([
            'maturasi_id' => $maturasi->id,
            'tgl_laporan' => $data['tanggal'],
            'masuk_hi' => $netto_basah,
            'diolah' => 0,
            'mutasi' => 0,
            'keterangan' => 'Auto-import dari Pengolahan Basah (' . $jenis . ')',
        ]);
    }

    return redirect()->route('pengolahan_basah.index')->with('success', 'Data pengolahan basah berhasil ditambahkan dan diperbarui di Maturasi.');
}


    public function show($id)
    {
        $data = PengolahanBasah::find($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = PengolahanBasah::find($id);
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        // Hapus K3 dari validasi
         $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'bak_maturasi' => 'required|string',
           'jenis' => 'required|string|in:PT,DS',
            'berat_truck' => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:'.$request->input('berat_truck', 0),
        ], [
            'berat_timbang.min' => 'Berat Timbang harus lebih besar dari Berat Truck.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = $validator->validated();
        
        $netto_basah = $data['berat_timbang'] - $data['berat_truck'];

        $pengolahan = PengolahanBasah::find($id);
        // Update, tapi JANGAN sentuh K3 dan Netto Kering
        $pengolahan->update(array_merge($data, [
            'netto_basah' => $netto_basah,
        ]));

        return redirect()->route('pengolahan_basah.index')->with('success', 'Data pengolahan basah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $pengolahan = PengolahanBasah::find($id);
        $pengolahan->delete();
        return redirect()->route('pengolahan_basah.index')->with('success', 'Data berhasil dihapus.');
    }
}