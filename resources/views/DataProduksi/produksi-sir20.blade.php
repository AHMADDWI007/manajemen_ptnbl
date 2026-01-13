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
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-toggle="modal" data-target="#modalInputLaporan">
                    <i class="fas fa-plus-circle"></i> Input Laporan Baru
                </button>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        Riwayat Produksi
                    </div>
                    <div class="card-body">
                        
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
                                        <th style="width: 10%;">Aksi</th>
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
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id_produksi_sir20 }}" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                {{-- 🔥 TOMBOL EDIT --}}
                                                <button class="btn btn-warning btn-sm btn-edit text-white" data-id="{{ $item->id_produksi_sir20 }}" title="Edit Data">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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
                                        <th width="30%">Ruang Maturasi</th>
                                        <th width="30%">Berat Remahan (Kg)</th>
                                        <th width="30%">Umur (Hari)</th>
                                        <th>
                                            <button type="button" class="btn btn-success btn-xs btn-block" id="btnAddMaturasi">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="maturasiContainer">
                                    <tr>
                                        <td>
                                            <select name="maturasi[0][ruang]" class="form-control form-control-sm select-ruang" required>
                                                <option value="">- Pilih Ruang -</option>
                                                @foreach($bak_aktif as $bak)
                                                    <option value="{{ $bak->uraian }}" 
                                                            data-berat="{{ $bak->stok_akhir }}" 
                                                            data-umur="{{ $bak->umur_real }}">
                                                        {{ $bak->uraian }} (Stok: {{ number_format($bak->stok_akhir, 0) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="maturasi[0][berat]" class="form-control form-control-sm input-berat" step="0.01" placeholder="0"></td>
                                        <td><input type="number" name="maturasi[0][umur]" class="form-control form-control-sm input-umur" placeholder="0" readonly></td>
                                        <td></td> 
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
                                <div class="col-5"><input type="number" name="trolly_keluar" class="form-control form-control-sm"></div>
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
                                <div class="col-5"><input type="number" name="jumlah_bales" id="inputBales" class="form-control form-control-sm calc-trigger" step="1"></div>
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
                                <div class="col-5"><input type="number" name="kg_sir20" class="form-control form-control-sm"></div>
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

                            <label class="text-muted small font-weight-bold">DETAIL PACKING</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Jml Pallet Diisi</label>
                                <div class="col-5"><input type="number" name="jml_pallet" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">SW</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Nomor</label>
                                <div class="col-5"><input type="number" name="total_nomor" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
                            </div>
                             <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">MC</label>
                                <div class="col-5"><input type="number" name="mc_val" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">MC</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Nomor</label>
                                <div class="col-2 pr-0"><input type="text" name="nomor_start" class="form-control form-control-sm"></div>
                                <div class="col-1 text-center small px-0">s/d</div>
                                <div class="col-2 pl-0"><input type="text" name="nomor_end" class="form-control form-control-sm"></div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Akhir</label>
                                <div class="col-5"><input type="number" name="total_nomor_akhir" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
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
<div class="modal fade" id="modalEditLaporan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form action="#" method="POST" id="formEditProduksi">
                @csrf
                @method('PUT') 
                
                <div class="modal-header bg-success text-white py-2"> 
                    <h5 class="modal-title fw-bold" style="font-size: 1.1rem;">Edit Laporan Produksi SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body pt-2">
                    
                    {{-- A. HEADER --}}
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

                    {{-- B. TABEL MATURASI (EDIT) --}}
                    <h6 class="font-weight-bold text-warning border-bottom pb-1">1. DATA MATURASI</h6>
                    <div class="row mb-3">
                        <div class="col-12">
                            <table class="table table-sm table-borderless table-maturasi mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="30%">Ruang Maturasi</th>
                                        <th width="30%">Berat Remahan (Kg)</th>
                                        <th width="30%">Umur (Hari)</th>
                                        <th>
                                            <button type="button" class="btn btn-warning btn-xs btn-block text-white" id="btnAddMaturasiEdit">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="maturasiContainerEdit">
                                    {{-- Isi via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- C. GRID UTAMA --}}
                    <div class="row">
                        <div class="col-lg-6 pr-lg-4" style="border-right: 1px solid #eee;">
                            
                            {{-- OPERASIONAL --}}
                            <h6 class="font-weight-bold text-warning border-bottom pb-1 mb-3">2. OPERASIONAL MESIN</h6>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Start Dryer</label>
                                <div class="col-4"><input type="time" name="jam_start_dryer" id="edit_jam_start" class="form-control form-control-sm"></div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Trolly Masuk</label>
                                <div class="col-4"><input type="number" name="trolly_masuk" id="edit_trolly_masuk" class="form-control form-control-sm" step="1"></div>
                            </div>

                            <label class="text-muted small font-weight-bold">AKTUAL TEMPERATURE</label>
                            {{-- Note: name tetap sama agar Controller bisa baca --}}
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
                                <div class="col-5"><input type="number" name="trolly_keluar" id="edit_trolly_keluar" class="form-control form-control-sm" step="1"></div>
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
                            
                            {{-- HASIL PRODUKSI --}}
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jumlah Bales</label>
                                <div class="col-5"><input type="number" name="jumlah_bales" id="edit_bales" class="form-control form-control-sm calc-trigger-edit" step="1"></div>
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
                                <div class="col-5"><input type="number" name="kg_sir20" id="edit_kg_cake" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Bales Kontamin</label>
                                <div class="col-5"><input type="number" name="bales_kontamin" id="edit_bales_kontamin" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Bales</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Jam Ops Genset</label>
                                <div class="col-5"><input type="number" name="jam_genset" id="edit_jam_genset" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>

                            <label class="text-muted small font-weight-bold">RATA-RATA BAHAN BAKAR</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="text" id="edit_avg_solar" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="text" id="edit_avg_batubara" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="text" id="edit_avg_cangkang" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN --}}
                        <div class="col-lg-6 pl-lg-4">
                            <h6 class="font-weight-bold text-warning border-bottom pb-1 mb-3">3. LAIN-LAIN & PACKING</h6>
                            <div class="row mb-4 align-items-center">
                                <label class="col-4">Listrik PLN</label>
                                <div class="col-5"><input type="number" name="pln_kwh" id="edit_pln_kwh" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">KWH</div>
                            </div>

                            <label class="text-muted small font-weight-bold">DETAIL PACKING</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Jml Pallet Diisi</label>
                                <div class="col-5"><input type="number" name="jml_pallet" id="edit_jml_pallet" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">SW</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Nomor</label>
                                <div class="col-5"><input type="number" name="total_nomor" id="edit_total_nomor" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
                            </div>
                             <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">MC</label>
                                <div class="col-5"><input type="number" name="mc_val" id="edit_mc_val" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">MC</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Nomor</label>
                                <div class="col-2 pr-0"><input type="text" name="nomor_start" id="edit_nomor_start" class="form-control form-control-sm"></div>
                                <div class="col-1 text-center small px-0">s/d</div>
                                <div class="col-2 pl-0"><input type="text" name="nomor_end" id="edit_nomor_end" class="form-control form-control-sm"></div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Akhir</label>
                                <div class="col-5"><input type="number" name="total_nomor_akhir" id="edit_total_nomor_akhir" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-4">Update Laporan</button>
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
        
        // --- Notifikasi ---
        @if (session('success')) Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 }); @endif
        @if (session('error')) Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", confirmButtonText: 'Cek Kembali' }); @endif

        // ==========================================
        // 🔥 1. LOGIKA FILTER TANGGAL (Start)
        // ==========================================
        var fpMin, fpMax;

        // Fungsi Parse Tanggal (dd-mm-yyyy -> Date Object)
        function parseDMY(dateStr){
            if (!dateStr) return null;
            var parts = dateStr.split('-'); 
            if(parts.length!==3) return null; 
            return new Date(parts[2], parts[1]-1, parts[0]);
        }

        // ==========================================
        // 🔥 1. LOGIKA FILTER TANGGAL (PERBAIKAN)
        // ==========================================
        
        // Pastikan selector ID benar (#min-date dan #max-date)
        // Tambahkan opsi allowInput: true agar user bisa mengetik manual juga
        var configFlatpickr = {
            altInput: true,
            altFormat: "d/m/Y",
            dateFormat: "Y-m-d",
            // allowInput: true,
            disableMobile: "true",
            defaultDate: "today" // 🔥 INI KUNCINYA: Set default ke hari ini
        };

        var fpMin = flatpickr("#min-date", configFlatpickr);
        var fpMax = flatpickr("#max-date", configFlatpickr);

        // Custom Filtering Function DataTables
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            var min = $('#min-date').val();
            var max = $('#max-date').val();
            
            // Kolom ke-2 (Index 1) adalah Tanggal (sesuai urutan <th>)
            var tableDateStr = data[1] || ''; 
            
            if (!tableDateStr || tableDateStr === '-') return true; 
            
            var tableDate = parseDMY(tableDateStr); 
            if (!tableDate) return true;

            var minDate = min ? new Date(min + 'T00:00:00') : null;
            var maxDate = max ? new Date(max + 'T23:59:59') : null;

            if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) {
                return true;
            }
            return false;
        });

        // ==========================================
        // 🔥 LOGIKA FILTER TANGGAL (End)
        // ==========================================

        // --- 2. Inisialisasi DataTable ---
        var table = $('#dataTable').DataTable({ 
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            "order": [[1,"desc"]] 
        });

        // Event Listener Tombol Filter
        $('#filter-btn').on('click', function(e){ 
            e.preventDefault(); 
            table.draw(); 
        });

        // Event Listener Tombol Reset
        $('#reset-filter').on('click', function(e){
            e.preventDefault(); 
            fpMin.clear(); 
            fpMax.clear();
            table.draw();
        });

        // --- A. PERSIAPAN DATA DROPDOWN ---
        var optionsMaturasi = '<option value="">- Pilih Ruang -</option>';
        @foreach($bak_aktif as $bak)
            optionsMaturasi += `<option value="{{ $bak->uraian }}" data-berat="{{ $bak->stok_akhir }}" data-umur="{{ $bak->umur_real }}">{{ $bak->uraian }} (Stok: {{ number_format($bak->stok_akhir, 0, ",", ".") }})</option>`;
        @endforeach

        // --- B. LOGIKA ISI BERAT & UMUR OTOMATIS ---
        $(document).on('change', '.select-ruang', function() {
            var selectedOption = $(this).find(':selected');
            var row = $(this).closest('tr');
            if(selectedOption.data('berat') !== undefined) {
                row.find('.input-berat').val(selectedOption.data('berat'));
                row.find('.input-umur').val(selectedOption.data('umur'));
                calculateTotals();
            } else {
                row.find('.input-berat').val(''); row.find('.input-umur').val(''); calculateTotals();
            }
        });

        // --- C. LOGIKA TAMBAH BARIS ---
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

        // --- D. LOGIKA HITUNGAN (Sama seperti sebelumnya) ---
        function calculateTotals() {
            var totalRemahan = 0;
            $('.input-berat').each(function() { 
                var val = parseFloat($(this).val()) || 0;
                totalRemahan += val; 
            });
            $('#bigTotalRemahan').text(Math.ceil(totalRemahan).toLocaleString('id-ID'));

            var bales = parseFloat($('#inputBales').val()) || 0;
            var kgPress = Math.ceil(bales * 35); 
            
            $('#outKgPress').val(kgPress);
            $('#bigTotalProduksi').text(kgPress.toLocaleString('id-ID'));

            var jamJalan = parseFloat($('#inputJamJalan').val()) || 0;
            var jamKerja = parseFloat($('#inputJamKerja').val()) || 0;
            
            var capacity = jamJalan > 0 ? Math.ceil(kgPress / jamJalan) : 0;
            var productivity = jamKerja > 0 ? Math.ceil(kgPress / jamKerja) : 0;

            $('#outCapacity').val(capacity);
            $('#outProductivity').val(productivity);

            var solar = parseFloat($('#inputSolar').val()) || 0;
            var batubara = parseFloat($('#inputBatubara').val()) || 0;
            var cangkang = parseFloat($('#inputCangkang').val()) || 0;

            $('#avgSolar').val(kgPress > 0 ? (solar/kgPress).toFixed(4) : 0);
            $('#avgBatubara').val(kgPress > 0 ? (batubara/kgPress).toFixed(4) : 0);
            $('#avgCangkang').val(kgPress > 0 ? (cangkang/kgPress).toFixed(4) : 0);
        }
        $(document).on('input', '.calc-trigger, .input-berat, input[name="jam_start_dryer"], input[name="jam_stop_dryer"]', calculateTotals);

        // --- E. LOGIKA TOMBOL DETAIL (VIEW) ---
        $(document).on('click', '.btn-detail', function() {
            var id = $(this).data('id');
            var url = "{{ url('produksi-sir20') }}/" + id;

            // Swal.fire({ title: 'Sedang Memuat...', didOpen: () => Swal.showLoading() });

            $.ajax({
                url: url, type: "GET", dataType: "JSON",
                success: function(data) {
                    Swal.close();
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

                    var arrSuhu = data.aktual_temperature || data.aktualTemperature || [];
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

                    var arrBB = data.bahan_bakar || data.bahanBakar || [];
                    var htmlBB = '';
                    if (Array.isArray(arrBB) && arrBB.length > 0) {
                        $.each(arrBB, function(i, val) { htmlBB += `<tr><td>${val.bahan_bakar}</td><td class="text-right">${fmt(val.digunakan)}</td></tr>`; });
                    } else { htmlBB = '<tr><td colspan="2" class="text-center">-</td></tr>'; }
                    $('#detTabelBB').html(htmlBB);

                    $('#detPallet').text(data.jumlah_pallet || 0);
                    $('#detNomor').text(`${data.nomor_start || ''} s/d ${data.nomor_end || ''}`);
                    $('#detTotalAkhir').text(data.total_nomor_akhir || 0);

                    $('#modalDetailData').modal('show');
                },
                error: function(xhr) {
                    Swal.close(); Swal.fire('Gagal', 'Gagal mengambil data.', 'error');
                }
            });
        });

        // --- TAMBAHAN: LOGIKA JAM JALAN OTOMATIS ---
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
        // 🔥 LOGIKA MODAL EDIT (LENGKAP)
        // ==========================================
        var maturasiEditIndex = 0;

        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            var urlShow = "{{ url('produksi-sir20') }}/" + id; // Endpoint ambil data (sama dgn detail)
            var urlUpdate = "{{ url('produksi-sir20') }}/" + id; // Endpoint update

            // Swal.fire({ title: 'Memuat Data Edit...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                url: urlShow, type: "GET", dataType: "JSON",
                success: function(data) {
                    Swal.close();
                    
                    // 1. Set Action Form Update
                    $('#formEditProduksi').attr('action', urlUpdate);

                    // 2. Isi Header
                    $('#edit_tanggal_produksi').val(data.tanggal_produksi);
                    $('#edit_shift_kerja').val(data.shift_kerja);

                    // 3. Isi Tabel Maturasi (Dynamic Rows)
                    var htmlMaturasi = '';
                    maturasiEditIndex = 0;
                    
                    if (data.remahan && data.remahan.length > 0) {
                        $.each(data.remahan, function(i, val) {
                            htmlMaturasi += `<tr>
                                <td>
                                    <select name="maturasi[${maturasiEditIndex}][ruang]" class="form-control form-control-sm select-ruang-edit" required>
                                        ${optionsMaturasi} 
                                    </select>
                                </td>
                                <td><input type="number" name="maturasi[${maturasiEditIndex}][berat]" class="form-control form-control-sm input-berat-edit" step="0.01" value="${val.berat}"></td>
                                <td><input type="number" name="maturasi[${maturasiEditIndex}][umur]" class="form-control form-control-sm input-umur-edit" readonly value="${val.umur}"></td>
                                <td><button type="button" class="btn btn-danger btn-xs btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
                            </tr>`;
                            maturasiEditIndex++;
                        });
                    } else {
                        // Baris kosong jika tidak ada data (fallback)
                        htmlMaturasi += `<tr>
                            <td><select name="maturasi[0][ruang]" class="form-control form-control-sm select-ruang-edit" required>${optionsMaturasi}</select></td>
                            <td><input type="number" name="maturasi[0][berat]" class="form-control form-control-sm input-berat-edit" step="0.01"></td>
                            <td><input type="number" name="maturasi[0][umur]" class="form-control form-control-sm input-umur-edit" readonly></td>
                            <td><button type="button" class="btn btn-danger btn-xs btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
                        </tr>`;
                        maturasiEditIndex = 1;
                    }
                    $('#maturasiContainerEdit').html(htmlMaturasi);

                    // Set value dropdown maturasi (karena HTML baru digenerate, valuenya harus diset manual)
                    if (data.remahan) {
                        $.each(data.remahan, function(i, val) {
                            $('#maturasiContainerEdit tr').eq(i).find('select').val(val.ruang_maturasi);
                        });
                    }

                    // 4. Isi Operasional
                    $('#edit_jam_start').val(data.jam_start_dryer);
                    $('#edit_trolly_masuk').val(data.jumlah_trolly_masuk);
                    $('#edit_trolly_keluar').val(data.jumlah_trolly_keluar);
                    $('#edit_jam_stop').val(data.jam_stop_dryer);
                    $('#edit_jam_jalan').val(data.jumlah_jam_dryer);

                    // 5. Isi Temperature & Cycle (Perlu Filter Array)
                    var temps = data.aktual_temperature || [];
                    var b1 = temps.find(t => t.jenis === 'Burner 1');
                    var b2 = temps.find(t => t.jenis === 'Burner 2');
                    var cy = temps.find(t => t.jenis === 'Cycle Time');

                    if(b1) { $('#edit_temp_b1_start').val(b1.nilai_start); $('#edit_temp_b1_end').val(b1.nilai_end); }
                    if(b2) { $('#edit_temp_b2_start').val(b2.nilai_start); $('#edit_temp_b2_end').val(b2.nilai_end); }
                    if(cy) { $('#edit_cycle_start').val(cy.nilai_start); $('#edit_cycle_end').val(cy.nilai_end); }

                    // 6. Isi Bahan Bakar (Perlu Filter Array)
                    var fuels = data.bahan_bakar || [];
                    var solar = fuels.find(f => f.bahan_bakar === 'Solar');
                    var batu = fuels.find(f => f.bahan_bakar === 'Batu Bara');
                    var cangkang = fuels.find(f => f.bahan_bakar === 'Cangkang');

                    if(solar) $('#edit_bb_solar').val(solar.digunakan);
                    if(batu) $('#edit_bb_batubara').val(batu.digunakan);
                    if(cangkang) $('#edit_bb_cangkang').val(cangkang.digunakan);

                    // 7. Isi Hasil Produksi
                    $('#edit_bales').val(data.jumlah_bales_dipress);
                    $('#edit_kg_press').val(data.kg_yang_dipress); // Ini nanti dihitung ulang otomatis
                    $('#edit_jam_kerja').val(data.jam_kerja);
                    $('#edit_kg_cake').val(data.kg_cake); // Sesuai field controller
                    $('#edit_bales_kontamin').val(data.bales_terkontaminasi);
                    $('#edit_jam_genset').val(data.jam_operasional_genset);

                    // 8. Isi Packing & Lainnya
                    $('#edit_pln_kwh').val(data.pemakaian_listrik_pln);
                    $('#edit_jml_pallet').val(data.jumlah_pallet);
                    $('#edit_total_nomor').val(data.total_nomor);
                    $('#edit_mc_val').val(data.mc_val);
                    $('#edit_nomor_start').val(data.nomor_start);
                    $('#edit_nomor_end').val(data.nomor_end);
                    $('#edit_total_nomor_akhir').val(data.total_nomor_akhir);

                    // Panggil fungsi hitung agar Capacity, Productivity, & Avg Fuel terisi
                    calculateTotalsEdit();

                    // Tampilkan Modal
                    $('#modalEditLaporan').modal('show');
                },
                error: function() {
                    Swal.close(); Swal.fire('Error', 'Gagal mengambil data edit.', 'error');
                }
            });
        });

        // --- TAMBAH BARIS DI MODAL EDIT ---
        $('#btnAddMaturasiEdit').click(function() {
            var html = `<tr>
                <td><select name="maturasi[${maturasiEditIndex}][ruang]" class="form-control form-control-sm select-ruang-edit" required>${optionsMaturasi}</select></td>
                <td><input type="number" name="maturasi[${maturasiEditIndex}][berat]" class="form-control form-control-sm input-berat-edit" step="0.01"></td>
                <td><input type="number" name="maturasi[${maturasiEditIndex}][umur]" class="form-control form-control-sm input-umur-edit" readonly></td>
                <td><button type="button" class="btn btn-danger btn-xs btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
            </tr>`;
            $('#maturasiContainerEdit').append(html);
            maturasiEditIndex++;
        });

        // --- LOGIKA DROPDOWN DI MODAL EDIT ---
        $(document).on('change', '.select-ruang-edit', function() {
            var selectedOption = $(this).find(':selected');
            var row = $(this).closest('tr');
            if(selectedOption.data('berat') !== undefined) {
                row.find('.input-berat-edit').val(selectedOption.data('berat'));
                row.find('.input-umur-edit').val(selectedOption.data('umur'));
            } else {
                row.find('.input-berat-edit').val(''); 
                row.find('.input-umur-edit').val('');
            }
            // Note: Tidak perlu hitung total remahan di edit karena tidak ada field total remahan di form edit
        });

        // --- LOGIKA HITUNGAN KHUSUS EDIT ---
        function calculateTotalsEdit() {
            var bales = parseFloat($('#edit_bales').val()) || 0;
            var kgPress = Math.ceil(bales * 35);
            $('#edit_kg_press').val(kgPress);

            var jamJalan = parseFloat($('#edit_jam_jalan').val()) || 0;
            var jamKerja = parseFloat($('#edit_jam_kerja').val()) || 0;

            var capacity = jamJalan > 0 ? Math.ceil(kgPress / jamJalan) : 0;
            var productivity = jamKerja > 0 ? Math.ceil(kgPress / jamKerja) : 0;

            $('#edit_capacity').val(capacity);
            $('#edit_produktivitas').val(productivity);

            var solar = parseFloat($('#edit_bb_solar').val()) || 0;
            var batubara = parseFloat($('#edit_bb_batubara').val()) || 0;
            var cangkang = parseFloat($('#edit_bb_cangkang').val()) || 0;

            $('#edit_avg_solar').val(kgPress > 0 ? (solar/kgPress).toFixed(4) : 0);
            $('#edit_avg_batubara').val(kgPress > 0 ? (batubara/kgPress).toFixed(4) : 0);
            $('#edit_avg_cangkang').val(kgPress > 0 ? (cangkang/kgPress).toFixed(4) : 0);
        }

        // Trigger hitung saat input berubah
        $(document).on('input', '.calc-trigger-edit', calculateTotalsEdit);

        // --- LOGIKA JAM OTOMATIS EDIT ---
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
    });
</script>

</body>
</html>