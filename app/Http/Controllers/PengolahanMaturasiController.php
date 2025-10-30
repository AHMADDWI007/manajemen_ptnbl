<?php

namespace App\Http\Controllers;

use App\Models\PengolahanMaturasi;
use App\Models\PengolahanBasah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PengolahanMaturasiController extends Controller
{
    /**
     * Menampilkan data maturasi untuk tanggal tertentu (default hari ini).
     */
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        $previousDate = $selectedDate->copy()->subDay();

        $maturasiHariIni = PengolahanMaturasi::whereDate('created_at', $selectedDate)->get()->keyBy('uraian');
        $maturasiKemarin = PengolahanMaturasi::whereDate('created_at', $previousDate)->get()->keyBy('uraian');
        
        // --- LOGIKA PENGOLAHAN BASAH: GUNAKAN GROUPBY & SUM AGAR LEBIH AKURAT ---
        $pengolahanBasahHariIni_Tanggal = PengolahanBasah::whereDate('tanggal', $selectedDate)
                                            ->select('bak_maturasi', DB::raw('SUM(netto_kering) as total_netto_kering'))
                                            ->groupBy('bak_maturasi')
                                            ->get()
                                            ->keyBy('bak_maturasi');
                                            
        $pengolahanBasahHariIni_CreatedAt = PengolahanBasah::whereDate('created_at', $selectedDate)
                                            ->select('bak_maturasi', DB::raw('SUM(netto_kering) as total_netto_kering'))
                                            ->groupBy('bak_maturasi')
                                            ->get()
                                            ->keyBy('bak_maturasi');
        
        $pengolahanBasahHariIni = $pengolahanBasahHariIni_Tanggal->union($pengolahanBasahHariIni_CreatedAt);


        $dataTampilan = new Collection();

        for ($i = 1; $i <= 49; $i++) {
            $uraian = "Di Bak Maturasi " . $i;
            $bakName = "Bak Maturasi " . $i; 

            $dataBak = [
                'id' => null,
                'tanggal_input_view' => $selectedDate->format('d-m-Y'),
                'uraian' => $uraian,
                'stok_awal' => 0,
                'tgl_masuk' => null,
                'umur' => 0,
                'diolah' => 0,
                'mutasi' => 0,
                'masuk_hi' => 0,
                'stok_akhir' => 0,
                'keterangan' => $selectedDate->isoFormat('D MMMM YYYY'),
                'created_at_view' => null,
                'asal_bokar' => 'Petani'
            ];

            if (isset($maturasiHariIni[$uraian])) {
                $item = $maturasiHariIni[$uraian];
                $dataBak['id'] = $item->id;
                $dataBak['stok_awal'] = $item->stok_awal ?? 0;
                $dataBak['tgl_masuk'] = $item->tgl_masuk;
                $dataBak['umur'] = $item->umur ?? 0;
                $dataBak['diolah'] = $item->diolah ?? 0;
                $dataBak['mutasi'] = $item->mutasi ?? 0;
                $dataBak['masuk_hi'] = $item->masuk_hi ?? 0; 
                $dataBak['stok_akhir'] = $item->stok_akhir ?? 0;
                $dataBak['keterangan'] = $item->keterangan ?? $selectedDate->isoFormat('D MMMM YYYY');
                $dataBak['created_at_view'] = $item->created_at;
                $dataBak['asal_bokar'] = $item->asal_bokar ?? 'Petani';

            } elseif (isset($maturasiKemarin[$uraian])) {
                $itemKemarin = $maturasiKemarin[$uraian];
                $dataBak['stok_awal'] = $itemKemarin->stok_akhir ?? 0;
                $dataBak['umur'] = ($itemKemarin->umur ?? -1) + 1;
                $dataBak['tgl_masuk'] = $itemKemarin->tgl_masuk;

                if (isset($pengolahanBasahHariIni[$bakName])) {
                    $itemBasah = $pengolahanBasahHariIni[$bakName];
                    $dataBak['masuk_hi'] = $itemBasah->total_netto_kering ?? 0;
                }
                $dataBak['stok_akhir'] = $dataBak['stok_awal'] + $dataBak['masuk_hi'];

            } else {
                if (isset($pengolahanBasahHariIni[$bakName])) {
                    $itemBasah = $pengolahanBasahHariIni[$bakName];
                    $dataBak['masuk_hi'] = $itemBasah->total_netto_kering ?? 0;
                    $dataBak['stok_akhir'] = $dataBak['masuk_hi']; 
                }
            }
            $dataTampilan->push($dataBak);
        }

        return view('Pengolahan.data_maturasi', [
            'data_maturasi' => $dataTampilan,
            'selected_date' => $selectedDate->format('Y-m-d')
        ]);
    }

    /**
     * Menyimpan data maturasi baru.
     */
    public function store(Request $request)
    {
        // =======================================================================
        // --- AWAL PERBAIKAN VALIDASI ---
        // Kita bersihkan dulu format angka (misal "2.520,00") 
        // menjadi format yang dimengerti validator (misal "2520.00")
        // =======================================================================
        $cleanNumber = function ($value) {
            if (empty($value)) return 0;
            // 1. Hapus titik (ribuan)
            // 2. Ganti koma (desimal) dengan titik
            return str_replace(',', '.', str_replace('.', '', $value));
        };

        // Ganti data di dalam Request SEBELUM divalidasi
        $request->merge([
            'stok_awal' => $cleanNumber($request->input('stok_awal')),
            'masuk_hi'  => $cleanNumber($request->input('masuk_hi')),
            'diolah'    => $cleanNumber($request->input('diolah')), // Bersihkan juga untuk jaga-jaga
            'mutasi'    => $cleanNumber($request->input('mutasi')), // Bersihkan juga untuk jaga-jaga
        ]);
        // --- AKHIR PERBAIKAN VALIDASI ---


        // Validasi input dari form (sekarang data angka sudah bersih)
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'stok_awal' => 'required|numeric|min:0', // <-- Validasi akan lolos
            'umur' => 'required|integer|min:0',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0', // <-- Validasi akan lolos
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            'tanggal_input_harian' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $tanggalInputHariIni = Carbon::parse($data['tanggal_input_harian']);
        $tanggalKemarin = $tanggalInputHariIni->copy()->subDay();
        unset($data['tanggal_input_harian']); 

        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi (\d+)/', $data['uraian'], $matches)) {
            $nomorBak = "Bak Maturasi " . $matches[1];
        }

        // Ambil data Pengolahan Basah (INI AKAN MENIMPA 'masuk_hi' dari form)
        if ($nomorBak) {
            $masuk_hi_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', $tanggalInputHariIni)
                ->sum('netto_kering');

            if ($masuk_hi_hari_ini == 0) {
                $masuk_hi_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                    ->whereDate('created_at', $tanggalInputHariIni)
                    ->sum('netto_kering');
            }
            
            // Timpa 'masuk_hi' dari form dengan data otomatis
            $data['masuk_hi'] = $masuk_hi_hari_ini;
        }

        // Ambil data kemarin untuk tgl_masuk
        $previousMaturasi = PengolahanMaturasi::where('uraian', $data['uraian'])
            ->whereDate('created_at', '<', $tanggalInputHariIni) 
            ->orderBy('created_at', 'desc')
            ->first();

        $tgl_masuk_stok = null;
        if($previousMaturasi && $previousMaturasi->stok_akhir > 0) {
            $tgl_masuk_stok = $previousMaturasi->tgl_masuk;
        } elseif (($data['masuk_hi'] ?? 0) > 0) {
            $tgl_masuk_stok = $tanggalInputHariIni;
        }

        // Hitung stok akhir otomatis
        $stok_akhir = ($data['stok_awal'] ?? 0)
                      - ($data['diolah'] ?? 0)
                      - ($data['mutasi'] ?? 0)
                      + ($data['masuk_hi'] ?? 0);

        PengolahanMaturasi::create(array_merge($data, [
            'stok_akhir' => $stok_akhir,
            'tgl_masuk' => $tgl_masuk_stok,
            'created_at' => $tanggalInputHariIni->format('Y-m-d H:i:s'),
            'updated_at' => $tanggalInputHariIni->format('Y-m-d H:i:s') 
        ]));

        return redirect()->route('maturasi.index', [
            'filter_tanggal' => $tanggalInputHariIni->format('Y-m-d')
        ])->with('success', 'Data maturasi berhasil ditambahkan.');
    }

    /**
     * Dipanggil lewat AJAX untuk mengisi modal tambah otomatis.
     */
    public function getPreviousData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'tanggal_filter' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Input tidak valid'], 400);
        }

        $uraian = $request->input('uraian');
        $tanggalFilter = Carbon::parse($request->input('tanggal_filter'));

        $previousMaturasi = PengolahanMaturasi::where('uraian', $uraian)
            ->whereDate('created_at', '<', $tanggalFilter)
            ->orderBy('created_at', 'desc')
            ->first();

        $stok_awal = 0;
        $umur = 0;
        $tgl_masuk_ref = null;

        if ($previousMaturasi) {
            $stok_awal = $previousMaturasi->stok_akhir ?? 0;
            $tgl_masuk_ref = $previousMaturasi->tgl_masuk ? Carbon::parse($previousMaturasi->tgl_masuk) : Carbon::parse($previousMaturasi->created_at);

            if ($stok_awal > 0 && $tgl_masuk_ref) {
                $umur = $tgl_masuk_ref->diffInDays($tanggalFilter);
            }
        }
        
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi (\d+)/', $uraian, $matches)) {
            $nomorBak = "Bak Maturasi " . $matches[1];
        }

        $netto_kering_hari_ini = 0;
        if ($nomorBak) {
            $netto_kering_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', $tanggalFilter)
                ->sum('netto_kering');

            if ($netto_kering_hari_ini == 0) {
                $netto_kering_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                    ->whereDate('created_at', $tanggalFilter)
                    ->sum('netto_kering');
            }
        }

        if($stok_awal == 0 && $netto_kering_hari_ini > 0) {
            $umur = 0;
        }

        return response()->json([
            'stok_awal' => $stok_awal,
            'umur' => $umur,
            'netto_kering_hi' => $netto_kering_hari_ini,
            'stok_akhir' => $stok_awal + $netto_kering_hari_ini, 
        ]);
    }

    public function show($id)
    {
        return response()->json(PengolahanMaturasi::findOrFail($id));
    }

    public function edit($id)
    {
        return response()->json(PengolahanMaturasi::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $maturasi = PengolahanMaturasi::findOrFail($id);

        // Lakukan pembersihan angka juga untuk 'update'
        $cleanNumber = function ($value) {
            if (empty($value)) return 0;
            return str_replace(',', '.', str_replace('.', '', $value));
        };
        $request->merge([
            'stok_awal' => $cleanNumber($request->input('stok_awal')),
            'masuk_hi'  => $cleanNumber($request->input('masuk_hi')),
            'diolah'    => $cleanNumber($request->input('diolah')),
            'mutasi'    => $cleanNumber($request->input('mutasi')),
        ]);


        $validator = Validator::make($request->all(), [
            'tanggal_input' => 'required|date',
            'stok_awal' => 'required|numeric|min:0',
            'umur' => 'required|integer|min:0',
            'tgl_masuk' => 'nullable|date',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $tanggalRedirect = Carbon::parse($maturasi->created_at)->format('Y-m-d');
            return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalRedirect])
                ->withErrors($validator)
                ->withInput()
                ->with(['edit_error' => true, 'edit_id' => $id]);
        }

        $data = $validator->validated();
        
        $tanggalUpdate = Carbon::parse($data['tanggal_input']);
        unset($data['tanggal_input']); 

        $stok_akhir = ($data['stok_awal'] ?? 0)
                      - ($data['diolah'] ?? 0)
                      - ($data['mutasi'] ?? 0)
                      + ($data['masuk_hi'] ?? 0);

        unset($data['uraian']); 
        
        $maturasi->update(array_merge($data, [
            'stok_akhir' => $stok_akhir,
            'created_at' => $tanggalUpdate->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now() 
        ]));

        return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalUpdate->format('Y-m-d')])
            ->with('success', 'Data maturasi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $maturasi = PengolahanMaturasi::findOrFail($id);
        $tanggal_input = Carbon::parse($maturasi->created_at)->format('Y-m-d');
        $maturasi->delete();

        return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggal_input])
            ->with('success', 'Data berhasil dihapus.');
    }
}