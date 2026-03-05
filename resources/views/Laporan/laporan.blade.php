<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laporan Harian</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .table-bordered th, .table-bordered td { 
            border: 1px solid #dee2e6; 
            vertical-align: middle !important; 
            white-space: nowrap; 
            padding: 6px 10px;
            font-size: 0.9rem;
        }
        .bg-light th { 
            background-color: #f8f9fa; 
            font-weight: bold; 
            text-align: center;
            text-transform: uppercase;
        }
        .header-white th { 
            text-align: center; 
            font-weight: bold; 
            background-color: #ffffff; 
            color: #343a40; 
        }
        .bg-highlight { background-color: #d4edda; color: #155724; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .nav-tabs .nav-link { color: #495057; font-weight: bold; }
        .nav-tabs .nav-link.active { color: #28a745; border-top: 3px solid #28a745; background-color: #fff; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Rekapitulasi Laporan Harian</h1>
                <div>
                    <a href="#" onclick="tampilModal(); return false;" class="btn btn-warning btn-sm fw-bold mr-1">
                        <i class="fas fa-print"></i> Preview & Cetak PDF
                    </a>
                    <button type="button" onclick="downloadExcel()" class="btn btn-success btn-sm fw-bold">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        <strong class="my-auto">Preview Data Laporan</strong>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('laporan.index') }}" method="GET">
                            <div class="row mb-3 align-items-end">
                                {{-- Filter Tanggal Harian --}}
                                <div class="col-auto">
                                    <label for="filter_tanggal" class="form-label small fw-bold mb-1">Pilih Tanggal:</label>
                                    <input type="text" id="filter_tanggal" name="tanggal" class="form-control form-control-sm" value="{{ $tanggal->format('Y-m-d') }}" style="width: 140px;">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary btn-sm fw-bold mr-2"><i class="fas fa-filter mr-1"></i> Tampilkan</button>
                                    <a href="{{ route('laporan.index') }}" class="btn btn-secondary btn-sm fw-bold"><i class="fas fa-undo mr-1"></i> Reset</a>
                                </div>

                                {{-- Export Excel Bulanan 🚀 --}}
                                <div class="col-auto border-left pl-3 ml-2">
                                    <label for="filter_bulan_tahun" class="form-label small fw-bold mb-1">Export Excel 1 Bulan:</label>
                                    <div class="d-flex">
                                        <input type="month" id="filter_bulan_tahun" class="form-control form-control-sm mr-2" value="{{ $tanggal->format('Y-m') }}" style="width: 150px;">
                                        <button type="button" onclick="downloadExcelBulanan()" class="btn btn-success btn-sm fw-bold">
                                            <i class="fas fa-file-excel mr-1"></i> Download Bulanan
                                        </button>
                                    </div>
                                </div>

                                {{-- Badge Status --}}
                                <div class="col text-right">
                                    <span class="badge badge-light border p-2 mt-2">
                                        <i class="far fa-calendar-check mr-1"></i> Data: {{ $tanggal->translatedFormat('d F Y') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                        <hr>

                        <ul class="nav nav-tabs mb-3" id="laporanTab" role="tablist">
                            <li class="nav-item"><a class="nav-link active" id="bokar-tab" data-toggle="tab" href="#bokar" role="tab">1. Penerimaan Bokar</a></li>
                            <li class="nav-item"><a class="nav-link" id="maturasi-tab" data-toggle="tab" href="#maturasi" role="tab">2. Maturasi</a></li>
                            <li class="nav-item"><a class="nav-link" id="wip-tab" data-toggle="tab" href="#wip" role="tab">3. Bahan Proses (WIP)</a></li>
                            <li class="nav-item"><a class="nav-link" id="gudang-tab" data-toggle="tab" href="#gudang" role="tab">4. Gudang & Mutu</a></li>
                            <li class="nav-item"><a class="nav-link" id="penjualan-tab" data-toggle="tab" href="#penjualan" role="tab">5. Penjualan</a></li>
                        </ul>

                        <div class="tab-content" id="laporanTabContent">
                            
                            {{-- TAB 1: BOKAR --}}
                            <div class="tab-pane fade show active" id="bokar" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="width:100%">
                                        <thead class="bg-light">
                                            <tr>
                                                <th rowspan="2" width="5%">No</th>
                                                <th rowspan="2">Uraian / Supplier</th>
                                                <th rowspan="2">Stok Awal</th>
                                                <th colspan="2">Bokar Masuk</th>
                                                <th rowspan="2">Jumlah Stock Bokar</th> 
                                                <th colspan="2">Bokar Diolah</th>
                                                <th rowspan="2">Rektif</th>
                                                <th rowspan="2">Stok Akhir</th>
                                                <th rowspan="2">Keterangan</th>
                                            </tr>
                                            <tr>
                                                <th>Hari Ini</th> <th>S/d Hari Ini</th>
                                                <th>Hari Ini</th> <th>S/d Hari Ini</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $no=1; @endphp
                                            @foreach(['DS', 'PT', 'INHUT'] as $key)
                                                @php 
                                                    $d = $rekapBokar[$key]; 
                                                    $jumlahStok = $d['stok_awal'] + $d['masuk_hi'];
                                                    $stokAkhir = $jumlahStok - $d['kering_hi'] + ($d['rektif'] ?? 0);
                                                    $label = $key == 'DS' ? 'Petani (DS)' : ($key == 'PT' ? 'PTPN (PT)' : 'Inhutani');
                                                @endphp
                                                <tr>
                                                    <td class="text-center">{{ $no++ }}</td>
                                                    <td class="text-left font-weight-bold">{{ $label }}</td>
                                                    <td class="text-center">{{ number_format($d['stok_awal'], 0, ',', '.') }}</td>
                                                    <td class="text-center font-weight-bold text-success">{{ number_format($d['masuk_hi'], 0, ',', '.') }}</td>
                                                    <td class="text-center">{{ number_format($d['masuk_sdhi'], 0, ',', '.') }}</td>
                                                    <td class="text-center font-weight-bold">{{ number_format($jumlahStok, 0, ',', '.') }}</td>
                                                    <td class="text-center font-weight-bold text-primary">{{ number_format($d['kering_hi'], 0, ',', '.') }}</td>
                                                    <td class="text-center">{{ number_format($d['kering_sdhi'], 0, ',', '.') }}</td>
                                                    <td class="text-center">{{ number_format($d['rektif'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="text-center font-weight-bold">{{ number_format($stokAkhir, 0, ',', '.') }}</td>
                                                    <td class="text-center">-</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <td colspan="2" class="text-center">Total</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('stok_awal'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('masuk_hi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('masuk_sdhi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('stok_awal') + collect($rekapBokar)->sum('masuk_hi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('kering_hi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('kering_sdhi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($rekapBokar)->sum('rektif'), 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    {{ number_format(
                                                        (collect($rekapBokar)->sum('stok_awal') + collect($rekapBokar)->sum('masuk_hi')) - 
                                                        collect($rekapBokar)->sum('kering_hi') + collect($rekapBokar)->sum('rektif'), 0, ',', '.'
                                                    ) }}
                                                </td>
                                                <td class="text-center">-</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            {{-- TAB 2: MATURASI --}}
                            <div class="tab-pane fade" id="maturasi" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="width:100%">
                                        <thead class="bg-light">
                                            <tr>
                                                <th rowspan="2" width="5%">No.</th>
                                                <th rowspan="2">Uraian Proses</th>
                                                <th colspan="3">Stock Awal</th>
                                                <th colspan="2">Diproses HI</th>
                                                <th colspan="1">Masuk</th>
                                                <th colspan="3">Quality (Lab Maturasi)</th>
                                                <th rowspan="2">Stock Akhir</th>
                                                <th rowspan="2">Asal Bokar</th>
                                                <th rowspan="2">Keterangan</th>
                                            </tr>
                                            <tr>
                                                <th>Kg KK</th> <th>Tgl</th> <th>Umur</th>
                                                <th>Diolah</th> <th>Mutasi</th>
                                                <th>HI</th>
                                                <th>K3</th> <th>Po</th> <th>PRI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dataMaturasi as $index => $m)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-left font-weight-bold">{{ $m->no_bak ?? $m->uraian }}</td>
                                                <td class="text-center">{{ number_format($m->kering ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $m->tgl_isi ? strtoupper(\Carbon\Carbon::parse($m->tgl_isi)->translatedFormat('d M Y')) : '-' }}</td>
                                                <td class="text-center">{{ $m->umur ?? 0 }}</td>
                                                <td class="text-center">{{ number_format($m->diolah ?? 0, 0, ',', '.') }}</td>
                                                {{-- 🔥 PERBAIKAN FORMAT MUTASI SESUAI GAMBAR 🔥 --}}
                                                <td class="text-center font-weight-bold {{ $m->mutasi > 0 ? 'text-danger' : ($m->mutasi < 0 ? 'text-success' : '') }}">
                                                    @if($m->mutasi > 0)
                                                        ({{ number_format($m->mutasi, 0, ',', '.') }})
                                                    @elseif($m->mutasi < 0)
                                                        {{ number_format(abs($m->mutasi), 0, ',', '.') }}
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                                <td class="text-center font-weight-bold text-primary">{{ number_format($m->masuk_hi ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format($m->k3 ?? 0, 2, ',', '.') }}</td>
                                                <td class="text-center">{{ $m->po ?? '-' }}</td>
                                                <td class="text-center">{{ $m->pri ?? '-' }}</td>
                                                <td class="text-center font-weight-bold">{{ number_format($m->stok_akhir ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $m->jenis ?? '-' }}</td>
                                                <td class="text-center">{{ $m->keterangan ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <td class="text-center" colspan="2">Jumlah</td>
                                                <td class="text-center">{{ number_format(collect($dataMaturasi)->sum('kering'), 0, ',', '.') }}</td>
                                                <td colspan="2"></td>
                                                <td class="text-center">{{ number_format(collect($dataMaturasi)->sum('diolah'), 0, ',', '.') }}</td>
                                                {{-- 🔥 PERBAIKAN FORMAT TOTAL MUTASI DI FOOTER 🔥 --}}
                                                <td class="text-center">
                                                    @php $totMutasi = collect($dataMaturasi)->sum('mutasi'); @endphp
                                                    @if($totMutasi > 0.1)
                                                        <span class="text-danger">({{ number_format($totMutasi, 0, ',', '.') }})</span>
                                                    @elseif($totMutasi < -0.1)
                                                        <span class="text-success">{{ number_format(abs($totMutasi), 0, ',', '.') }}</span>
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ number_format(collect($dataMaturasi)->sum('masuk_hi'), 0, ',', '.') }}</td>
                                                <td colspan="3"></td>
                                                <td class="text-center">{{ number_format(collect($dataMaturasi)->sum('stok_akhir'), 0, ',', '.') }}</td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            {{-- TAB 3: WIP --}}
                            <div class="tab-pane fade" id="wip" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="width:100%">
                                        <thead class="bg-light">
                                            <tr>
                                                <th rowspan="2" width="5%">No.</th>
                                                <th rowspan="2">Tanggal</th>
                                                <th rowspan="2">Uraian</th>
                                                <th rowspan="2">Saldo Awal</th>
                                                <th colspan="2">WIP (Kg)</th> 
                                                <th rowspan="2">Produksi<br>SIR20</th>
                                                <th rowspan="2">Rektif</th>
                                                <th rowspan="2">Saldo Akhir</th>
                                                <th rowspan="2">Keterangan</th>
                                            </tr>
                                            <tr>
                                                <th>Masuk</th>
                                                <th>Keluar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dataWip as $index => $w)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-center">{{ $tanggal->format('d-m-Y') }}</td>
                                                <td class="text-left font-weight-bold">{{ $w->uraian }}</td>
                                                <td class="text-center">{{ number_format($w->stok_awal, 0, ',', '.') }}</td>
                                                <td class="text-center text-success">{{ number_format($w->masuk, 0, ',', '.') }}</td>
                                                <td class="text-center text-danger">{{ number_format($w->keluar, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format($w->produksi_sir20 ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format($w->rektif ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold">{{ number_format($w->stok_akhir, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $w->keterangan ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <td colspan="3" class="text-center">Total</td>
                                                <td class="text-center">{{ number_format(collect($dataWip)->sum('stok_awal'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataWip)->sum('masuk'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataWip)->sum('keluar'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataWip)->sum('produksi_sir20'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataWip)->sum('rektif'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataWip)->sum('stok_akhir'), 0, ',', '.') }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            {{-- TAB 4: GUDANG --}}
                            <div class="tab-pane fade" id="gudang" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-striped" style="width:100%">
                                        <thead class="header-white">
                                            <tr>
                                                <th rowspan="2">No</th>
                                                <th rowspan="2">Stock Dalam Gudang SIR</th>
                                                <th rowspan="2">Saldo Awal</th>
                                                <th rowspan="2">Masuk</th>
                                                <th rowspan="2">Total</th>
                                                <th colspan="2">Produksi Bulan Ini</th>
                                                <th rowspan="2">Pengiriman</th>
                                                <th rowspan="2">Saldo Akhir</th>
                                                <th rowspan="2">TOTAL I SD IV</th>
                                            </tr>
                                            <tr><th>Yg lalu</th><th>s/d HI</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dataGudang as $g)
                                            <tr>
                                                <td class="text-center font-weight-bold">{{ $loop->iteration }}</td>
                                                <td class="text-left font-weight-bold">{{ $g->uraian }}</td>
                                                <td class="text-center">{{ number_format($g->stok_awal, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold text-primary">{{ number_format($g->prod_hi, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(($g->stok_awal + $g->prod_hi), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format($g->prod_bln_lalu ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format($g->prod_sdhi, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold text-danger">{{ number_format($g->pengiriman ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold">{{ number_format($g->stok_akhir, 0, ',', '.') }}</td>
                                                <td class="text-center">-</td>
                                            </tr>
                                            @endforeach
                                            {{-- Baris Jumlah --}}
                                            <tr class="bg-light font-weight-bold">
                                                <td colspan="2" class="text-center">Total</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('stok_awal'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('prod_hi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('stok_awal') + collect($dataGudang)->sum('prod_hi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('prod_bln_lalu'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('prod_sdhi'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('pengiriman'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataGudang)->sum('stok_akhir'), 0, ',', '.') }}</td>
                                                <td class="text-center text-dark font-weight-bold" style="background-color: yellow;">
                                                    {{ number_format($total_1_sd_4, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- TABEL TAMBAHAN: MUTU --}}
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="width:100%">
                                        <thead class="header-white">
                                            <tr>
                                                <th>No</th>
                                                <th>Uraian</th>
                                                <th>Kg</th>
                                                <th>Pallet</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dataMutu as $idx => $r)
                                            <tr>
                                                <td class="text-center font-weight-bold">{{ $idx + 1 }}</td>
                                                <td class="text-left font-weight-bold">{{ $r[0] }}</td>
                                                <td class="text-center">{{ number_format($r[1], 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $r[2] }}</td>
                                                <td class="text-center">-</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- TAB 5: PENJUALAN --}}
                            <div class="tab-pane fade" id="penjualan" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" style="width:100%">
                                        <thead class="header-white">
                                            <tr>
                                                <th rowspan="2" width="5%">No</th>
                                                <th rowspan="2">Telah Dijual (KG SIR-20)</th>
                                                <th rowspan="2" width="12%">s/d Bulan Lalu</th>
                                                <th colspan="2">Penjualan Bulan Ini</th>
                                                <th rowspan="2">Total Bulan Ini</th>
                                                <th rowspan="2">Total Penjualan<br>s/d Hari ini</th>
                                                <th rowspan="2">Keterangan</th>
                                            </tr>
                                            <tr>
                                                <th class="bg-highlight">Yg lalu</th>
                                                <th>Hari Ini</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dataPenjualan as $p)
                                            <tr>
                                                <td class="text-center font-weight-bold">{{ $loop->iteration }}</td>
                                                <td class="text-left font-weight-bold">{{ $p->uraian }}</td>
                                                <td class="text-center">{{ number_format($p->sd_bulan_lalu, 0, ',', '.') }}</td>
                                                <td class="text-center bg-highlight">{{ number_format($p->bln_ini_lalu, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold text-success">{{ number_format($p->hari_ini, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold">{{ number_format($p->total_bln_ini, 0, ',', '.') }}</td>
                                                <td class="text-center font-weight-bold">{{ number_format($p->total_sd_hari_ini, 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $p->keterangan ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <td colspan="2" class="text-center text-uppercase">Total Ringkasan</td>
                                                <td class="text-center">{{ number_format(collect($dataPenjualan)->sum('sd_bulan_lalu'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataPenjualan)->sum('bln_ini_lalu'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataPenjualan)->sum('hari_ini'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataPenjualan)->sum('total_bln_ini'), 0, ',', '.') }}</td>
                                                <td class="text-center">{{ number_format(collect($dataPenjualan)->sum('total_sd_hari_ini'), 0, ',', '.') }}</td>
                                                <td class="text-center">-</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                        </div> {{-- End Tab Content --}}

                    </div> {{-- End Card Body --}}
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

<div class="modal fade" id="modalCetak" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;"> 
        <div class="modal-content" style="height: 90vh;"> 
            
            <div class="modal-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="modal-title font-weight-bold" id="modalLabel">
                    <i class="fas fa-print mr-2"></i> Preview Cetak Laporan
                </h5>
                
                <div>
                    <button onclick="downloadPdfFromModal()" class="btn btn-success btn-sm font-weight-bold mr-2">
                        <i class="fas fa-file-download mr-1"></i> UNDUH PDF
                    </button>

                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>

            <div class="modal-body p-0" style="background-color: #525659;">
                <iframe id="framePreview" src="" frameborder="0" style="width: 100%; height: 100%;"></iframe>
            </div>
        </div>
    </div>
</div>

@include('template.script')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// 1. Flatpickr & Event Listener jQuery
$(document).ready(function() {
    flatpickr("#filter_tanggal", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        defaultDate: "{{ $tanggal->format('Y-m-d') }}"
    });

    // Event saat modal ditutup (membersihkan iframe agar ringan)
    $('#modalCetak').on('hidden.bs.modal', function () {
        document.getElementById('framePreview').src = "";
    });
});

// 2. Fungsi Tampilkan Modal
function tampilModal() {
    var tanggalDipilih = document.getElementById('filter_tanggal').value;

    if(!tanggalDipilih) {
        alert("Harap pilih tanggal terlebih dahulu!");
        return;
    }

    var baseUrl = "{{ route('laporan.previewCetak', ['tanggal' => 'PLACEHOLDER']) }}";
    var finalUrl = baseUrl.replace('PLACEHOLDER', tanggalDipilih);

    // Masukkan ke Iframe
    document.getElementById('framePreview').src = finalUrl;

    // Tampilkan Modal
    $('#modalCetak').modal('show');
}

// 3. Fungsi Download PDF (Dieksekusi dari Parent, jalan di Child Iframe)
function downloadPdfFromModal() {
    var iframe = document.getElementById('framePreview');
    
    // Cek apakah iframe sudah memuat konten dan fungsi generatePDF ada
    if (iframe && iframe.contentWindow && iframe.contentWindow.generatePDF) {
        iframe.contentWindow.generatePDF(); // Panggil fungsi di halaman anak
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Tunggu Sebentar...',
            text: 'Dokumen sedang disiapkan, silakan coba beberapa detik lagi.',
            timer: 2000
        });
    }
}

// ==========================================================
// 🔥 4. TAMBAHKAN FUNGSI INI AGAR TOMBOL EXCEL BERFUNGSI 🔥
// ==========================================================
function downloadExcel() {
    // A. Ambil tanggal dari input
    var tanggalDipilih = document.getElementById('filter_tanggal').value;

    // B. Cek jika tanggal kosong
    if(!tanggalDipilih) {
        Swal.fire('Perhatian', 'Harap pilih tanggal terlebih dahulu!', 'warning');
        return;
    }

    // C. Redirect ke Route Export Excel
    // Ini akan memicu browser untuk mendownload file .xlsx
    var baseUrl = "{{ route('laporan.exportExcel') }}";
    window.location.href = baseUrl + "?tanggal=" + tanggalDipilih;
}

// ==========================================================
// 🔥 5. TAMBAHKAN FUNGSI INI AGAR TOMBOL EXCEL UNTUK BULANAN 🔥
// ==========================================================
function downloadExcelBulanan() {
        const input = document.getElementById('filter_bulan_tahun').value;
        if (!input) {
            alert('Silakan pilih bulan dan tahun!');
            return;
        }
        
        // Pecah YYYY-MM
        const parts = input.split('-');
        const tahun = parts[0];
        const bulan = parts[1];

        // Redirect ke route export (pastikan route name ini sudah terdaftar di web.php)
        window.location.href = `{{ route('laporan.exportBulanan') }}?bulan=${bulan}&tahun=${tahun}`;
    }
</script>
</body>
</html>