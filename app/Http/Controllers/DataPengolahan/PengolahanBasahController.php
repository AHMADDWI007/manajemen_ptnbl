<?php

namespace App\Http\Controllers\DataPengolahan;

use Exception;
use Carbon\Carbon;
use App\Models\Maturasi;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah;
use App\Models\RektifikasiStok;
use App\Models\TransaksiApiBokar;
use App\Models\PengolahanMaturasi;
use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabBokarDiolah;
use Illuminate\Support\Facades\Validator;

class PengolahanBasahController extends Controller
{
    public function index(Request $request)
    {
        // 1. QUERY DATA UTAMA
        // Ambil semua data, urutkan dari yang terbaru
        $query = PengolahanBasah::with('maturasi')
                    ->orderBy('tanggal', 'desc')
                    ->orderBy('created_at', 'desc');

        // Filter Tanggal (Jika User Memilih Tanggal)
        if ($request->has('tanggal') && $request->tanggal != '') {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $raw_data = $query->get();

        // 2. 🔥 LOGIKA GROUPING FINAL (PENGGABUNGAN TAMPILAN)
        // Kita kelompokkan berdasarkan: TANGGAL + ID BAK + JAM & MENIT
        // Format 'YmdHi' (Tanpa detik 's') menjamin data pecahan yang selisih detik tetap menyatu.
        $data_pengolahan = $raw_data->groupBy(function($item) {
            return $item->tanggal . '-' . $item->id_maturasi . '-' . $item->created_at->format('YmdHi');
        });

        // 3. LOGIKA SUMMARY STOK (CARD ATAS)
        $selected_date_str = $request->query('tanggal');
        $today = $selected_date_str ? Carbon::parse($selected_date_str) : Carbon::today();

        // Inisialisasi default array
        $summary_data = [
            'stok_awal'          => 0,
            'masuk_hi'           => 0,
            'masuk_sdhi'         => 0,
            'jumlah_stock_bokar' => 0,
            'diolah_hi'          => 0,
            'diolah_sdhi'        => 0,
            'stok_akhir'         => 0,
        ];

        try {
            $result = $this->calculateAllRecapTotals($today);
            $all_totals = $result['total'];

            // Merge data summary
            $summary_data = array_merge($summary_data, $all_totals);

        } catch (Exception $e) {
            // Silent fail agar halaman tetap loading walau hitungan error
        }

        // Variabel dummy untuk total footer (karena dihitung JS)
        $total_data = [];

        // 4. RETURN KE VIEW
        return view('DataPengolahan.pengolahan-basah', compact('data_pengolahan', 'summary_data', 'total_data', 'today'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'id_maturasi'   => 'required|exists:maturasi,id_maturasi',
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|gte:berat_truck',
            'split_pt'      => 'nullable|numeric|min:0',
            'split_ds'      => 'nullable|numeric|min:0',
            'split_inhut'   => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        // 2. Hitung Data Utama
        $berat_truck_total = (float) $request->berat_truck;
        $berat_timbang_total = (float) $request->berat_timbang;
        $netto_total_real = $berat_timbang_total - $berat_truck_total;

        // 3. Ambil Input Pecahan
        $pt_netto = (float) $request->input('split_pt', 0);
        $ds_netto = (float) $request->input('split_ds', 0);
        $inhut_netto = (float) $request->input('split_inhut', 0);
        $total_split = $pt_netto + $ds_netto + $inhut_netto;

        try {
            // === SKENARIO 1: INPUT UTUH (GELONDONGAN) ===
            // Jika user membiarkan kolom pecahan kosong (0)
            if ($total_split == 0) {
                // Simpan sebagai 1 baris utuh
                // Kita defaultkan ke 'DS' (atau jenis lain sesuai kesepakatan) sebagai penampung sementara
                PengolahanBasah::create([
                    'tanggal'       => $request->tanggal,
                    'id_maturasi'   => $request->id_maturasi,
                    'jenis'         => 'PENDING', // Default sementara, nanti di-Pecah oleh Admin
                    'berat_truck'   => $berat_truck_total,
                    'berat_timbang' => $berat_timbang_total,
                    'netto_basah'   => $netto_total_real,
                    'k3'            => null,
                    'netto_kering'  => null,
                ]);

                // Update Trigger (Label DS akan masuk ke master bak)
                $this->updateMaturasiTrigger($request->id_maturasi, 'DS', $request->tanggal);

                return redirect()->route('pengolahan-basah.index')->with('success', 'Data utuh berhasil disimpan (Belum dipecah).');
            }

            // === SKENARIO 2: INPUT LANGSUNG PECAH ===
            // Jika user mengisi kolom pecahan
            else {
                // Validasi: Total pecahan harus sama dengan Netto (Toleransi 0.1)
                if (abs($total_split - $netto_total_real) > 0.1) {
                    return redirect()->back()->withErrors(['msg' => 'Total rincian (' . $total_split . ') tidak sama dengan Netto (' . $netto_total_real . '). Jika ingin simpan utuh, kosongkan semua kolom rincian.'])->withInput();
                }

                $splits = [
                    'PT' => $pt_netto,
                    'DS' => $ds_netto,
                    'INHUT' => $inhut_netto
                ];

                foreach ($splits as $jenis => $netto_bagian) {
                    if ($netto_bagian > 0) {
                        // Hitung Proporsional Berat Truk & Timbang
                        $persentase = $netto_bagian / $netto_total_real;
                        $proporsi_truck = $berat_truck_total * $persentase;
                        $proporsi_timbang = $berat_timbang_total * $persentase;

                        PengolahanBasah::create([
                            'tanggal'       => $request->tanggal,
                            'id_maturasi'   => $request->id_maturasi,
                            'jenis'         => $jenis,
                            'berat_truck'   => $proporsi_truck,
                            'berat_timbang' => $proporsi_timbang,
                            'netto_basah'   => $netto_bagian,
                            'k3'            => null,
                            'netto_kering'  => null,
                        ]);

                        $this->updateMaturasiTrigger($request->id_maturasi, $jenis, $request->tanggal);
                    }
                }
                
                return redirect()->route('pengolahan-basah.index')->with('success', 'Data berhasil disimpan dan dipecah otomatis.');
            }

        } catch (Exception $e) {
            return redirect()->back()->withErrors(['msg' => 'Error: ' . $e->getMessage()])->withInput();
        }
    }

    // Method baru untuk menangani proses Pecah Data
    public function pecahStore(Request $request)
    {
        // 1. Cari Data Asal
        $dataAsal = PengolahanBasah::find($request->id_asal);
        if (!$dataAsal) {
            return redirect()->back()->with('error', 'Data asal tidak ditemukan.');
        }

        // CEK PENTING: Pastikan data asal sudah punya K3 & Netto Kering
        // Karena kita mau mecah berdasarkan Kering, kalau Keringnya 0, error kan.
        if ($dataAsal->netto_kering <= 0 || empty($dataAsal->k3)) {
            return redirect()->back()->with('error', 'Data ini belum memiliki Nilai Netto Kering / Hasil Lab (K3). Tidak bisa dipecah.');
        }

        // 2. Ambil Inputan Pecahan (INI ADALAH NILAI KERING)
        $pt_kering    = (float) $request->split_pt;
        $ds_kering    = (float) $request->split_ds;
        $inhut_kering = (float) $request->split_inhut;
        
        $totalInputKering = $pt_kering + $ds_kering + $inhut_kering;

        // 3. Validasi: Total Input harus sama dengan Netto Kering Asal (Dibulatkan ke atas biar klop sama View)
        // Kita pakai ceil() di sini karena di View tombolnya pakai ceil()
        $targetKering = ceil($dataAsal->netto_kering); 

        if (abs($totalInputKering - $targetKering) > 0.1) {
            return redirect()->back()->withErrors(['msg' => 'Total pecahan (' . number_format($totalInputKering) . ') tidak sama dengan Netto Kering asal (' . number_format($targetKering) . ').'])->withInput();
        }

        try {
            // Array Data (Nilai Kering)
            $splits = [
                'PT'    => $pt_kering, 
                'DS'    => $ds_kering, 
                'INHUT' => $inhut_kering
            ];

            // Ambil K3 Asal (Persentase) untuk hitung mundur
            $k3_persen = $dataAsal->k3; 
            
            foreach ($splits as $jenis => $nilaiKering) {
                if ($nilaiKering > 0) {
                    
                    // --- A. HITUNG MUNDUR NETTO BASAH ---
                    // Rumus: Basah = Kering / (K3 / 100)
                    // Contoh: Kering 500, K3 50% -> Basah = 500 / 0.5 = 1000
                    $nilaiBasah = $nilaiKering / ($k3_persen / 100);

                    // --- B. LOGIKA PROPORSIONAL (BERAT TRUK & TIMBANG) ---
                    // Kita pakai rasio dari Netto Kering
                    // Rumus: (Nilai Kering Bagian / Total Kering Asal)
                    $ratio = $nilaiKering / $dataAsal->netto_kering;

                    $beratTruckBaru   = $dataAsal->berat_truck * $ratio;
                    $beratTimbangBaru = $dataAsal->berat_timbang * $ratio;

                    // --- C. SIMPAN DATA BARU ---
                    PengolahanBasah::create([
                        'tanggal'       => $dataAsal->tanggal,
                        'id_maturasi'   => $dataAsal->id_maturasi,
                        'jenis'         => $jenis, 
                        
                        // Data Timbangan (Proporsional)
                        'berat_truck'   => $beratTruckBaru,
                        'berat_timbang' => $beratTimbangBaru,
                        
                        // Data Hasil Hitungan
                        'netto_basah'   => $nilaiBasah,   // Hasil hitung mundur
                        'k3'            => $k3_persen,    // COPY K3 DARI INDUK (JANGAN DI-NULL)
                        'netto_kering'  => $nilaiKering,  // Inputan Admin
                    ]);
                    
                    // Update Trigger Maturasi
                    $this->updateMaturasiTrigger($dataAsal->id_maturasi, $jenis, $dataAsal->tanggal);
                }
            }

            // 4. Hapus Data Lama
            $dataAsal->delete();

            return redirect()->back()->with('success', 'Data berhasil dipecah berdasarkan Netto Kering.');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memecah data: ' . $e->getMessage());
        }
    }

    public function show($id) 
    { 
        // 🔥 [PERBAIKAN] find($id) otomatis cari di PK model (id_pengolahan_basah)
        return response()->json(PengolahanBasah::with('maturasi')->find($id)); 
    }

    public function edit($id) 
    { 
        return response()->json(PengolahanBasah::with('maturasi')->find($id)); 
    }
    
    public function update(Request $request, $id) 
    {
        $pengolahan = PengolahanBasah::find($id);
        
        if(!$pengolahan) return redirect()->back()->with('error', 'Data tidak ditemukan');

        // Validasi input agar aman (terutama id_maturasi dan jenis)
        $request->validate([
            'id_maturasi' => 'required|exists:maturasi,id_maturasi',
            'jenis'       => 'required|string|in:PT,DS,INHUT',
        ]);

        $netto_basah = $request->berat_timbang - $request->berat_truck;
        
        // 1. UPDATE DATA UTAMA (PENGOLAHAN BASAH)
        $pengolahan->update(array_merge($request->all(), ['netto_basah' => $netto_basah]));
        
        // 2. 🔥 [PERBAIKAN PENTING] SINKRONISASI KE TABEL LAB (Jika Sudah Ada)
        // Jika jenis diubah di sini, maka di tabel hasil uji juga harus berubah
        // agar logika 'getDetailedAsalBokarString' di MaturasiController nanti tidak bingung.
        HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $id)
            ->update([
                'jenis'       => $request->jenis,
                'id_maturasi' => $request->id_maturasi, // Update juga jika pindah bak
                'netto_basah' => $netto_basah
                // Netto kering & K3 biarkan tetap (atau hitung ulang jika perlu)
            ]);

        // 3. 🔥 [PERBAIKAN UTAMA] TRIGGER UPDATE STATUS BAK MATURASI
        // Panggil fungsi trigger agar kolom 'asal_bokar' di tabel Maturasi berubah
        $this->updateMaturasiTrigger($request->id_maturasi, $request->jenis, $request->tanggal);
        
        return redirect()->route('pengolahan-basah.index')->with('success', 'Data diperbarui dan status Bak disesuaikan.');
    }

    public function destroy($id) 
    {
        $pengolahan = PengolahanBasah::find($id);
        if($pengolahan) $pengolahan->delete();
        
        return redirect()->route('pengolahan-basah.index')->with('success', 'Data dihapus.');
    }

    // Hapus Banyak Data Sekaligus (Group)
    public function destroyGroup(Request $request)
    {
        $ids = json_decode($request->group_ids, true); // Terima array ID dari view
        
        if (!empty($ids) && is_array($ids)) {
            // Ambil sample data untuk trigger (ambil data pertama sebelum dihapus)
            $sample = PengolahanBasah::find($ids[0]);
            
            if ($sample) {
                $maturasiId = $sample->id_maturasi;
                $tanggal = $sample->tanggal;

                // Hapus Semua Data berdasarkan ID array
                PengolahanBasah::whereIn('id_pengolahan_basah', $ids)->delete();

                // 🔥 PENTING: Jalankan Trigger untuk update Status Bak
                // Kita kirim jenisBaru = '' (kosong) karena kita sedang menghapus, bukan menambah.
                // Trigger akan otomatis scan ulang sisa data di database.
                $this->updateMaturasiTrigger($maturasiId, '', $tanggal);
            }
        }

        return redirect()->back()->with('success', 'Seluruh data pecahan dalam grup berhasil dihapus.');
    }

    public function updateRektif(Request $request) 
    {
        RektifikasiStok::updateOrCreate(
            ['tanggal' => $request->tanggal, 'jenis' => $request->jenis],
            ['berat' => $request->rektif, 'keterangan' => 'Input via Modal Rektif']
        );
        return response()->json(['success' => true]);
    }

    public function rekap(Request $request)
    {
        $selected_date_str = $request->query('tanggal');
        $today = $selected_date_str ? Carbon::parse($selected_date_str) : Carbon::today();
        $result = $this->calculateAllRecapTotals($today, true);

        return response()->json([
            'bulan'        => $today->translatedFormat('F Y'),
            'hari_tanggal' => $today->translatedFormat('l, d F Y'),
            'data'         => $result['data'],
            'total'        => $result['total']
        ]);
    }

    private function calculateAllRecapTotals(Carbon $currentDate, $getDetails = false)
    {
        try {
            $map = [
                ['uraian' => 'Pembelian Bokar Rakyat / Petani', 'jenis_db' => 'DS', 'kode_api' => 'petani'],
                ['uraian' => 'Pembelian Bokar PT.PN Kebun Batulicin', 'jenis_db' => 'PT', 'kode_api' => 'ptpn'],
                ['uraian' => 'Pembelian Bokar PT. INHUTANI 1', 'jenis_db' => 'INHUT', 'kode_api' => 'inhut']
            ];

            $data = [];
            $total = [
                'stok_awal'             => 0,
                'penerimaan_sd_kemarin' => 0,
                'masuk_hi'              => 0,
                'penerimaan_sid_hi'     => 0,
                'jumlah_stock_bokar'    => 0,
                'diolah_hi'             => 0,
                'diolah_sdhi'           => 0,
                'stok_akhir'            => 0,
            ];

            $rektifRecords = RektifikasiStok::where('tanggal', $currentDate->toDateString())->get()->keyBy('jenis');

            foreach ($map as $item) {
                $jenis = $item['jenis_db'];
                $kode_api = $item['kode_api'];

                $rektif_today = $rektifRecords->has($jenis) ? (float)$rektifRecords[$jenis]->berat : 0.00;

                $stok_awal = $this->calculateOptimizedNetStokAkhirT1($jenis, $currentDate, $kode_api);

                $masuk_hi = (float) TransaksiApiBokar::where('kode_api', $kode_api)
                    ->where('tanggal', $currentDate->format('Y-m-d'))
                    ->value('masuk_hi');

                $startOfMonth = $currentDate->copy()->startOfMonth();
                $yesterday = $currentDate->copy()->subDay();
                
                $penerimaan_sd_kemarin = 0;
                if ($yesterday->gte($startOfMonth)) {
                    $penerimaan_sd_kemarin = TransaksiApiBokar::where('kode_api', $kode_api)
                        ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $yesterday->format('Y-m-d')])
                        ->sum('masuk_hi');
                }

                $penerimaan_sid_hi  = $penerimaan_sd_kemarin + $masuk_hi;
                $jumlah_stock_bokar = $stok_awal + $masuk_hi;

                $diolah_hi = PengolahanBasah::where('jenis', $jenis)
                    ->whereDate('tanggal', $currentDate)
                    ->sum('netto_kering');

                $diolah_sdhi = PengolahanBasah::where('jenis', $jenis)
                    ->whereDate('tanggal', '<=', $currentDate)
                    ->whereDate('tanggal', '>=', $startOfMonth)
                    ->sum('netto_kering');

                $stok_akhir = $stok_awal + $masuk_hi - $diolah_hi + $rektif_today;

                if ($getDetails) {
                    $data[] = [
                        'uraian'              => $item['uraian'],
                        'stok_awal'           => $stok_awal,
                        'penerimaan_sd_kemarin' => $penerimaan_sd_kemarin,
                        'masuk_hi'            => $masuk_hi,
                        'penerimaan_sid_hi'   => $penerimaan_sid_hi,
                        'jumlah_stock_bokar'  => $jumlah_stock_bokar,
                        'diolah_hi'           => $diolah_hi,
                        'diolah_sdhi'         => $diolah_sdhi,
                        'rektif'              => $rektif_today,
                        'stok_akhir'          => $stok_akhir,
                        'keterangan'          => $rektif_today != 0 ? 'Rektifikasi: ' . number_format($rektif_today, 0) : '-',
                        'jenis_db'            => $jenis
                    ];
                }

                $total['stok_awal'] += $stok_awal;
                $total['penerimaan_sd_kemarin'] += $penerimaan_sd_kemarin;
                $total['masuk_hi'] += $masuk_hi;
                $total['penerimaan_sid_hi'] += $penerimaan_sid_hi;
                $total['jumlah_stock_bokar'] += $jumlah_stock_bokar;
                $total['diolah_hi'] += $diolah_hi;
                $total['diolah_sdhi'] += $diolah_sdhi;
                $total['stok_akhir'] += $stok_akhir;
            }

            return ['total' => $total, 'data' => $data];
        } catch (Exception $e) {
            return ['total' => $total, 'data' => []];
        }
    }

