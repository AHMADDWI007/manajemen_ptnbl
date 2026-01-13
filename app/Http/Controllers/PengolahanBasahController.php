<?php

namespace App\Http\Controllers;

use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PengolahanBasahController extends Controller
{
    // ==========================================================
    // FUNGSI UTAMA (INDEX, STORE, SHOW, EDIT, UPDATE, DESTROY)
    // ==========================================================

    public function index(Request $request)
    {
        // Ambil SEMUA data, TIDAK termasuk baris REKTIF_DATA untuk tampilan tabel transaksi utama
        $data_pengolahan = PengolahanBasah::where('bak_maturasi', '!=', 'REKTIF_DATA')
            ->orderBy('tanggal', 'desc')
            ->get();

        $selected_date_str = $request->query('tanggal');
        try {
            $today = $selected_date_str ? Carbon::parse($selected_date_str) : Carbon::today();
        } catch (Exception $e) {
            $today = Carbon::today();
        }

        // --- INISIALISASI VARIABEL DEFAULT RINGKASAN STOK ---
        $stok_awal = 0;
        $bokar_masuk_hi = 0;
        $bokar_masuk_sdhi = 0;
        $bokar_diolah_hi = 0;
        $bokar_diolah_sdhi = 0;
        $stok_akhir = 0;
        $jumlah_stock_bokar = 0;

        try {
            // Memanggil fungsi rekap yang sudah dioptimalkan (untuk Ringkasan Stok atas)
            $result = $this->calculateAllRecapTotals($today);
            $all_totals = $result['total'];

            $stok_awal          = $all_totals['stok_awal'];
            $bokar_masuk_hi     = $all_totals['masuk_hi'];
            $bokar_masuk_sdhi   = $all_totals['penerimaan_sid_hi'];
            $bokar_diolah_hi    = $all_totals['diolah_hi'];
            $bokar_diolah_sdhi  = $all_totals['diolah_sdhi'];
            $stok_akhir         = $all_totals['stok_akhir'];
            $jumlah_stock_bokar = $all_totals['jumlah_stock_bokar'];

        } catch (Exception $e) {
            // Log::error("Error calculating summary: " . $e->getMessage());
        }

        $summary_data = [
            'stok_awal'          => $stok_awal,
            'masuk_hi'           => $bokar_masuk_hi,
            'masuk_sdhi'         => $bokar_masuk_sdhi,
            'jumlah_stock_bokar' => $jumlah_stock_bokar,
            'diolah_hi'          => $bokar_diolah_hi,
            'diolah_sdhi'        => $bokar_diolah_sdhi,
            'stok_akhir'         => $stok_akhir,
        ];

        // total_data diisi dengan nilai 0 (karena total dihitung di JS Front-end)
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

        // Create Data Pengolahan Basah
        PengolahanBasah::create(array_merge($data, [
            'netto_basah'  => $netto_basah,
            'k3'           => null,
            'netto_kering' => null,
            'rektif'       => 0, // Pastikan rektif diisi 0 untuk transaksi normal
        ]));

        // Asumsi logika update Maturasi (jika ada) tetap sama
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
            ->with('success', 'Data pengolahan basah berhasil ditambahkan dan diperbarui di Maturasi.');
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
            // Rektif tidak diubah di modal edit transaksi, diasumsikan 0
        ]));

        return redirect()->route('pengolahan_basah.index')
            ->with('success', 'Data pengolahan basah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $pengolahan = PengolahanBasah::find($id);
        $pengolahan->delete();
        return redirect()->route('pengolahan_basah.index')
            ->with('success', 'Data berhasil dihapus.');
    }

    // =====================================================
    // FUNGSI REKTIF
    // =====================================================

    public function updateRektif(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis'   => 'required|string|in:PT,DS,INHUT',
            'tanggal' => 'required|date',
            'rektif'  => 'required|numeric', // Rektif bisa positif atau negatif
        ], [
            'rektif.numeric' => 'Nilai Rektif harus berupa angka.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        try {
            // Logika KRITIS: updateOrCreate untuk baris khusus data rekap
            PengolahanBasah::updateOrCreate(
                [
                    'tanggal'      => $data['tanggal'],
                    'jenis'        => $data['jenis'],
                    'bak_maturasi' => 'REKTIF_DATA', // <-- FLAG KHUSUS
                ],
                [
                    'rektif'        => $data['rektif'],
                    // Set kolom transaksional ke 0 agar baris ini tidak mengganggu total
                    'berat_truck'   => 0,
                    'berat_timbang' => 0,
                    'netto_basah'   => 0,
                    'k3'            => 0,
                    'netto_kering'  => 0,
                ]
            );

            return response()->json(['success' => true, 'message' => 'Rektif berhasil diperbarui.']);

        } catch (Exception $e) {
            // Log::error("Error updating rektif: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui database.'], 500);
        }
    }

    // =====================================================
    // FUNGSI REKAP & HELPER (DIPERBAIKI UNTUK SINKRONISASI)
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

            // 1. Ambil nilai Rektif untuk tanggal hari ini dari DB (Perbaikan Case-Sensitive)
            $rektifRecords = PengolahanBasah::where('tanggal', $currentDate->toDateString())
                ->where('bak_maturasi', 'REKTIF_DATA')
                ->get()
                ->keyBy(function ($item) {
                    // ✅ Perbaikan Case-Sensitive pada kunci
                    return strtoupper($item->jenis);
                });

            foreach ($map as $item) {
                $jenis = $item['jenis_db'];
                $uraian = $item['uraian'];
                $kode_api = $item['kode_api'];

                $rektif_today = $rektifRecords->has($jenis) ? (float)$rektifRecords[$jenis]->rektif : 0.00;

                // 1. Hitung Stok Awal = Stok Akhir T-1 (Menggunakan Akumulasi Internal)
                $stok_awal = $this->calculateOptimizedNetStokAkhirT1($jenis, $currentDate, $kode_api);

                $api_data_today = $this->getBokarMasuk($kode_api, $currentDate);
                $masuk_hi = $api_data_today['masuk_hi'];
                $penerimaan_sd_kemarin = $api_data_today['masuk_sd_kemarin'];

                // 2. Logika perhitungan turunan (per baris)
                $penerimaan_sid_hi = $penerimaan_sd_kemarin + $masuk_hi;
                $jumlah_stock_bokar = $stok_awal + $masuk_hi;

                // Diolah Hari Ini (DB) - Exclude baris REKTIF_DATA
                $diolah_hi = PengolahanBasah::where('jenis', $jenis)
                    ->whereDate('tanggal', $currentDate)
                    ->where('bak_maturasi', '!=', 'REKTIF_DATA')
                    ->sum('netto_basah');

                // Diolah S/D HI - Exclude baris REKTIF_DATA
                $diolah_sdhi = PengolahanBasah::where('jenis', $jenis)
                    ->whereDate('tanggal', '<=', $currentDate)
                    ->where('bak_maturasi', '!=', 'REKTIF_DATA')
                    ->sum('netto_basah');

                // Stok Akhir = Stok Awal + Masuk HI - Diolah HI + Rektif HI
                $stok_akhir = $stok_awal + $masuk_hi - $diolah_hi + $rektif_today;

                if ($getDetails) {
                    $rowData = [
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
                        'keterangan'          => $rektif_today != 0 ? 'Rektifikasi sebesar ' . number_format($rektif_today, 0) : '-',
                        'jenis_db'            => $jenis
                    ];
                    $data[] = $rowData;
                }

                // 🛑 Akumulasi Total (Perbaikan Sinkronisasi)
                $total['stok_awal'] += $stok_awal;
                $total['penerimaan_sd_kemarin'] += $penerimaan_sd_kemarin;
                $total['masuk_hi'] += $masuk_hi;

                // Menggunakan perhitungan per baris yang sudah benar
                $total['penerimaan_sid_hi'] += $penerimaan_sid_hi;
                $total['jumlah_stock_bokar'] += $jumlah_stock_bokar;

                $total['diolah_hi'] += $diolah_hi;
                $total['diolah_sdhi'] += $diolah_sdhi;
                $total['stok_akhir'] += $stok_akhir;
            }

            return ['total' => $total, 'data' => $data];
        } catch (Exception $e) {
            // Log::error("Error calculating summary: " . $e->getMessage());
            return [
                'total' => [
                    'stok_awal'             => 0,
                    'penerimaan_sd_kemarin' => 0,
                    'masuk_hi'              => 0,
                    'penerimaan_sid_hi'     => 0,
                    'jumlah_stock_bokar'    => 0,
                    'diolah_hi'             => 0,
                    'diolah_sdhi'           => 0,
                    'stok_akhir'            => 0,
                ],
                'data' => []
            ];
        }
    }

    // =====================================================
    // FUNGSI UTAMA YANG DIPERBAIKI (calculateOptimizedNetStokAkhirT1)
    // =====================================================
    private function calculateOptimizedNetStokAkhirT1($jenis, $currentDate, $kodeApi)
    {
        $yesterday = $currentDate->copy()->subDay();
        $start_date = null;

        $earliest_entry_date_str = PengolahanBasah::where('jenis', $jenis)
            ->orderBy('tanggal', 'asc')
            ->value('tanggal');

        // KASUS A: Jika tidak ada data pengolahan di DB
        if (!$earliest_entry_date_str) {
            // ✅ Stok Awal hari ini = 0. Mengabaikan API Kumulatif yang tidak stabil.
            return 0;
        }

        $start_date = Carbon::parse($earliest_entry_date_str)->startOfDay();

        // KASUS B: Jika tanggal filter (T) adalah hari pertama entri DB atau lebih awal
        if ($currentDate->lte($start_date)) {
            // ✅ Stok Awal hari ini = 0. Mengabaikan API Kumulatif yang tidak stabil.
            return 0;
        }

        // --- Jika kode mencapai sini, $start_date sudah pasti terdefinisi (KASUS C) ---

        // 🚀 LANGKAH 1: OPTIMASI DATABASE (Data Diolah dan Rektif T-1)

        // a. Diolah (Exclude REKTIF_DATA)
        $diolah_data_cache = PengolahanBasah::where('jenis', $jenis)
            ->whereDate('tanggal', '>=', $start_date)
            ->whereDate('tanggal', '<=', $yesterday)
            ->where('bak_maturasi', '!=', 'REKTIF_DATA')
            ->selectRaw('tanggal, SUM(netto_basah) as total_diolah')
            ->groupBy('tanggal')
            ->pluck('total_diolah', 'tanggal')
            ->mapWithKeys(function ($item, $key) {
                return [Carbon::parse($key)->toDateString() => $item];
            })
            ->all();

        // b. Rektif (Hanya ambil REKTIF_DATA)
        $rektif_data_cache = PengolahanBasah::where('jenis', $jenis)
            ->whereDate('tanggal', '>=', $start_date)
            ->whereDate('tanggal', '<=', $yesterday)
            ->where('bak_maturasi', 'REKTIF_DATA')
            ->selectRaw('tanggal, rektif')
            ->pluck('rektif', 'tanggal')
            ->mapWithKeys(function ($item, $key) {
                return [Carbon::parse($key)->toDateString() => $item];
            })
            ->all();

        // --- Inisialisasi Saldo Awal Penuh (Baseline Historis) ---
        $current_stok = 0; // Mulai akumulasi Stok Awal dari NOL

        // 🚀 LANGKAH 2: Iterasi Saldo (Mengakumulasi Stok Akhir T-1)
        $period = CarbonPeriod::create($start_date, $yesterday);

        foreach ($period->toArray() as $date) {
            $date_str = $date->toDateString();

            // PANGGILAN API (untuk Masuk HI)
            $api_data_daily = $this->getBokarMasuk($kodeApi, $date);
            $masuk_hi_daily = $api_data_daily['masuk_hi']; // <-- HANYA AMBIL MASUK HI

            // Ambil Diolah dan Rektif dari CACHE
            $diolah_hi_daily = $diolah_data_cache[$date_str] ?? 0;
            $rektif_hi_daily = $rektif_data_cache[$date_str] ?? 0;

            // Saldo Kumulatif: Stok Akhir sebelumnya + Masuk HI - Diolah HI + Rektif HI
            $current_stok += $masuk_hi_daily;
            $current_stok -= $diolah_hi_daily;
            $current_stok += $rektif_hi_daily;
        }

        return max(0, $current_stok);
    }

    private function getBokarMasuk($kode, $tanggal)
    {
        try {
            $url = "https://bokar.ptnb.co.id/get_bokar.php?tgl=" . $tanggal->format('Y-m-d') . "&kode=" . $kode;
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                $bokar_api = $response->json();

                $masuk_hi = isset($bokar_api['masuk_hi']) ? (float) $bokar_api['masuk_hi'] : 0;
                $masuk_sd_kemarin = isset($bokar_api['masuk_sd_kemarin']) ? (float) $bokar_api['masuk_sd_kemarin'] : 0;

                return [
                    'masuk_hi' => $masuk_hi,
                    'masuk_sd_kemarin' => $masuk_sd_kemarin
                ];
            }
            return [
                'masuk_hi' => 0,
                'masuk_sd_kemarin' => 0
            ];
        } catch (Exception $e) {
            return [
                'masuk_hi' => 0,
                'masuk_sd_kemarin' => 0
            ];
        }
    }
}