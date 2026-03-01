<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Produksi SIR 20</title>

    {{-- CSS Libraries (Sama seperti Lab) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* --- STYLE TABEL MIRIP LAB --- */
        .table-bordered th, .table-bordered td { 
            border: 1px solid #dee2e6; 
            vertical-align: middle !important; 
            white-space: nowrap; 
            text-align: center !important; 
        }
        /* Header Tabel Abu-abu Muda */
        #dataTable thead th {
            background-color: #f8f9fa; 
            color: #333;
            font-weight: bold;
        }
        
        /* Tombol Aksi Rapi */
        .action-buttons { 
            display: flex; 
            justify-content: center; 
            gap: 5px; 
        }

        /* --- STYLE LAIN (MODAL, FORM) TETAP ADA --- */
        .form-control-sm { border-radius: 3px; }
        .auto-input { background-color: #fff9c4 !important; color: #333; font-weight: bold; border: 1px solid #e0e0e0; cursor: default; }
        .unit-label { font-size: 0.85rem; font-weight: 600; line-height: 2; padding-left: 5px; }
        
        .summary-box { background: #e8f5e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 5px; text-align: center; margin-bottom: 15px; }
        .summary-value { font-size: 1.8rem; font-weight: bold; color: #2e7d32; display: block; }
        .summary-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #555; }

        .modal-xl { max-width: 95%; }
        .divider { border-top: 1px solid #eee; margin: 15px 0; }
        
        /* Fix Pagination Bootstrap 4 */
        .page-item.active .page-link { background-color: #28a745; border-color: #28a745; }
        .page-link { color: #28a745; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        {{-- HEADER CONTENT --}}
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Data Produksi SIR 20</h1>
                {{-- 🔥 HANYA TAMPIL JIKA BUKAN USER --}}
                @if(auth()->user()->role != 'user')
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-toggle="modal" data-target="#modalInputLaporan">
                    <i class="fas fa-plus-circle"></i> Input Laporan Baru
                </button>
                @endif
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong class="my-auto">Riwayat Produksi</strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3 align-items-end">
                            <div class="col-auto">
                                <form action="{{ route('produksi-sir20.cetak-harian') }}" method="GET" target="_blank" class="d-flex align-items-end" style="gap: 5px;">
                                    <div>
                                        <label class="form-label small fw-bold mb-1">Cetak Tanggal:</label>
                                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required style="width: 140px;">
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm fw-bold">
                                        <i class="fas fa-file-pdf"></i> Cetak Rekap Harian
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        {{-- 🔥 TAMBAHAN: FILTER TANGGAL (Gaya Laboratorium) --}}
                            <div class="row mb-3 align-items-end">
                                <div class="col-auto">
                                    <label for="min-date" class="form-label small fw-bold mb-1">Dari Tanggal:</label>
                                    <input type="text" id="min-date" class="form-control form-control-sm" placeholder="dd/mm/yyyy" style="width: 140px;">
                                </div>
                                <div class="col-auto">
                                    <label for="max-date" class="form-label small fw-bold mb-1">Sampai Tanggal:</label>
                                    <input type="text" id="max-date" class="form-control form-control-sm" placeholder="dd/mm/yyyy" style="width: 140px;">
                                </div>
                                <div class="col-auto">
                                    <button id="filter-btn" class="btn btn-primary btn-sm fw-bold mr-2">
                                        <i class="fas fa-filter mr-1"></i> Filter
                                    </button>
                                    <button id="reset-filter" class="btn btn-secondary btn-sm fw-bold">
                                        <i class="fas fa-undo mr-1"></i> Reset
                                    </button>
                                </div>
                            </div>
                            <hr>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle" id="dataTable" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Kamar Maturasi</th> {{-- 🔥 KOLOM BARU --}}
                                        <th>Total Remahan</th>
                                        <th>Total Produksi</th>
                                        <th>Pallet</th>         {{-- 🔥 KOLOM BARU --}}
                                        <th>Jam Kerja</th>
                                        <th>Petugas</th> {{-- 🔥 TAMBAHAN 1: Header Kolom Petugas --}}
                                        {{-- Lebar kolom aksi disesuaikan: 5% untuk user (1 tombol), 10% untuk admin (3 tombol) --}}
                                        <th style="width: {{ auth()->user()->role == 'user' ? '5%' : '10%' }};">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal_produksi)->format('d-m-Y') }}</td>
                                        <td>{{ $item->shift_kerja }}</td>
                                        
                                        {{-- 🔥 LOGIKA KAMAR MATURASI (List dipisah koma) --}}
                                        <td class="text-left small">
                                            {{ $item->remahan->pluck('ruang_maturasi')->unique()->implode(', ') }}
                                        </td>

                                        <td class="text-right">{{ number_format($item->remahan->sum('berat'), 0) }} Kg</td>
                                        <td class="text-right fw-bold">{{ number_format($item->kg_yang_dipress, 0) }} Kg</td>
                                        
                                        {{-- 🔥 KOLOM PALLET --}}
                                        <td>{{ number_format($item->jumlah_pallet, 0) }}</td>

                                        <td>{{ $item->jam_kerja }} Jam</td>
                                        
                                        {{-- 🔥 TAMBAHAN 2: Tampilkan Nama Petugas --}}
                                        <td>{{ $item->petugas ?? '-' }}</td>

                                        <td>
                                            <div class="action-buttons d-flex" style="gap: 5px;">
                                                {{-- Tombol Detail --}}
                                                <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id_produksi_sir20 }}" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                {{-- 🔥 TOMBOL EDIT & HAPUS: HANYA UNTUK NON-USER --}}
                                                @if(auth()->user()->role != 'user')
                                                    {{-- Tombol Edit --}}
                                                    <button class="btn btn-warning btn-sm btn-edit text-white" data-id="{{ $item->id_produksi_sir20 }}" title="Edit Data">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    {{-- 🔥 TOMBOL CETAK PDF BARU --}}
                                                    <a href="{{ route('produksi-sir20.cetak', $item->id_produksi_sir20) }}" target="_blank" class="btn btn-danger btn-sm" title="Cetak Laporan Ini">
                                                        <i class="fas fa-print"></i>
                                                    </a>

                                                    {{-- 🔥 TOMBOL HAPUS/BATAL --}}
                                                    <form action="{{ route('produksi-sir20.destroy', $item->id_produksi_sir20) }}" method="POST" class="delete-form" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan produksi ini? Stok akan dikembalikan ke Maturasi dan Pallet akan dihapus.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus/Batalkan Produksi">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

{{-- MODAL INPUT LAPORAN (TETAP SAMA) --}}
<div class="modal fade" id="modalInputLaporan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form action="{{ route('produksi-sir20.store') }}" method="POST" id="formProduksi">
                @csrf
                <div class="modal-header bg-success text-white py-2"> 
                    <h5 class="modal-title fw-bold" style="font-size: 1.1rem;">Input Laporan Produksi SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                {{-- ISI BODY MODAL SAMA SEPERTI SEBELUMNYA (LOGIKA HITUNG SUDAH BENAR) --}}
                <div class="modal-body pt-2">
                    {{-- ... (Paste Body Modal Input Anda di sini) ... --}}
                    {{-- HEADER FORM: TANGGAL --}}
                    <div class="row mb-3 bg-light p-2 rounded border">
                        <div class="col-md-3">
                            <label>Tanggal Produksi</label>
                            <input type="date" name="tanggal_produksi" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label>Shift Kerja</label>
                            <select name="shift_kerja" class="form-control form-control-sm">
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                                <option value="3">Shift 3</option>
                            </select>
                        </div>
                    </div>

                    {{-- TABEL MATURASI --}}
                    <h6 class="font-weight-bold text-success border-bottom pb-1">1. DATA MATURASI</h6>
                    <div class="row mb-3">
                        <div class="col-12">
                            <table class="table table-sm table-borderless table-maturasi mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        {{-- Tambahkan vertical-align: middle agar teks header sejajar vertikal --}}
                                        <th width="40%" style="vertical-align: middle;">Ruang Maturasi</th>
                                        <th width="25%" style="vertical-align: middle;">Berat Remahan (Kg)</th>
                                        <th width="20%" style="vertical-align: middle;">Umur (Hari)</th>
                                    </tr>
                                </thead>
                                <tbody id="maturasiContainer">
                                    <tr>
                                        <td>
                                            <select name="maturasi[0][ruang]" class="form-control form-control-sm select-ruang" required>
                                                <option value="">- Pilih Ruang -</option>
                                                {{-- Ganti bagian ini di dalam loop bak_aktif baris pertama --}}
                                                @foreach($bak_aktif as $bak)
                                                    <option value="{{ $bak->uraian }}" 
                                                            data-berat="{{ $bak->stok_akhir }}" 
                                                            data-umur="{{ $bak->umur }}"> {{-- 🔥 Pastikan pakai $bak->umur (sesuai buildOptions) --}}
                                                        {{ $bak->uraian }} (Stok: {{ number_format($bak->stok_akhir, 0) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="maturasi[0][berat]" class="form-control form-control-sm input-berat" step="0.01" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="maturasi[0][umur]" class="form-control form-control-sm input-umur" placeholder="0" readonly>
                                        </td>
                                        <td class="text-center">
                                            {{-- 🔥 TOMBOL DIPINDAH KESINI (Style Height diatur agar sama dengan input) 🔥 --}}
                                            <button type="button" class="btn btn-success btn-sm btn-block" id="btnAddMaturasi" title="Tambah Baris"
                                                    style="height: calc(1.6em + 0.5rem + 2px); display: flex; align-items: center; justify-content: center; border-radius: 3px;">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td> 
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- GRID UTAMA --}}
                    <div class="row">
                        <div class="col-lg-6 pr-lg-4" style="border-right: 1px solid #eee;">
                            <h6 class="font-weight-bold text-success border-bottom pb-1 mb-3">2. OPERASIONAL MESIN</h6>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Start Dryer</label>
                                <div class="col-4"><input type="time" name="jam_start_dryer" class="form-control form-control-sm"></div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Trolly Masuk</label>
                                <div class="col-4"><input type="number" name="trolly_masuk" class="form-control form-control-sm"></div>
                            </div>

                            <label class="text-muted small font-weight-bold">AKTUAL TEMPERATURE</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Brunner 1</label>
                                <div class="col-3"><input type="number" name="temp_b1_start" class="form-control form-control-sm" placeholder="Awal"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="temp_b1_end" class="form-control form-control-sm" placeholder="Akhir"></div>
                                <div class="col-1 unit-label">°C</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Brunner 2</label>
                                <div class="col-3"><input type="number" name="temp_b2_start" class="form-control form-control-sm" placeholder="Awal"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="temp_b2_end" class="form-control form-control-sm" placeholder="Akhir"></div>
                                <div class="col-1 unit-label">°C</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Cycle Time</label>
                                <div class="col-3"><input type="number" name="cycle_start" class="form-control form-control-sm" placeholder="Awal"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="cycle_end" class="form-control form-control-sm" placeholder="Akhir"></div>
                                <div class="col-1 unit-label">Min</div>
                            </div>

                            <label class="text-muted small font-weight-bold">BAHAN BAKAR</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="number" name="bb_solar" id="inputSolar" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="number" name="bb_batubara" id="inputBatubara" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="number" name="bb_cangkang" id="inputCangkang" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>

                            <div class="divider"></div>

                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jml Trolly Keluar</label>
                                <div class="col-5"><input type="number" name="trolly_keluar" id="inputTrollyKeluar" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Unit</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Stop Dryer</label>
                                <div class="col-5"><input type="time" name="jam_stop_dryer" class="form-control form-control-sm"></div>
                            </div>
                            {{-- 🔥 UPDATE INPUT JAM JALAN (READONLY KUNING) --}}
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Jalan Dryer</label>
                                <div class="col-5">
                                    <input type="text" name="jam_jalan_dryer" id="inputJamJalan" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>
                            
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jumlah Bales</label>
                                <div class="col-5"><input type="number" name="jumlah_bales" id="inputBales" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Bales</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Kg Dipress</label>
                                <div class="col-5"><input type="text" name="kg_press" id="outKgPress" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Capacity/Jam</label>
                                <div class="col-5">
                                    <input type="text" name="capacity_per_jam" id="outCapacity" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">Kg/H</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Kerja</label>
                                <div class="col-5"><input type="number" name="jam_kerja" id="inputJamKerja" class="form-control form-control-sm calc-trigger" step="0.1"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Produktivitas</label>
                                <div class="col-5">
                                    <input type="text" name="produktivitas" id="outProductivity" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">Kg/H</div>
                            </div>
                             <div class="row mb-1 align-items-center">
                                <label class="col-4">Kg Sir20/Cake</label>
                                <div class="col-5"><input type="number" name="kg_sir20" id="inputKgSir20" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Bales Kontamin</label>
                                <div class="col-5"><input type="number" name="bales_kontamin" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Bales</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Jam Ops Genset</label>
                                <div class="col-5"><input type="number" name="jam_genset" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>

                            <label class="text-muted small font-weight-bold">RATA-RATA BAHAN BAKAR (AUTO)</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="text" id="avgSolar" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="text" id="avgBatubara" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="text" id="avgCangkang" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN --}}
                        <div class="col-lg-6 pl-lg-4">
                            <h6 class="font-weight-bold text-success border-bottom pb-1 mb-3">3. LAIN-LAIN & PACKING</h6>
                            <div class="row mb-4 align-items-center">
                                <label class="col-4">Listrik PLN</label>
                                <div class="col-5"><input type="number" name="pln_kwh" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">KWH</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="summary-box">
                                        <span class="summary-title">Total Remahan</span>
                                        <span class="summary-value" id="bigTotalRemahan">0</span>
                                        <small class="text-muted">Kilogram</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="summary-box">
                                        <span class="summary-title">Produksi SIR 20</span>
                                        <span class="summary-value" id="bigTotalProduksi">0</span>
                                        <small class="text-muted">Kilogram</small>
                                    </div>
                                </div>
                            </div>

                            <label class="text-muted small font-weight-bold">DETAIL PACKING & JENIS KEMASAN</label>
                            <div class="form-group row align-items-center mb-2">
                                <label class="col-4 font-weight-normal">Jml Pallet Diisi</label>
                                <div class="col-5">
                                    <input type="number" name="jml_pallet" id="inputJmlPallet" class="form-control form-control-sm calc-trigger" step="1" placeholder="Input Manual">
                                </div>
                                <div class="col-3 unit-label text-muted small italic">Tentukan jenis per pallet ↓</div>
                            </div>

                            <div id="palletTypeRowsContainer">
                                </div>
                            {{-- <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Nomor</label>
                                <div class="col-5"><input type="number" name="total_nomor" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
                            </div>
                             <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">MC</label>
                                <div class="col-5"><input type="number" name="mc_val" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">MC</div>
                            </div> --}}
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Nomor</label>
                                {{-- Tambahkan class auto-input atau readonly --}}
                                <div class="col-2 pr-0">
                                    {{-- ID: outNomorStart --}}
                                    <input type="text" name="nomor_start" id="outNomorStart" class="form-control form-control-sm text-center auto-input" readonly>
                                </div>
                                <div class="col-1 text-center small px-0">s/d</div>
                                <div class="col-2 pl-0">
                                    {{-- ID: outNomorEnd --}}
                                    <input type="text" name="nomor_end" id="outNomorEnd" class="form-control form-control-sm text-center auto-input" readonly>
                                </div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Akhir</label>
                                <div class="col-5">
                                    {{-- ID: outTotalAkhir --}}
                                    <input type="number" name="total_nomor_akhir" id="outTotalAkhir" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">WP</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-bold text-dark">Petugas</label>
                                <div class="col-5">
                                    <select name="petugas" class="form-control form-control-sm select2-petugas" required>
                                        <option value="" disabled selected>-- Pilih Petugas --</option>
                                        @foreach($users as $user)
                                            {{-- Ubah $user->name menjadi $user->fullname --}}
                                            <option value="{{ $user->fullname }}">{{ $user->fullname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-4">Simpan Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT LAPORAN --}}
{{-- MODAL EDIT LAPORAN --}}
<div class="modal fade" id="modalEditLaporan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            {{-- Form action akan diisi dinamis via JS menggunakan ID --}}
            <form action="#" method="POST" id="formEditProduksi">
                @csrf
                @method('PUT')
                
                <div class="modal-header bg-success text-white py-2"> 
                    <h5 class="modal-title fw-bold" style="font-size: 1.1rem;">Edit Laporan Produksi SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body pt-2">
                    {{-- HEADER FORM: TANGGAL & SHIFT --}}
                    <div class="row mb-3 bg-light p-2 rounded border">
                        <div class="col-md-3">
                            <label>Tanggal Produksi</label>
                            <input type="date" name="tanggal_produksi" id="edit_tanggal_produksi" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label>Shift Kerja</label>
                            <select name="shift_kerja" id="edit_shift_kerja" class="form-control form-control-sm">
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                                <option value="3">Shift 3</option>
                            </select>
                        </div>
                    </div>

                    {{-- 1. DATA MATURASI --}}
                    <h6 class="font-weight-bold text-success border-bottom pb-1">1. DATA MATURASI</h6>
                    <div class="row mb-3">
                        <div class="col-12">
                            <table class="table table-sm table-borderless table-maturasi mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40%" style="vertical-align: middle;">Ruang Maturasi</th>
                                        <th width="25%" style="vertical-align: middle;">Berat Remahan (Kg)</th>
                                        <th width="20%" style="vertical-align: middle;">Umur (Hari)</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="maturasiContainerEdit">
                                    {{-- Baris akan di-generate otomatis oleh JavaScript saat tombol Edit diklik --}}
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-outline-success btn-xs mt-2" id="btnAddMaturasiEdit">
                                <i class="fas fa-plus"></i> Tambah Baris Maturasi
                            </button>
                        </div>
                    </div>

                    {{-- GRID UTAMA --}}
                    <div class="row">
                        {{-- KOLOM KIRI: OPERASIONAL --}}
                        <div class="col-lg-6 pr-lg-4" style="border-right: 1px solid #eee;">
                            <h6 class="font-weight-bold text-success border-bottom pb-1 mb-3">2. OPERASIONAL MESIN</h6>
                            
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Start Dryer</label>
                                <div class="col-4"><input type="time" name="jam_start_dryer" id="edit_jam_start" class="form-control form-control-sm"></div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Trolly Masuk</label>
                                <div class="col-4"><input type="number" name="trolly_masuk" id="edit_trolly_masuk" class="form-control form-control-sm"></div>
                            </div>

                            <label class="text-muted small font-weight-bold">AKTUAL TEMPERATURE</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Brunner 1</label>
                                <div class="col-3"><input type="number" name="temp_b1_start" id="edit_temp_b1_start" class="form-control form-control-sm"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="temp_b1_end" id="edit_temp_b1_end" class="form-control form-control-sm"></div>
                                <div class="col-1 unit-label">°C</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Brunner 2</label>
                                <div class="col-3"><input type="number" name="temp_b2_start" id="edit_temp_b2_start" class="form-control form-control-sm"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="temp_b2_end" id="edit_temp_b2_end" class="form-control form-control-sm"></div>
                                <div class="col-1 unit-label">°C</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Cycle Time</label>
                                <div class="col-3"><input type="number" name="cycle_start" id="edit_cycle_start" class="form-control form-control-sm"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="cycle_end" id="edit_cycle_end" class="form-control form-control-sm"></div>
                                <div class="col-1 unit-label">Min</div>
                            </div>

                            <label class="text-muted small font-weight-bold">BAHAN BAKAR</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="number" name="bb_solar" id="edit_bb_solar" class="form-control form-control-sm calc-trigger-edit"></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="number" name="bb_batubara" id="edit_bb_batubara" class="form-control form-control-sm calc-trigger-edit"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="number" name="bb_cangkang" id="edit_bb_cangkang" class="form-control form-control-sm calc-trigger-edit"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>

                            <div class="divider"></div>

                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jml Trolly Keluar</label>
                                <div class="col-5"><input type="number" name="trolly_keluar" id="edit_trolly_keluar" class="form-control form-control-sm calc-trigger-edit"></div>
                                <div class="col-3 unit-label">Unit</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Stop Dryer</label>
                                <div class="col-5"><input type="time" name="jam_stop_dryer" id="edit_jam_stop" class="form-control form-control-sm"></div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Jalan Dryer</label>
                                <div class="col-5">
                                    <input type="text" name="jam_jalan_dryer" id="edit_jam_jalan" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>
                            
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jumlah Bales</label>
                                <div class="col-5"><input type="number" name="jumlah_bales" id="edit_bales" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Bales</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Kg Dipress</label>
                                <div class="col-5"><input type="text" name="kg_press" id="edit_kg_press" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Capacity/Jam</label>
                                <div class="col-5"><input type="text" name="capacity_per_jam" id="edit_capacity" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg/H</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Kerja</label>
                                <div class="col-5"><input type="number" name="jam_kerja" id="edit_jam_kerja" class="form-control form-control-sm calc-trigger-edit" step="0.1"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Produktivitas</label>
                                <div class="col-5"><input type="text" name="produktivitas" id="edit_produktivitas" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg/H</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Kg Sir20/Cake</label>
                                <div class="col-5"><input type="number" name="kg_sir20" id="edit_kg_sir20" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Jam Ops Genset</label>
                                <div class="col-5"><input type="number" name="jam_genset" id="edit_jam_genset" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>

                            <label class="text-muted small font-weight-bold">RATA-RATA BAHAN BAKAR (AUTO)</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="text" id="edit_avgSolar" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="text" id="edit_avgBatubara" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="text" id="edit_avgCangkang" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN: SUMMARY & PACKING --}}
                        <div class="col-lg-6 pl-lg-4">
                            <h6 class="font-weight-bold text-success border-bottom pb-1 mb-3">3. LAIN-LAIN & PACKING</h6>
                            
                            <div class="row mb-4 align-items-center">
                                <label class="col-4">Listrik PLN</label>
                                <div class="col-5"><input type="number" name="pln_kwh" id="edit_pln_kwh" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">KWH</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="summary-box">
                                        <span class="summary-title">Total Remahan</span>
                                        <span class="summary-value" id="edit_bigTotalRemahan">0</span>
                                        <small class="text-muted">Kilogram</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="summary-box">
                                        <span class="summary-title">Produksi SIR 20</span>
                                        <span class="summary-value" id="edit_bigTotalProduksi">0</span>
                                        <small class="text-muted">Kilogram</small>
                                    </div>
                                </div>
                            </div>

                            <label class="text-muted small font-weight-bold">DETAIL PACKING</label>
                            <div class="form-group row align-items-center">
                                <label class="col-4 font-weight-normal">Jml Pallet Diisi</label>
                                <div class="col-5">
                                    <input type="number" name="jml_pallet" id="edit_inputJmlPallet" class="form-control form-control-sm calc-trigger-edit" step="1">
                                </div>
                                <div class="col-3 unit-label">SW</div>
                            </div>

                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Nomor</label>
                                <div class="col-2 pr-0">
                                    <input type="text" name="nomor_start" id="edit_outNomorStart" class="form-control form-control-sm text-center auto-input" readonly>
                                </div>
                                <div class="col-1 text-center small px-0">s/d</div>
                                <div class="col-2 pl-0">
                                    <input type="text" name="nomor_end" id="edit_outNomorEnd" class="form-control form-control-sm text-center auto-input" readonly>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Total Akhir</label>
                                <div class="col-5">
                                    <input type="number" name="total_nomor_akhir" id="edit_outTotalAkhir" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">WP</div>
                            </div>

                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-bold text-dark">Petugas</label>
                                <div class="col-5">
                                    <select name="petugas" id="edit_petugas" class="form-control form-control-sm" required>
                                        <option value="" disabled selected>-- Pilih Petugas --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->fullname }}">{{ $user->fullname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-4 text-white">Update Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL DATA (TETAP SAMA) --}}
