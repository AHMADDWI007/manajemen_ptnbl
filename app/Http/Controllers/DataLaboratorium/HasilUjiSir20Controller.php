<?php

namespace App\Http\Controllers\DataLaboratorium;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use Illuminate\Validation\Rule;
use App\Models\HasilUjiLabSIR20;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class HasilUjiSIR20Controller extends Controller
{
public function index(Request $request)
{
    $query = HasilUjiLabSIR20::query();
    
    $status = $request->get('status_mutu', 'all');
    
    // Ambil tanggal dari request, jika kosong gunakan hari ini (Asia/Makassar)
    $today = Carbon::now('Asia/Makassar')->format('Y-m-d');
    $start = $request->get('start_date', $today);
    $end   = $request->get('end_date', $today);

    // 1. Filter Mutu
    if ($status === 'low') {
        $query->where(function($q) {
            $q->where('pri', '<', 40)
              ->orWhere('po', '<', 30);
        });
    }

    // 2. Filter Tanggal (Selalu berjalan karena sudah ada default hari ini)
    $query->whereBetween('tanggal', [$start, $end]);

    $data_sir_20 = $query->orderBy('tanggal', 'desc')->get();

    // Logika Pallet Options (tetap sama)
    $testedPallets = HasilUjiLabSIR20::pluck('no_palet')->toArray();
    $allProd = ProduksiSir20::orderBy('tanggal_produksi', 'asc')->get();
    $palletOptions = [];
    foreach ($allProd as $prod) {
        for ($i = (int)$prod->nomor_start; $i <= (int)$prod->nomor_end; $i++) {
            if (!in_array($i, $testedPallets)) {
                $palletOptions[] = ['nomor' => $i, 'tanggal_prod' => $prod->tanggal_produksi];
            }
        }
    }

    return view('DataLaboratorium.hasil-uji-sir20', [
        'data_sir_20'   => $data_sir_20,
        'palletOptions' => $palletOptions,
        'fromDate'      => $start, // Kirim nilai start
        'toDate'        => $end,   // Kirim nilai end
        'statusMutu'    => $status,
        'today'         => $today
    ]);
}

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'nullable|string|in:MB5,SW',
            'no_palet'      => 'required|string|max:255|unique:hasil_uji_lab_sir_20,no_palet',
            'po'            => 'nullable|numeric|min:0',
            'pa'            => 'nullable|numeric|min:0',
            'dirt'          => 'nullable|numeric',
            'ash'           => 'nullable|numeric',
            'vm'            => 'nullable|numeric',
            'money'         => 'nullable|numeric',
            'nitrogen'      => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // ✅ LOGIKA HITUNG PRI OTOMATIS (Pa / Po * 100)
        $po = $request->input('po');
        $pa = $request->input('pa');

        if ($po !== null && $pa !== null && $po > 0) {
            $data['pri'] = ($pa / $po) * 100;
        } else {
            $data['pri'] = 0;
        }

        HasilUjiLabSIR20::create($data);

        return redirect()->route('hasil-uji-sir20.index')
                         ->with('success', 'Data hasil uji SIR 20 berhasil ditambahkan!');
    }

    public function show($id): JsonResponse
    {
        $data = HasilUjiLabSIR20::find($id);
        if(!$data) return response()->json(['error' => 'Data tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = HasilUjiLabSIR20::find($id);
        if(!$data) return response()->json(['error' => 'Data tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $hasilUji = HasilUjiLabSIR20::find($id);
        if (!$hasilUji) return redirect()->back()->with('error', 'Data tidak ditemukan.');

        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'nullable|string|in:MB5,SW',
            'no_palet'      => [
                'required', 'string', 'max:255',
                Rule::unique('hasil_uji_lab_sir_20')->ignore($hasilUji->id_hasil_uji_lab_sir_20, 'id_hasil_uji_lab_sir_20')
            ],
            'po'            => 'nullable|numeric|min:0',
            'pa'            => 'nullable|numeric|min:0',
            'dirt'          => 'nullable|numeric',
            'ash'           => 'nullable|numeric',
            'vm'            => 'nullable|numeric',
            'money'         => 'nullable|numeric',
            'nitrogen'      => 'nullable|numeric',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        $data = $validator->validated();

        // ✅ LOGIKA HITUNG PRI OTOMATIS (UPDATE)
        $po = $request->input('po');
        $pa = $request->input('pa');

        if ($po !== null && $pa !== null && $po > 0) {
            $data['pri'] = ($pa / $po) * 100;
        } else {
            $data['pri'] = 0;
        }

        $hasilUji->update($data);

        return redirect()->route('hasil-uji-sir20.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id): RedirectResponse
    {
        $hasilUji = HasilUjiLabSIR20::find($id);
        if ($hasilUji) {
            $hasilUji->delete();
            return redirect()->route('hasil-uji-sir20.index')->with('success', 'Data berhasil dihapus!');
        }
        return redirect()->route('hasil-uji-sir20.index')->with('error', 'Data gagal dihapus.');
    }

    public function getAvailablePallets(Request $request)
    {
        // 1. Ambil SEMUA nomor palet yang sudah pernah diuji
        $palletSudahDiuji = HasilUjiLabSIR20::pluck('no_palet')->toArray();

        // 2. Ambil data Pallet Fisik dari Tabel Pallet
        // Tambahkan 'id_pallet' ke dalam select
        $palletTersedia = \App\Models\Pallet::whereNull('tanggal_penjualan')
                            ->orderBy('id_pallet', 'asc') // Urutkan berdasarkan ID
                            ->get(['id_pallet', 'no_pallet', 'tanggal_produksi']);

        $daftarPalet = [];
        
        foreach ($palletTersedia as $p) {
            // 3. Hanya masukkan palet yang BELUM ada di tabel hasil uji
            if (!in_array($p->no_pallet, $palletSudahDiuji)) {
                
                $daftarPalet[] = [
                    // VALUE: Tetap 'no_pallet' string agar Controller Store tidak Error Validasi
                    'nomor' => $p->no_pallet, 
                    
                    // LABEL: Tampilkan ID Pallet sesuai permintaan
                    'label' => 'ID: ' . $p->id_pallet . ' (Tgl: ' . Carbon::parse($p->tanggal_produksi)->format('d/m/y') . ')',
                    
                    'tanggal_prod' => $p->tanggal_produksi
                ];
            }
        }

        return response()->json($daftarPalet);
    }
}