    private function calculateOptimizedNetStokAkhirT1($jenis, $currentDate, $kodeApi)
    {
        $yesterday = $currentDate->copy()->subDay();

        $total_masuk = TransaksiApiBokar::where('kode_api', $kodeApi)
            ->where('tanggal', '<=', $yesterday->format('Y-m-d'))
            ->sum('masuk_hi');

        $total_diolah = PengolahanBasah::where('jenis', $jenis)
            ->where('tanggal', '<=', $yesterday->format('Y-m-d'))
            ->sum('netto_kering');

        $total_rektif = RektifikasiStok::where('jenis', $jenis)
            ->where('tanggal', '<=', $yesterday->format('Y-m-d'))
            ->sum('berat');

        $stok = $total_masuk - $total_diolah + $total_rektif;
        return max(0, $stok);
    }

    // Function Helper untuk Update Status Bak Maturasi Otomatis (REVISI: Target kolom asal_bokar)
    public function updateMaturasiTrigger($id_maturasi, $jenisBaru, $tanggal)
    {
        // 1. Ambil SEMUA jenis bokar unik di Bak & Tanggal tersebut
        $listJenis = PengolahanBasah::where('id_maturasi', $id_maturasi)
                        ->where('tanggal', $tanggal)
                        ->pluck('jenis')
                        // 🔥 PERBAIKAN DISINI: Tambahkan "&& $value !== 'PENDING'"
                        ->filter(function ($value) { 
                            return !is_null($value) && $value !== '' && $value !== 'PENDING'; 
                        })
                        ->unique()
                        ->sort() 
                        ->values();

        // 2. Tentukan Label Akhir
        $labelAkhir = '';

        if ($listJenis->count() > 1) {
            // Jika Multi Jenis -> CMP (DS, PT)
            $rincian = $listJenis->implode(', '); 
            $labelAkhir = "CMP ($rincian)";
        } 
        elseif ($listJenis->count() == 1) {
            // Jika cuma 1 jenis -> Tetap PT / DS / INHUT
            $labelAkhir = $listJenis->first();
        } 
        else {
            // Jika data kosong
            $labelAkhir = null; 
        }

        // 3. Update ke Tabel Master Maturasi
        $bak = Maturasi::find($id_maturasi);
        
        if ($bak) {
            // 🔥 PERBAIKAN DI SINI: Simpan ke kolom 'asal_bokar'
            $bak->asal_bokar = $labelAkhir; 
            $bak->save();
        }
    }
}