<div class="modal fade" id="modalDetailData" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle"></i> Detail Produksi SIR 20</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light">
                
                {{-- INFO UTAMA --}}
                <div class="card card-body shadow-sm mb-3 pt-2 pb-2">
                    <div class="row">
                        <div class="col-md-3"><strong>Tanggal:</strong> <span id="detTanggal"></span></div>
                        <div class="col-md-3"><strong>Shift:</strong> <span id="detShift"></span></div>
                        <div class="col-md-3"><strong>Total Prod:</strong> <span id="detTotalProd" class="text-success font-weight-bold"></span></div>
                        <div class="col-md-3"><strong>Jam Kerja:</strong> <span id="detJamKerja"></span></div>
                    </div>
                </div>

                <div class="row">
                    {{-- KOLOM KIRI --}}
                    <div class="col-md-6">
                        <h6 class="font-weight-bold border-bottom border-info pb-1">1. DATA MATURASI (Sumber)</h6>
                        <table class="table table-sm table-bordered bg-white">
                            <thead class="thead-light">
                                <tr>
                                    <th>Ruang</th>
                                    <th class="text-right">Berat (Kg)</th>
                                    <th class="text-center">Umur</th>
                                </tr>
                            </thead>
                            <tbody id="detTabelMaturasi"></tbody>
                        </table>

                        <h6 class="font-weight-bold border-bottom border-info pb-1 mt-4">2. OPERASIONAL & SUHU</h6>
                        <dl class="row mb-0 small text-dark">
                            <dt class="col-5">Dryer (Start - Stop)</dt>
                            <dd class="col-7">: <span id="detJamDryer"></span> (<span id="detDurasiDryer"></span> Jam)</dd>
                            <dt class="col-5">Trolly (Masuk/Keluar)</dt>
                            <dd class="col-7">: <span id="detTrolly"></span> Unit</dd>
                            <dt class="col-5">Cycle Time</dt>
                            <dd class="col-7">: <span id="detCycle"></span> Menit</dd>
                        </dl>
                        <div class="mt-2 bg-white p-2 border rounded">
                            <small class="font-weight-bold d-block mb-1">SUHU BURNER:</small>
                            <ul class="mb-0 pl-3 small" id="detListSuhu"></ul>
                        </div>
                    </div>

                    {{-- KOLOM KANAN --}}
                    <div class="col-md-6">
                        <h6 class="font-weight-bold border-bottom border-info pb-1">3. HASIL PRODUKSI & UTILITAS</h6>
                        <dl class="row mb-0 small text-dark">
                            <dt class="col-6">Jumlah Bales</dt> <dd class="col-6">: <span id="detBales"></span></dd>
                            <dt class="col-6">Kg Dipress</dt> <dd class="col-6">: <span id="detKgPress"></span> Kg</dd>
                            <dt class="col-6">Capacity / Jam</dt> <dd class="col-6">: <span id="detCapacity"></span> Kg/H</dd>
                            <dt class="col-6">Produktivitas</dt> <dd class="col-6">: <span id="detProd"></span> Kg/H</dd>
                            <dt class="col-6">Listrik PLN</dt> <dd class="col-6">: <span id="detListrik"></span> KWH</dd>
                            <dt class="col-6">Genset</dt> <dd class="col-6">: <span id="detGenset"></span> Jam</dd>
                        </dl>

                        <h6 class="font-weight-bold border-bottom border-info pb-1 mt-3">4. BAHAN BAKAR</h6>
                        <table class="table table-sm table-bordered bg-white mb-3">
                            <thead class="thead-light">
                                <tr><th>Jenis</th><th class="text-right">Jumlah</th></tr>
                            </thead>
                            <tbody id="detTabelBB"></tbody>
                        </table>

                        <h6 class="font-weight-bold border-bottom border-info pb-1">5. PACKING</h6>
                        <div class="bg-white p-2 border rounded small">
                            <div><strong>Pallet:</strong> <span id="detPallet"></span> SW</div>
                            <div><strong>Nomor:</strong> <span id="detNomor"></span></div>
                            <div><strong>Total Akhir:</strong> <span id="detTotalAkhir"></span> WP</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('template.script')

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
{{-- Tambahkan baris ini --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    $(document).ready(function() {

        $('#inputJmlPallet').on('input', function() {
            var jml = parseInt($(this).val()) || 0;
            var container = $('#palletTypeRowsContainer');
            container.empty();

            if (jml > 0) {
                for (var i = 0; i < jml; i++) {
                    // Kita buat per baris sejajar agar rapi
                    container.append(`
                        <div class="form-group row align-items-center mb-1">
                            <label class="col-4 font-weight-normal small pl-4">Jenis Pallet #${i+1}</label>
                            <div class="col-5">
                                <select name="jenis_pallets[]" class="form-control form-control-sm">
                                    <option value="SW" selected>SW (Standard Wrapping)</option>
                                    <option value="MB5">MB5 (Metal Box 5)</option>
                                </select>
                            </div>
                            <div class="col-3 unit-label text-success small font-weight-bold">MANTAP</div>
                        </div>
                    `);
                }
            }
        });
        
        // --- Notifikasi SweetAlert ---
        @if (session('success')) Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 }); @endif
        @if (session('error')) Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", confirmButtonText: 'Cek Kembali' }); @endif

        // ==========================================
        // 🔥 1. LOGIKA FILTER TANGGAL (DATATABLE)
        // ==========================================
        function parseDMY(dateStr){
            if (!dateStr) return null;
            var parts = dateStr.split('-'); 
            if(parts.length!==3) return null; 
            return new Date(parts[2], parts[1]-1, parts[0]);
        }

        var configFlatpickr = { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", disableMobile: "true", defaultDate: "today" };
        var fpMin = flatpickr("#min-date", configFlatpickr);
        var fpMax = flatpickr("#max-date", configFlatpickr);

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            var min = $('#min-date').val();
            var max = $('#max-date').val();
            var tableDateStr = data[1] || ''; // Index 1 = Kolom Tanggal
            
            if (!tableDateStr || tableDateStr === '-') return true; 
            var tableDate = parseDMY(tableDateStr); 
            if (!tableDate) return true;

            var minDate = min ? new Date(min + 'T00:00:00') : null;
            var maxDate = max ? new Date(max + 'T23:59:59') : null;

            if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) { return true; }
            return false;
        });

        var table = $('#dataTable').DataTable({ 
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" }, 
            "order": [[1,"desc"]] 
        });

        $('#filter-btn').on('click', function(e){ e.preventDefault(); table.draw(); });
        $('#reset-filter').on('click', function(e){ e.preventDefault(); fpMin.clear(); fpMax.clear(); table.draw(); });

        // ==========================================
        // 🔥 2. LOGIKA SENSOR TANGGAL & UMUR (AJAX)
        // ==========================================
        
        // Fungsi pembangun opsi dropdown
        function buildOptions(dataArray) {
            let html = '<option value="">- Pilih Ruang -</option>';
            dataArray.forEach(function(bak) {
                // Ambil umur dari properti yang tersedia (umur atau umur_real)
                let umurBak = bak.umur !== undefined ? bak.umur : (bak.umur_real !== undefined ? bak.umur_real : 0);
                
                html += `<option value="${bak.uraian}" 
                                data-berat="${bak.stok_akhir}" 
                                data-umur="${umurBak}">
                            ${bak.uraian} (Stok: ${new Intl.NumberFormat('id-ID').format(bak.stok_akhir)} Kg)
                        </option>`;
            });
            return html;
        }

        // Init pertama kali dari server (Saat render awal)
        var optionsMaturasi = buildOptions(@json($bak_aktif));

        // 🔥 SENSOR 1: Saat Tanggal Input Baru Diganti
        $('input[name="tanggal_produksi"]').on('change', function() {
            var selectedDate = $(this).val();
            if(selectedDate) {
                $.ajax({
                    url: window.location.pathname + '?ajax_date=' + selectedDate,
                    type: 'GET',
                    success: function(data) {
                        optionsMaturasi = buildOptions(data);
                        // Refresh semua dropdown di modal tambah
                        $('.select-ruang').each(function() {
                            var currentVal = $(this).val();
                            $(this).html(optionsMaturasi);
                            $(this).val(currentVal);
                            $(this).trigger('change'); // 🔥 TRIGGER INI PENTING agar umur terisi otomatis
                        });
                    }
                });
            }
        });

        // 🔥 SENSOR 2: Saat Tanggal Edit Diganti
        $('#edit_tanggal_produksi').on('change', function() {
            var selectedDate = $(this).val();
            if(selectedDate) {
                $.ajax({
                    url: window.location.pathname + '?ajax_date=' + selectedDate,
                    type: 'GET',
                    success: function(data) {
                        var newEditOptions = buildOptions(data);
                        $('.select-ruang-edit').each(function() {
                            var currentVal = $(this).val();
                            $(this).html(newEditOptions);
                            $(this).val(currentVal);
                            $(this).trigger('change'); // 🔥 TRIGGER INI PENTING
                        });
                    }
                });
            }
        });

        // Event Listener: Saat Ruang Dipilih (Murni Mengambil Data Server)
        $(document).on('change', '.select-ruang, .select-ruang-edit', function() {
            var selectedOption = $(this).find(':selected');
            var row = $(this).closest('tr');

            var inputBerat = row.find('.input-berat, .input-berat-edit');
            var inputUmur = row.find('.input-umur, .input-umur-edit');

            if (selectedOption.val() !== "") {
                // Gunakan .attr('data-umur') sebagai fallback jika .data('umur') gagal
                var berat = selectedOption.data('berat');
                var umur = selectedOption.data('umur');

                // Jika .data() menghasilkan undefined, ambil mentahnya
                if (umur === undefined) {
                    umur = selectedOption.attr('data-umur');
                }

                inputBerat.val(parseFloat(String(berat).replace(',', '.')));
                inputUmur.val(umur || 0); 
                
                calculateTotals();
            } else {
                inputBerat.val(''); 
                inputUmur.val(''); 
                calculateTotals();
            }
        });

        // Event Listener: Tambah Baris Maturasi
        var maturasiIndex = 1;
        $('#btnAddMaturasi').click(function() {
            var html = `<tr>
                <td><select name="maturasi[${maturasiIndex}][ruang]" class="form-control form-control-sm select-ruang" required>${optionsMaturasi}</select></td>
                <td><input type="number" name="maturasi[${maturasiIndex}][berat]" class="form-control form-control-sm input-berat" step="0.01"></td>
                <td><input type="number" name="maturasi[${maturasiIndex}][umur]" class="form-control form-control-sm input-umur" readonly></td>
                <td><button type="button" class="btn btn-danger btn-xs btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
            </tr>`;
            $('#maturasiContainer').append(html);
            maturasiIndex++;
        });

        $(document).on('click', '.btn-remove-maturasi', function() { $(this).closest('tr').remove(); calculateTotals(); });

        // Fungsi Hitung Total Berat & Produksi
        function calculateTotals() {
            // 1. Hitung Total Remahan (Tetap Sama)
            var totalRemahan = 0;
            $('.input-berat').each(function() { 
                var val = parseFloat($(this).val()) || 0;
                totalRemahan += val; 
            });
            $('#bigTotalRemahan').text(Math.ceil(totalRemahan).toLocaleString('id-ID'));

            // =========================================================
            // 🔥 LOGIKA BARU: PALLET MANUAL -> BALES AUTO 🔥
            // =========================================================
            
            // 2. Ambil input Jml Pallet yang diisi manual
            var jmlPallet = parseFloat($('#inputJmlPallet').val()) || 0;

            // 3. Hitung Jumlah Bales Otomatis (Misal: 1 Pallet isi 36 Bales)
            // Ganti angka 36 di bawah ini sesuai standar Maswi
            var autoBales = jmlPallet * 36; 
            $('#inputBales').val(autoBales); // Set ke input Bales

            // 4. Hitung Kg Press (Bales x 35)
            var kgPress = Math.ceil(autoBales * 35); 
            
            $('#outKgPress').val(kgPress);
            $('#bigTotalProduksi').text(kgPress.toLocaleString('id-ID'));

            // 5. Update Nomor Packing (Tetap otomatis berdasarkan Jml Pallet)
            if (jmlPallet > 0) {
                // nextStart didapat dari variabel global lastNomorDB + 1
                var nomorEnd = nextStart + jmlPallet - 1;
                $('#outNomorStart').val(nextStart);
                $('#outNomorEnd').val(nomorEnd); 
                $('#outTotalAkhir').val(nomorEnd); 
            } else {
                $('#outNomorEnd').val('');
                $('#outTotalAkhir').val('');
            }

            // =========================================================
            // 6. RUMUS KG CAKE (RATA-RATA PER SEKAT)
            // =========================================================
            var trollyKeluar = parseFloat($('#inputTrollyKeluar').val()) || 0;
            if (kgPress > 0 && trollyKeluar > 0) {
                var nilaiCake = (kgPress / trollyKeluar) / 28;
                $('#inputKgSir20').val(nilaiCake.toFixed(2));
            } else {
                $('#inputKgSir20').val(0);
            }

            // 7. Hitung Kapasitas & Produktivitas
            var jamJalan = parseFloat($('#inputJamJalan').val()) || 0;
            var jamKerja = parseFloat($('#inputJamKerja').val()) || 0;
            
            var capacity = jamJalan > 0 ? Math.ceil(kgPress / jamJalan) : 0;
            var productivity = jamKerja > 0 ? Math.ceil(kgPress / jamKerja) : 0;

            $('#outCapacity').val(capacity);
            $('#outProductivity').val(productivity);

            // 8. Hitung Rata-rata Bahan Bakar
            var solar = parseFloat($('#inputSolar').val()) || 0;
            var batubara = parseFloat($('#inputBatubara').val()) || 0;
            var cangkang = parseFloat($('#inputCangkang').val()) || 0;

            // Rumus: Kg SIR Press / 1000
            var divisor = kgPress / 1000;

            if (divisor > 0) {
                $('#avgSolar').val((solar / divisor).toFixed(4));
                $('#avgBatubara').val((batubara / divisor).toFixed(4));
                $('#avgCangkang').val((cangkang / divisor).toFixed(4));
            } else {
                $('#avgSolar').val(0);
                $('#avgBatubara').val(0);
                $('#avgCangkang').val(0);
            }
        }
        
        $(document).on('input', '.calc-trigger, .input-berat, #inputJmlPallet, input[name="jam_start_dryer"], input[name="jam_stop_dryer"]', calculateTotals);

        // Logika Jam Jalan Otomatis
        $('input[name="jam_start_dryer"], input[name="jam_stop_dryer"]').on('change', function() {
            var start = $('input[name="jam_start_dryer"]').val();
            var stop = $('input[name="jam_stop_dryer"]').val();

            if (start && stop) {
                var dateStart = new Date("01/01/2000 " + start);
                var dateStop = new Date("01/01/2000 " + stop);
                if (dateStop < dateStart) dateStop.setDate(dateStop.getDate() + 1);

                var diffHrs = (dateStop - dateStart) / 1000 / 60 / 60;
                $('#inputJamJalan').val(diffHrs.toFixed(2));
                calculateTotals();
            }
        });

        // ==========================================
        // 🔥 3. LOGIKA PACKING (AUTO NOMOR)
        // ==========================================
        var lastNomorDB = {{ $lastNomorAkhir ?? 0 }};
        var nextStart = parseInt(lastNomorDB) + 1;
        $('#outNomorStart').val(nextStart);

        $('#inputJmlPallet').on('input', function() {
            var jmlPallet = parseInt($(this).val()) || 0;
            if (jmlPallet > 0) {
                var nomorEnd = nextStart + jmlPallet - 1;
                $('#outNomorStart').val(nextStart);
                $('#outNomorEnd').val(nomorEnd); 
                $('#outTotalAkhir').val(nomorEnd); 
            } else {
                $('#outNomorEnd').val('');
                $('#outTotalAkhir').val('');
            }
        });

        // Reset Form saat modal dibuka
        $('#modalInputLaporan').on('show.bs.modal', function () {
            $('#outNomorStart').val(nextStart); 
            // 2. 🔥 REFRESH Dropdown Baris Pertama agar mengikuti opsi terbaru dari buildOptions
            // Ini penting agar atribut data-umur dan data-berat-nya segar
            $('.select-ruang').first().html(optionsMaturasi);
            $('#inputJmlPallet').val('');
            $('#outNomorEnd').val('');
            $('#outTotalAkhir').val('');
        });

        // ==========================================
        // 🔥 4. LOGIKA EDIT & DETAIL
        // ==========================================
        
        // Logika Edit Nomor (Auto Hitung saat Edit)
        function hitungNomorEdit() {
            var start = parseInt($('#edit_nomor_start').val()) || 0;
            var jml = parseInt($('#edit_jml_pallet').val()) || 0;
            if (start > 0 && jml > 0) {
                var end = start + jml - 1;
                $('#edit_nomor_end').val(end);
                $('#edit_total_nomor_akhir').val(end); 
            }
        }
        $('#edit_jml_pallet, #edit_nomor_start').on('input keyup', function() { hitungNomorEdit(); });

        // Logika Hitung Total Edit
        function calculateTotalsEdit() {
            // 1. Hitung Total Remahan
            var totalRemahan = 0;
            $('.input-berat-edit').each(function() { 
                totalRemahan += parseFloat($(this).val()) || 0; 
            });
            $('#edit_bigTotalRemahan').text(Math.ceil(totalRemahan).toLocaleString('id-ID'));

            // 2. Logika Pallet -> Bales -> Kg Press
            var jmlPallet = parseFloat($('#edit_inputJmlPallet').val()) || 0;
            var autoBales = jmlPallet * 36; // 1 Pallet = 36 Bales
            var kgPress = Math.ceil(autoBales * 35); // 1 Bale = 35 Kg
            
            $('#edit_bales').val(autoBales);
            $('#edit_kg_press').val(kgPress);
            $('#edit_bigTotalProduksi').text(kgPress.toLocaleString('id-ID'));

            // 3. Update Nomor Packing (Otomatis)
            var start = parseInt($('#edit_outNomorStart').val()) || 0;
            if (start > 0 && jmlPallet > 0) {
                var end = start + jmlPallet - 1;
                $('#edit_outNomorEnd').val(end);
                $('#edit_outTotalAkhir').val(end); 
            }

            // 4. Hitung Jam Jalan Dryer
            var startT = $('#edit_jam_start').val();
            var stopT = $('#edit_jam_stop').val();
            if (startT && stopT) {
                var d1 = new Date("01/01/2000 " + startT);
                var d2 = new Date("01/01/2000 " + stopT);
                if (d2 < d1) d2.setDate(d2.getDate() + 1);
                var diff = (d2 - d1) / 1000 / 60 / 60;
                $('#edit_jam_jalan').val(diff.toFixed(2));
            }

            // 5. Hitung Kapasitas & Produktivitas
            var jamJalan = parseFloat($('#edit_jam_jalan').val()) || 0;
            var jamKerja = parseFloat($('#edit_jam_kerja').val()) || 0;
            
            $('#edit_capacity').val(jamJalan > 0 ? Math.ceil(kgPress / jamJalan) : 0);
            $('#edit_produktivitas').val(jamKerja > 0 ? Math.ceil(kgPress / jamKerja) : 0);

            // 6. Hitung Rata-rata Bahan Bakar
            var solar = parseFloat($('#edit_bb_solar').val()) || 0;
            var batubara = parseFloat($('#edit_bb_batubara').val()) || 0;
            var cangkang = parseFloat($('#edit_bb_cangkang').val()) || 0;

            // Rumus: Kg SIR Press / 1000
            var divisorEdit = kgPress / 1000;

            if (divisorEdit > 0) {
                $('#edit_avgSolar').val((solar / divisorEdit).toFixed(4));
                $('#edit_avgBatubara').val((batubara / divisorEdit).toFixed(4));
                $('#edit_avgCangkang').val((cangkang / divisorEdit).toFixed(4));
            } else {
                $('#edit_avgSolar').val(0);
                $('#edit_avgBatubara').val(0);
                $('#edit_avgCangkang').val(0);
            }
        }

        // Trigger untuk Modal Edit
        $(document).on('input change', '.calc-trigger-edit, .input-berat-edit, #edit_inputJmlPallet, #edit_jam_start, #edit_jam_stop, #edit_jam_kerja, #edit_trolly_keluar', calculateTotalsEdit);

        // Tombol Tambah Baris Maturasi di Edit
        var maturasiEditIndex = 0; // Global agar tidak reset
        $('#btnAddMaturasiEdit').click(function() {
            var html = `<tr>
                <td><select name="maturasi[${maturasiEditIndex}][ruang]" class="form-control form-control-sm select-ruang-edit" required>${optionsMaturasi}</select></td>
                <td><input type="number" name="maturasi[${maturasiEditIndex}][berat]" class="form-control form-control-sm input-berat-edit" step="0.01"></td>
                <td><input type="number" name="maturasi[${maturasiEditIndex}][umur]" class="form-control form-control-sm input-umur-edit" readonly></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
            </tr>`;
            $('#maturasiContainerEdit').append(html);
            maturasiEditIndex++;
        });

        // Jam Jalan Edit
        $('#edit_jam_start, #edit_jam_stop').on('change', function() {
            var start = $('#edit_jam_start').val();
            var stop = $('#edit_jam_stop').val();
            if (start && stop) {
                var dateStart = new Date("01/01/2000 " + start);
                var dateStop = new Date("01/01/2000 " + stop);
                if (dateStop < dateStart) dateStop.setDate(dateStop.getDate() + 1);
                var diffHrs = (dateStop - dateStart) / 1000 / 60 / 60;
                $('#edit_jam_jalan').val(diffHrs.toFixed(2));
                calculateTotalsEdit();
            }
        });

        // Klik Tombol Edit
        var maturasiEditIndex = 0;
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ url('produksi-sir20') }}/" + id,
                type: "GET",
                success: function(data) {
                    // Set Action Form
                    $('#formEditProduksi').attr('action', "{{ url('produksi-sir20') }}/" + id);
                    
                    // Map Data Header
                    $('#edit_tanggal_produksi').val(data.tanggal_produksi);
                    $('#edit_shift_kerja').val(data.shift_kerja);
                    $('#edit_petugas').val(data.petugas);

                    // Map Maturasi
                    $('#maturasiContainerEdit').empty();
                    if(data.remahan && data.remahan.length > 0) {
                        data.remahan.forEach(function(val, i) {
                            var row = `<tr>
                                <td><select name="maturasi[${i}][ruang]" class="form-control form-control-sm select-ruang-edit" required>${optionsMaturasi}</select></td>
                                <td><input type="number" name="maturasi[${i}][berat]" class="form-control form-control-sm input-berat-edit" step="0.01" value="${val.berat}"></td>
                                <td><input type="number" name="maturasi[${i}][umur]" class="form-control form-control-sm input-umur-edit" readonly value="${val.umur}"></td>
                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
                            </tr>`;
                            $('#maturasiContainerEdit').append(row);
                            // Set value dropdown
                            $('#maturasiContainerEdit tr').last().find('select').val(val.ruang_maturasi);
                            maturasiEditIndex = i + 1;
                        });
                    }

                    // Map Operasional & Suhu
                    $('#edit_jam_start').val(data.jam_start_dryer);
                    $('#edit_jam_stop').val(data.jam_stop_dryer);
                    $('#edit_trolly_masuk').val(data.jumlah_trolly_masuk);
                    $('#edit_trolly_keluar').val(data.jumlah_trolly_keluar);
                    $('#edit_jam_kerja').val(data.jam_kerja);
                    $('#edit_jam_genset').val(data.jam_operasional_genset);
                    $('#edit_pln_kwh').val(data.pemakaian_listrik_pln);

                    // Map Suhu & Cycle
                    if(data.aktual_temperature) {
                        var b1 = data.aktual_temperature.find(t => t.jenis === 'Burner 1');
                        var b2 = data.aktual_temperature.find(t => t.jenis === 'Burner 2');
                        var cy = data.aktual_temperature.find(t => t.jenis === 'Cycle Time');
                        if(b1) { $('#edit_temp_b1_start').val(b1.nilai_start); $('#edit_temp_b1_end').val(b1.nilai_end); }
                        if(b2) { $('#edit_temp_b2_start').val(b2.nilai_start); $('#edit_temp_b2_end').val(b2.nilai_end); }
                        if(cy) { $('#edit_cycle_start').val(cy.nilai_start); $('#edit_cycle_end').val(cy.nilai_end); }
                    }

                    // Map Bahan Bakar
                    if(data.bahan_bakar) {
                        var s = data.bahan_bakar.find(f => f.bahan_bakar === 'Solar');
                        var b = data.bahan_bakar.find(f => f.bahan_bakar === 'Batu Bara');
                        var c = data.bahan_bakar.find(f => f.bahan_bakar === 'Cangkang');
                        if(s) $('#edit_bb_solar').val(s.digunakan);
                        if(b) $('#edit_bb_batubara').val(b.digunakan);
                        if(c) $('#edit_bb_cangkang').val(c.digunakan);
                    }

                    // Map Packing
                    $('#edit_inputJmlPallet').val(data.jumlah_pallet);
                    $('#edit_outNomorStart').val(data.nomor_start);
                    $('#edit_outNomorEnd').val(data.nomor_end);
                    $('#edit_outTotalAkhir').val(data.total_nomor_akhir);

                    // Jalankan kalkulasi setelah semua data masuk
                    calculateTotalsEdit();
                    
                    $('#modalEditLaporan').modal('show');
                }
            });
        });

        // Detail View Logic
        $(document).on('click', '.btn-detail', function() {
            var id = $(this).data('id');
            var url = "{{ url('produksi-sir20') }}/" + id;
            $.ajax({
                url: url, type: "GET", dataType: "JSON",
                success: function(data) {
                    const fmt = (num) => (num === null || num === undefined) ? '0' : parseFloat(num).toLocaleString('id-ID');
                    $('#detTanggal').text(data.tanggal_produksi || '-');
                    $('#detShift').text(data.shift_kerja || '-');
                    $('#detTotalProd').text(fmt(data.kg_yang_dipress) + ' Kg');
                    $('#detJamKerja').text((data.jam_kerja || 0) + ' Jam');
                    
                    var htmlMaturasi = '';
                    if (data.remahan && data.remahan.length > 0) {
                        $.each(data.remahan, function(i, val) {
                            htmlMaturasi += `<tr><td>${val.ruang_maturasi || '-'}</td><td class="text-right">${fmt(val.berat)}</td><td class="text-center">${val.umur || 0} Hari</td></tr>`;
                        });
                    } else { htmlMaturasi = '<tr><td colspan="3" class="text-center text-muted">-</td></tr>'; }
                    $('#detTabelMaturasi').html(htmlMaturasi);

                    $('#detJamDryer').text(`${data.jam_start_dryer || '?'} s/d ${data.jam_stop_dryer || '?'}`);
                    $('#detDurasiDryer').text(data.jumlah_jam_dryer || 0);
                    $('#detTrolly').text(`${data.jumlah_trolly_masuk || 0} Masuk / ${data.jumlah_trolly_keluar || 0} Keluar`);

                    var arrSuhu = data.aktual_temperature || [];
                    var htmlSuhu = '', cycle = '-';
                    if (Array.isArray(arrSuhu)) {
                        $.each(arrSuhu, function(i, val) {
                            if (val.jenis === 'Cycle Time') cycle = `${val.nilai_start} - ${val.nilai_end}`;
                            else htmlSuhu += `<li>${val.jenis}: <strong>${val.nilai_start} - ${val.nilai_end} °C</strong></li>`;
                        });
                    }
                    $('#detCycle').text(cycle);
                    $('#detListSuhu').html(htmlSuhu);

                    $('#detBales').text(data.jumlah_bales_dipress || 0);
                    $('#detKgPress').text(fmt(data.kg_yang_dipress));
                    $('#detCapacity').text(fmt(data.capacity_per_jam));
                    $('#detProd').text(fmt(data.produktivitas));
                    $('#detListrik').text(fmt(data.pemakaian_listrik_pln));
                    $('#detGenset').text(data.jam_operasional_genset || 0);

                    var arrBB = data.bahan_bakar || [];
                    var htmlBB = '';
                    if (Array.isArray(arrBB) && arrBB.length > 0) {
                        $.each(arrBB, function(i, val) { htmlBB += `<tr><td>${val.bahan_bakar}</td><td class="text-right">${fmt(val.digunakan)}</td></tr>`; });
                    } else { htmlBB = '<tr><td colspan="2" class="text-center">-</td></tr>'; }
                    $('#detTabelBB').html(htmlBB);

                    $('#detPallet').text(data.jumlah_pallet || 0);
                    $('#detNomor').text(`${data.nomor_start || ''} s/d ${data.nomor_end || ''}`);
                    $('#detTotalAkhir').text(data.total_nomor_akhir || 0);

                    $('#modalDetailData').modal('show');
                }
            });
        });

    });
</script>

</body>
</html>