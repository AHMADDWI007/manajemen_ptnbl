<?php

namespace App\Http\Controllers;

use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\TransaksiApiBokar; // Model API
use App\Models\RektifikasiStok;   // Model Rektif Baru
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengolahanBasahController extends Controller
{
    // ==========================================================
    // FUNGSI UTAMA (INDEX, STORE, SHOW, EDIT, UPDATE, DESTROY)
    // ==========================================================

    public function index(Request $request)
    {
        // QUERY LEBIH BERSIH: Data murni produksi, tanpa filter aneh-aneh
        $data_pengolahan = PengolahanBasah::orderBy('tanggal', 'desc')->get();

        $selected_date_str = $request->query('tanggal');
        try {
            $today = $selected_date_str ? Carbon::parse($selected_date_str) : Carbon::today();
        } catch (Exception $e) {
            $today = Carbon::today();
        }

        // Inisialisasi Default Summary
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
            // Hitung total menggunakan data dari 3 Tabel berbeda (Lokal)
            $result = $this->calculateAllRecapTotals($today);
            $all_totals = $result['total'];

            $summary_data['stok_awal']          = $all_totals['stok_awal'];
            $summary_data['masuk_hi']           = $all_totals['masuk_hi'];
            $summary_data['masuk_sdhi']         = $all_totals['penerimaan_sid_hi'];
            $summary_data['diolah_hi']          = $all_totals['diolah_hi'];
            $summary_data['diolah_sdhi']        = $all_totals['diolah_sdhi'];
            $summary_data['stok_akhir']         = $all_totals['stok_akhir'];
            $summary_data['jumlah_stock_bokar'] = $all_totals['jumlah_stock_bokar'];

        } catch (Exception $e) {
            // Silent error
        }

        // total_data diisi 0 (karena dihitung JS di frontend untuk DataTables)
        $total_data = [
            'total_pt_netto_kering'    => 0,
            'total_ds_netto_kering'    => 0,
            'total_inhut_netto_kering' => 0,
            'jumlah_netto_kering'      => 0,
        ];

        return view('Pengolahan.pengolahan_basah', compact(
            'data_pengolahan',
            'summary_data',
            'total_data',
            'today'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'bak_maturasi'  => 'required|string',
            'jenis'         => 'required|string|in:PT,DS,INHUT',
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:' . $request->input('berat_truck', 0),
        ], [
            'berat_timbang.min' => 'Berat Timbang harus lebih besar dari Berat Truck.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $netto_basah = $data['berat_timbang'] - $data['berat_truck'];

        // Create Data Pengolahan Basah (Murni Produksi)
        PengolahanBasah::create(array_merge($data, [
            'netto_basah'  => $netto_basah,
            'k3'           => null,
            'netto_kering' => null,
            'rektif'       => 0, // Pastikan 0, karena rektif punya tabel sendiri
        ]));

        // Update Maturasi (Logika tetap sama)
        $bak = $data['bak_maturasi'];
        $jenis = strtoupper($data['jenis']);
        $maturasi = Maturasi::where('uraian', 'LIKE', "%$bak%")->first();

        if ($maturasi) {
            $asalSebelumnya = $maturasi->asal_bokar;
            
            if (!$asalSebelumnya) {
                $asalBaru = $jenis;
            } else {
                if ($asalSebelumnya !== $jenis && $asalSebelumnya !== 'CMP') {
                    $asalBaru = 'CMP';
                } else {
                    $asalBaru = $asalSebelumnya;
                }
            }
            
            $maturasi->update(['asal_bokar' => $asalBaru]);
            
            PengolahanMaturasi::create([
                'maturasi_id' => $maturasi->id,
                'tgl_laporan' => $data['tanggal'],
                'masuk_hi'    => $netto_basah,
                'diolah'      => 0,
                'mutasi'      => 0,
                'keterangan'  => 'Auto-import dari Pengolahan Basah (' . $jenis . ')',
            ]);
        }

        return redirect()->route('pengolahan_basah.index')
            ->with('success', 'Data produksi berhasil ditambahkan.');
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
        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'bak_maturasi'  => 'required|string',
            'jenis'         => 'required|string|in:PT,DS,INHUT',
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:' . $request->input('berat_truck', 0),
        ], [
            'berat_timbang.min' => 'Berat Timbang harus lebih besar dari Berat Truck.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $data = $validator->validated();
        $netto_basah = $data['berat_timbang'] - $data['berat_truck'];

        $pengolahan = PengolahanBasah::find($id);
        $pengolahan->update(array_merge($data, [
            'netto_basah' => $netto_basah,
        ]));

        return redirect()->route('pengolahan_basah.index')
            ->with('success', 'Data produksi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $pengolahan = PengolahanBasah::find($id);
        $pengolahan->delete();
        return redirect()->route('pengolahan_basah.index')
            ->with('success', 'Data produksi berhasil dihapus.');
    }

    // =====================================================
    // FUNGSI REKTIF (MENGGUNAKAN TABEL BARU)
    // =====================================================

    public function updateRektif(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis'   => 'required|string|in:PT,DS,INHUT',
            'tanggal' => 'required|date',
            'rektif'  => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        try {
            // SIMPAN KE TABEL 'rektifikasi_stok'
            RektifikasiStok::updateOrCreate(
                [
                    'tanggal' => $data['tanggal'],
                    'jenis'   => $data['jenis'],
                ],
                [
                    'berat'      => $data['rektif'],
                    'keterangan' => 'Input via Modal Rektif',
                ]
            );

            return response()->json(['success' => true, 'message' => 'Rektif berhasil diperbarui.']);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui database.'], 500);
        }
    }

    // =====================================================
    // FUNGSI REKAP (GABUNGAN 3 TABEL)
    // =====================================================

    public function rekap(Request $request)
    {
        try {
            $selected_date_str = $request->query('tanggal');
            $today = $selected_date_str ? Carbon::parse($selected_date_str) : Carbon::today();

            $result = $this->calculateAllRecapTotals($today, true);

            return response()->json([
                'bulan'        => $today->translatedFormat('F Y'),
                'hari_tanggal' => $today->translatedFormat('l, d F Y'),
                'data'         => $result['data'],
                'total'        => $result['total']
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

            // Ambil Rektif Hari Ini dari TABEL BARU
            $rektifRecords = RektifikasiStok::where('tanggal', $currentDate->toDateString())
                ->get()
                ->keyBy(function ($item) {
                    return strtoupper($item->jenis);
                });

            foreach ($map as $item) {
                $jenis = $item['jenis_db'];
                $uraian = $item['uraian'];
                $kode_api = $item['kode_api'];

                $rektif_today = $rektifRecords->has($jenis) ? (float)$rektifRecords[$jenis]->berat : 0.00;

                // 1. Hitung Stok Awal dari DB Lokal (Gabungan 3 Tabel)
                $stok_awal = $this->calculateOptimizedNetStokAkhirT1($jenis, $currentDate, $kode_api);

                // 2. Ambil Bokar Masuk Hari Ini dari DB Lokal (Tabel API)
                $api_data_today = $this->getBokarMasukFromDB($kode_api, $currentDate);
                $masuk_hi = $api_data_today['masuk_hi'];
                $penerimaan_sd_kemarin = $api_data_today['masuk_sd_kemarin'];

                // Perhitungan Turunan
                $penerimaan_sid_hi = $penerimaan_sd_kemarin + $masuk_hi;
                $jumlah_stock_bokar = $stok_awal + $masuk_hi;

                // 3. Diolah Hari Ini (Tabel Produksi - Murni)
                $diolah_hi = PengolahanBasah::where('jenis', $jenis)
                    ->whereDate('tanggal', $currentDate)
                    ->sum('netto_basah');

                // 4. Diolah S/D Hari Ini (Tabel Produksi - Murni)
                $diolah_sdhi = PengolahanBasah::where('jenis', $jenis)
                    ->whereDate('tanggal', '<=', $currentDate)
                    ->sum('netto_basah');

                // Rumus Stok Akhir
                $stok_akhir = $stok_awal + $masuk_hi - $diolah_hi + $rektif_today;

                if ($getDetails) {
                    $data[] = [
                        'uraian'              => $uraian,
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

                // Akumulasi Total
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
    
    // =====================================================
    // LOGIKA INTI: HITUNG STOK HISTORIS DARI 3 TABEL
    // =====================================================
    
    private function calculateOptimizedNetStokAkhirT1($jenis, $currentDate, $kodeApi)
    {
        // Stok Awal Hari Ini = Stok Akhir Kemarin
        $yesterday = $currentDate->copy()->subDay();

        // A. TOTAL MASUK (Tabel API)
        $total_masuk = TransaksiApiBokar::where('kode_api', $kodeApi)
            ->where('tanggal', '<=', $yesterday->format('Y-m-d'))
            ->sum('masuk_hi');

        // B. TOTAL DIOLAH (Tabel Produksi - Murni)
        $total_diolah = PengolahanBasah::where('jenis', $jenis)
            ->where('tanggal', '<=', $yesterday->format('Y-m-d'))
            ->sum('netto_basah');

        // C. TOTAL REKTIF (Tabel Rektif - Baru)
        $total_rektif = RektifikasiStok::where('jenis', $jenis)
            ->where('tanggal', '<=', $yesterday->format('Y-m-d'))
            ->sum('berat');

        // Rumus Stok: Masuk - Diolah + Rektif
        $stok = $total_masuk - $total_diolah + $total_rektif;

        return max(0, $stok);
    }

    private function getBokarMasukFromDB($kode, $tanggal)
    {
        // Ambil data dari Tabel API
        $data = TransaksiApiBokar::where('kode_api', $kode)
            ->where('tanggal', $tanggal->format('Y-m-d'))
            ->first();

        if ($data) {
            return [
                'masuk_hi' => (float) $data->masuk_hi,
                'masuk_sd_kemarin' => (float) $data->masuk_sd_kemarin
            ];
        }

        return [
            'masuk_hi' => 0,
            'masuk_sd_kemarin' => 0
        ];
    }
}