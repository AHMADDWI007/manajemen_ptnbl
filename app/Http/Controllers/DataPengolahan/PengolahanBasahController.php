<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\TransaksiApiBokar;
use App\Models\RektifikasiStok;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengolahanBasahController extends Controller
{
    public function index(Request $request)
    {
        // 🔥 [PERBAIKAN] Menggunakan relasi 'maturasi' yang sudah didefinisikan di Model
        $data_pengolahan = PengolahanBasah::with('maturasi')->orderBy('tanggal', 'desc')->get();

        $selected_date_str = $request->query('tanggal');
        $today = $selected_date_str ? Carbon::parse($selected_date_str) : Carbon::today();

        // Init Summary
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

            $summary_data['stok_awal']          = $all_totals['stok_awal'];
            $summary_data['masuk_hi']           = $all_totals['masuk_hi'];
            $summary_data['masuk_sdhi']         = $all_totals['penerimaan_sid_hi'];
            $summary_data['diolah_hi']          = $all_totals['diolah_hi'];
            $summary_data['diolah_sdhi']        = $all_totals['diolah_sdhi'];
            $summary_data['stok_akhir']         = $all_totals['stok_akhir'];
            $summary_data['jumlah_stock_bokar'] = $all_totals['jumlah_stock_bokar'];

        } catch (Exception $e) { }

        $total_data = [
            'total_pt_netto_kering'    => 0,
            'total_ds_netto_kering'    => 0,
            'total_inhut_netto_kering' => 0,
            'jumlah_netto_kering'      => 0,
        ];

        return view('DataPengolahan.pengolahan-basah', compact('data_pengolahan', 'summary_data', 'total_data', 'today'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            // 🔥 [PERBAIKAN] Validasi FK ke tabel 'maturasi' kolom 'id_maturasi'
            'id_maturasi'   => 'required|exists:maturasi,id_maturasi', 
            'jenis'         => 'required|string|in:PT,DS,INHUT',
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:' . $request->input('berat_truck', 0),
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        $data = $validator->validated();
        $netto_basah = $data['berat_timbang'] - $data['berat_truck'];

        // 🔥 [PERBAIKAN] Pastikan field foreign key pakai 'id_maturasi' (sesuai $fillable/guarded model)
        PengolahanBasah::create(array_merge($data, [
            'netto_basah'  => $netto_basah,
            'k3'           => null,
            'netto_kering' => null,
        ]));

        $this->updateMaturasiTrigger($data['id_maturasi'], $data['jenis'], $data['tanggal']);

        return redirect()->route('pengolahan-basah.index')->with('success', 'Data produksi berhasil ditambahkan.');
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

        $netto_basah = $request->berat_timbang - $request->berat_truck;
        
        // Pastikan request mengirim 'id_maturasi' jika diedit
        $pengolahan->update(array_merge($request->all(), ['netto_basah' => $netto_basah]));
        
        return redirect()->route('pengolahan-basah.index')->with('success', 'Data diperbarui.');
    }

    public function destroy($id) 
    {
        $pengolahan = PengolahanBasah::find($id);
        if($pengolahan) $pengolahan->delete();
        
        return redirect()->route('pengolahan-basah.index')->with('success', 'Data dihapus.');
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

    private function updateMaturasiTrigger($maturasiId, $jenis, $tanggal)
    {
        // 🔥 [PERBAIKAN] find($maturasiId) mencari di kolom id_maturasi
        $maturasi = Maturasi::find($maturasiId);
        
        if ($maturasi) {
            $asalBaru = strtoupper($jenis);
            if ($maturasi->asal_bokar && $maturasi->asal_bokar !== $asalBaru && $maturasi->asal_bokar !== 'CMP') {
                $asalBaru = 'CMP';
            }
            $maturasi->update(['asal_bokar' => $asalBaru]);
            
            // 🔥 [PERBAIKAN] FK 'maturasi_id' jadi 'id_maturasi' di PengolahanMaturasi
            PengolahanMaturasi::firstOrCreate(
                ['id_maturasi' => $maturasiId, 'tgl_laporan' => $tanggal],
                ['masuk_hi' => 0, 'diolah' => 0, 'mutasi' => 0, 'keterangan' => 'Fisik Bokar Masuk']
            );
        }
    }
}