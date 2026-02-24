<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pengolahan Basah</title>

    {{-- 🔥 UBAH KE DATATABLES BOOTSTRAP 4 AGAR SINKRON DENGAN LAB --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Style Tabel Sinkron */
        .table-bordered th, .table-bordered td { 
            border: 1px solid #dee2e6; 
            vertical-align: middle !important; 
            white-space: nowrap; 
            text-align: center; 
        }
        
        /* Paksa header dan body rata tengah */
        #dataTable th, #dataTable td, #summaryTable th, #summaryTable td { 
            text-align: center !important; 
            vertical-align: middle !important; 
        }

        .action-buttons { display: flex; justify-content: center; gap: 5px; }
        
        /* Header Tabel Abu-abu */
        .bg-light th { background-color: #f8f9fa; font-weight: bold; }
        
        .total-label { text-align: right !important; font-weight: bold; }

        /* Style Tabel Rekap (Modal) */
        .rekap-table { text-align: center; vertical-align: middle; }
        .rekap-table th { background-color: #f8f9fa; vertical-align: middle !important; }
        .rekap-table .text-left { text-align: left !important; }
        .rekap-table .indent { padding-left: 2.5em !important; }
        .rekap-table .font-bold { font-weight: bold; }

        /* Info Rekap Header */
        .info-rekap div { line-height: 1.4; }
        .info-rekap strong { display: inline-block; font-weight: bold; }
        .info-rekap .label { width: 80px; }
        .info-rekap .colon { width: 10px; }

        /* 🔥 FIX PAGINATION BOOTSTRAP 4 (WARNA HIJAU) */
        .page-item.active .page-link {
            background-color: #28a745;
            border-color: #28a745;
        }
        .page-link { color: #28a745; }
        .page-link:hover { color: #1e7e34; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    {{-- Include Navbar dan Sidebar Template --}}
    @include('template.navbar')
    @include('template.sidebar')

    {{-- Konten Utama Halaman --}}
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Pengolahan Basah (Bokar)</h1>
                {{-- Tombol untuk memicu Modal Tambah Data --}}
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- Card 1: Ringkasan Stok --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white fw-bold"> Ringkasan Stok </div>
                    <div class="card-body">
                        
                        {{-- Form Filter Tanggal untuk Ringkasan Stok --}}
                        <form action="{{ route('pengolahan-basah.index') }}" method="GET" class="form-inline mb-3 justify-content-start align-items-center">
                            <div class="form-group d-flex align-items-center">
                                <label for="filter_tanggal_summary" class="mr-2 fw-bold">Tampilkan Ringkasan Tanggal:</label>
                                <input type="text" id="filter_tanggal_summary" name="tanggal" class="form-control form-control-sm" style="width: 150px;" placeholder="Pilih tanggal...">
                                <button type="submit" class="btn btn-success btn-sm ml-2 fw-bold">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                                
                                {{-- ✅ TAMBAHAN: Tombol untuk memicu Modal Input Rektif --}}
                                <button type="button" class="btn btn-warning btn-sm ml-2 fw-bold" id="btnInputRektif">
                                    <i class="fas fa-redo-alt"></i> Input Rektif
                                </button>

                                {{-- ✅ TOMBOL BARU: SYNC API --}}
                                <button type="button" class="btn btn-primary btn-sm ml-2 fw-bold" id="btnSyncApi">
                                    <i class="fas fa-sync-alt"></i> Sync API
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="summaryTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th rowspan="2">Stok Awal</th>
                                        <th colspan="2">Bokar Masuk</th>
                                        <th rowspan="2">Jumlah Stock Bokar</th> <th colspan="2">Bokar Diolah</th>
                                        <th rowspan="2">Stok Akhir</th>
                                    </tr>
                                    <tr>
                                        <th>Hi</th> <th>Sd.Hi</th>
                                        <th>Hi</th> <th>Sd.Hi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data ringkasan diisi dari $summary_data (Controller) --}}
                                    <tr>
                                        <td>{{ number_format($summary_data['stok_awal'], 0) }}</td>
                                        <td>{{ number_format($summary_data['masuk_hi'], 0) }}</td>
                                        <td>{{ number_format($summary_data['masuk_sdhi'], 0) }}</td>
                                        <td>{{ number_format($summary_data['jumlah_stock_bokar'], 0) }}</td> 
                                        <td>{{ number_format($summary_data['diolah_hi'], 0) }}</td>
                                        <td>{{ number_format($summary_data['diolah_sdhi'], 0) }}</td>
                                        <td>{{ number_format($summary_data['stok_akhir'], 0) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Tombol untuk memicu Modal Detail Rekapitulasi --}}
                        <div class="text-right mt-3">
                            <button class="btn btn-outline-success btn-sm fw-bold" id="btnDetailRekap">
                                <i class="fas fa-file-alt"></i> Detail Rekapitulasi
                            </button>
                        </div>

                    </div>
                </div>

                {{-- Card 2: Daftar Pengolahan Basah (DataTables) --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold"> Daftar Pengolahan Basah </div>
                    <div class="card-body">
                        {{-- Menampilkan Error Validasi --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Filter Rentang Tanggal untuk DataTables --}}
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
                        
                        {{-- Tabel Utama (DataTables) --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="dataTable" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th>No</th> 
                                        <th>Tanggal</th> 
                                        <th>Bak Maturasi</th> 
                                        <th>Jenis</th>
                                        <th>Berat Truck (Kg)</th> 
                                        <th>Berat Timbang (Kg)</th> 
                                        <th>Netto Basah (Kg)</th>
                                        <th>K3%</th> 
                                        <th>Netto Kering (Kg)</th> 
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data_pengolahan as $groupKey => $group)
                                        @php
                                            $head = $group->first(); 
                                            $jumlah_pecahan = $group->count();
                                            
                                            // Hitung Total
                                            $total_truck   = $group->sum('berat_truck');
                                            $total_timbang = $group->sum('berat_timbang');
                                            $total_netto   = $group->sum('netto_basah');
                                            $total_kering  = $group->sum('netto_kering');
                                            
                                            // List Jenis
                                            $list_jenis = $group->pluck('jenis')->unique()->implode(', ');
                                        @endphp

                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($head->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $head->maturasi->uraian ?? 'Bak Terhapus' }}</td>
                                            
                                            {{-- KOLOM JENIS --}}
                                            <td>
                                                @if($jumlah_pecahan > 1)
                                                    <span class="badge badge-info">Multi: {{ $list_jenis }}</span>
                                                @else
                                                    @if($head->jenis == 'PENDING')
                                                        <span class="badge badge-warning text-dark"><i class="fas fa-exclamation-circle"></i> {{ $head->jenis }} (Cek)</span>
                                                    @else
                                                        {{ $head->jenis }}
                                                    @endif
                                                @endif
                                            </td>

                                            {{-- Berat Truck --}}
                                            <td>{{ number_format(round($total_truck), 0, ',', '.') }}</td>

                                            {{-- Berat Timbang --}}
                                            <td>{{ number_format(round($total_timbang), 0, ',', '.') }}</td>

                                            {{-- Netto Basah (Hapus text-success jika mau, tambah round) --}}
                                            <td class="font-weight-bold">{{ number_format(round($total_netto), 0, ',', '.') }}</td>

                                            {{-- K3 (Persen biarkan ada koma karena butuh presisi) --}}
                                            <td>{{ $head->k3 ? number_format($head->k3, 2).'%' : '-' }}</td>

                                            {{-- Netto Kering (Hijau) --}}
                                            <td class="font-weight-bold text-success">{{ $total_kering > 0 ? number_format(round($total_kering), 0, ',', '.') : '-' }}</td>

                                            <td>
                                                <div class="action-buttons">
                                                    {{-- Data JSON tersembunyi untuk keperluan JavaScript --}}
                                                    <textarea class="d-none group-data-json">{{ $group->toJson() }}</textarea>
                                                    
                                                    {{-- 1. Tombol Preview (Melihat rincian) --}}
                                                    <button type="button" class="btn btn-info btn-sm btn-detail-group" 
                                                            data-bak="{{ $head->maturasi->uraian ?? '-' }}"
                                                            title="Lihat Rincian"> 
                                                        <i class="fas fa-eye"></i> 
                                                    </button>

                                                    @if($jumlah_pecahan > 1)
                                                        {{-- 🔥 TOMBOL EDIT RINCIAN (Diletakkan di Luar) --}}
                                                        <button type="button" class="btn btn-warning btn-sm btn-edit-pecahan-group" 
                                                                data-id-asal="{{ $head->id_pengolahan_basah }}"
                                                                data-netto="{{ number_format(round($total_kering), 0, '.', '') }}"
                                                                title="Edit Pembagian PT/DS/INHUT">
                                                            <i class="fas fa-edit"></i> Edit Rincian
                                                        </button>

                                                        {{-- Tombol Hapus Group --}}
                                                        <form action="{{ route('pengolahan-basah.destroy-group') }}" method="POST" class="form-hapus-group" style="display:inline;">
                                                            @csrf @method('DELETE')
                                                            <input type="hidden" name="group_ids" value="{{ json_encode($group->pluck('id_pengolahan_basah')) }}">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus Group"><i class="fas fa-trash-alt"></i></button>
                                                        </form>
                                                    @else
                                                        {{-- Aksi untuk Data Tunggal --}}
                                                        @if($head->jenis == 'PENDING' && $head->netto_kering > 0)
                                                            <button type="button" class="btn btn-primary btn-sm btn-pecah" 
                                                                    data-id="{{ $head->id_pengolahan_basah }}" 
                                                                    data-netto="{{ number_format(round($total_kering), 0, '.', '') }}"
                                                                    title="Pecah Data Menjadai Rincian"> 
                                                                <i class="fas fa-project-diagram"></i> Pecah Data
                                                            </button>
                                                        @endif

                                                        {{-- Tombol Edit Timbangan Utama (Pending Only) --}}
                                                        @if($head->jenis == 'PENDING')
                                                            <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                    data-id="{{ $head->id_pengolahan_basah }}" 
                                                                    data-maturasi-id="{{ $head->id_maturasi }}" 
                                                                    title="Edit Timbangan Utama"> 
                                                                <i class="fas fa-edit"></i> 
                                                            </button>
                                                        @endif

                                                        <form action="{{ route('pengolahan-basah.destroy', $head->id_pengolahan_basah) }}" method="POST" class="form-hapus-single" style="display:inline;">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center text-muted">Belum ada data.</td></tr>
                                    @endforelse
                                </tbody>

                                {{-- ✅ PERBAIKAN KRITIS: Sembunyikan TFOOT jika data kosong untuk mencegah DataTables error --}}
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="8"></td> 
                                        <td class="total-label">Total DS</td> 
                                        <td id="total_ds_netto_kering_display"></td> {{-- ID unik untuk diisi JS --}}
                                    </tr>
                                    <tr>
                                        <td colspan="8"></td> 
                                        <td class="total-label">Total PT</td> 
                                        <td id="total_pt_netto_kering_display"></td> {{-- ID unik untuk diisi JS --}}
                                    </tr>
                                    <tr>
                                        <td colspan="8"></td> 
                                        <td class="total-label">Total INHUT</td> 
                                        <td id="total_inhut_netto_kering_display"></td> {{-- ID unik untuk diisi JS --}}
                                    </tr>
                                    <tr>
                                        <td colspan="8"></td> 
                                        <td class="total-label">Jumlah</td> 
                                        <td id="jumlah_netto_kering_display"></td> {{-- ID unik untuk diisi JS --}}
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="main-footer"> @include('template.footer') </footer>
</div>

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
            <form action="{{ route('pengolahan-basah.store') }}" method="POST" id="formTambahSplit">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Tambah Pengolahan Basah (Multi Jenis)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Bak Maturasi</label>
                                <select name="id_maturasi" class="form-control" required>
                                    <option value="">-- Pilih Bak --</option>
                                    @foreach (\App\Models\Maturasi::orderBy('id_maturasi')->get() as $bak)
                                        <option value="{{ $bak->id_maturasi }}">{{ $bak->uraian }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 bg-light p-2 border rounded">
                            <label class="fw-bold">Data Timbangan Utama</label>
                            <div class="form-group">
                                <label>Berat Truck (Kg)</label>
                                <input type="number" name="berat_truck" id="add_berat_truck" class="form-control" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Berat Timbang (Kg)</label>
                                <input type="number" name="berat_timbang" id="add_berat_timbang" class="form-control" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Total Netto Basah (Kg)</label>
                                <input type="number" id="add_netto_basah" class="form-control font-weight-bold text-success" readonly style="background-color: #e9ecef; font-size: 1.2em;">
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <h6 class="fw-bold text-primary"><i class="fas fa-sitemap"></i> Rincian Pembagian Netto (Isi sesuai muatan)</h6>
                    <div class="alert alert-warning py-1" style="font-size: 0.9rem;">
                        Total rincian di bawah harus sama dengan <b>Total Netto Basah</b>.
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>PT (Kg)</label>
                                <input type="number" name="split_pt" class="form-control split-input" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>DS (Kg)</label>
                                <input type="number" name="split_ds" class="form-control split-input" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>INHUT (Kg)</label>
                                <input type="number" name="split_inhut" class="form-control split-input" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 p-2 rounded" id="status_split_container" style="background-color: #f8f9fa;">
                        <strong>Total Rincian: <span id="total_split_display">0</span> Kg</strong>
                        <strong id="sisa_split_display" class="text-danger">Selisih: 0 Kg</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnSimpanSplit" disabled>Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Edit Pengolahan Basah</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal" id="editTanggal" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Bak Maturasi</label>
                                <select name="id_maturasi" id="editBakMaturasi" class="form-control" required>
                                    <option value="">-- Pilih Bak --</option>
                                    @foreach (\App\Models\Maturasi::orderBy('id_maturasi')->get() as $bak)
                                        <option value="{{ $bak->id_maturasi }}">{{ $bak->uraian }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jenis</label>
                                <select name="jenis" id="editJenis" class="form-control"> {{-- Hilangkan 'required' --}}
                                    <option value="PENDING">-- PENDING --</option> {{-- Tambahkan ini --}}
                                    <option value="PT">PT</option>
                                    <option value="DS">DS</option>
                                    <option value="INHUT">INHUT</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 bg-light p-2 border rounded">
                            <label class="fw-bold text-primary">Data Timbangan</label>
                            <div class="form-group">
                                <label>Berat Truck (Kg)</label>
                                <input type="number" name="berat_truck" id="editBeratTruck" class="form-control" step="0.01" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label>Berat Timbang (Kg)</label>
                                <input type="number" name="berat_timbang" id="editBeratTimbang" class="form-control" step="0.01" placeholder="0" required>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label>Netto Basah (Kg)</label>
                                <input type="number" id="editNettoBasah" class="form-control font-weight-bold text-success" step="0.01" readonly style="background-color: #e9ecef; font-size: 1.2em;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <div class="modal-header bg-success text-white">
                 <h5 class="modal-title">Detail Pengolahan Basah</h5>
                 <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
             </div>
             <div class="modal-body">
                 <dl class="row mb-0">
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Tanggal</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailTanggal">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Bak Maturasi</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailBakMaturasi">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Jenis</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailJenis">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Berat Truck</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailBeratTruck">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Berat Timbang</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailBeratTimbang">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Netto Basah</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailNettoBasah">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>K3 (%)</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailK3">-</dd>
                     <dt class="col-sm-5 d-flex justify-content-between"><span>Netto Kering</span><span>:</span></dt>
                     <dd class="col-sm-7" id="detailNettoKering">-</dd>
                 </dl>
             </div>
         </div>
     </div>
</div>

{{-- ✅ MODAL INPUT REKTIF BARU --}}
<div class="modal fade" id="modalInputRektif" tabindex="-1" role="dialog" aria-labelledby="modalInputRektifLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formInputRektif">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold" id="modalInputRektifLabel"><i class="fas fa-redo-alt"></i> Input Rektifikasi Stok</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2" role="alert">
                        <i class="fas fa-info-circle"></i> Input rektifikasi untuk menyesuaikan stok. Gunakan nilai negatif (misal: -100) untuk mengurangi stok, dan nilai positif (misal: 100) untuk menambah stok.
                    </div>
                    <div class="form-group">
                        <label for="rektifTanggal">Tanggal Laporan</label>
                        <input type="date" id="rektifTanggal" name="tanggal" class="form-control" required readonly style="background-color: #e9ecef;">
                        <small class="form-text text-muted">Data akan direktif pada tanggal ini.</small>
                    </div>
                    <div class="form-group">
                        <label for="rektifJenis">Jenis Bokar</label>
                        <select name="jenis" id="rektifJenis" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="PT">PT</option>
                            <option value="DS">DS</option>
                            <option value="INHUT">INHUT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rektifNilai">Nilai Rektifikasi (Kg)</label>
                        <input type="number" name="rektif" id="rektifNilai" class="form-control" step="1" placeholder="Cth: 100 atau -500" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Rektif</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL REKAPITULASI (VERSI PERBAIKAN) --}}
<div class="modal fade" id="modalDetailRekap" tabindex="-1" role="dialog" aria-labelledby="modalDetailRekapLabel" aria-hidden="true">
     <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
         <div class="modal-content">
             
             <div class="modal-header bg-success text-white justify-content-center" style="position: relative;">
                 <h5 class="modal-title fw-bold">LAPORAN HARIAN</h5>
                 
                 <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1.5rem;">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
             
             <div class="modal-body" id="rekapContainer">
                 
                 <div class="text-center">
                     <h5 class="mb-0" style="font-size: 1.1rem;"> 
                         <strong>
                             REKAPITULASI LAPORAN HARIAN
                             <br>
                             PENERIMAAN BOKAR, PROSES PENGOLAHAN & PRODUKSI SIR-20
                         </strong>
                     </h5>
                 </div>
                 
                 <div class="mt-3 info-rekap">
                     <div>
                         <strong class="label">PKR</strong>
                         <strong class="colon">:</strong>
                         <span>KARANG BINTANG PT.NBL</span>
                     </div>
                     <div>
                         <strong class="label">BULAN</strong>
                         <strong class="colon">:</strong>
                         <span id="rekapBulan"></span>
                     </div>
                     <div>
                         <strong class="label">HARI/TGL</strong>
                         <strong class="colon">:</strong>
                         <span id="rekapTanggal"></span>
                     </div>
                 </div>
                 <br>

                 {{-- Tabel rekap akan di-build oleh JavaScript --}}
                 <div class="table-responsive">
                    <table class="table table-bordered table-sm rekap-table">
                        <thead class="text-center align-middle">
                            <tr>
                                <th rowspan="2">NO.</th>
                                <th rowspan="2">URAIAN</th>
                                <th rowspan="2">Stok Awal</th>
                                <th colspan="3">Penerimaan Bokar (Kg KK)</th>
                                
                                <th rowspan="2">Jumlah<br>Stock Bokar</th>
                                
                                <th colspan="2">Bokar Diproses (Kg KK)</th>
                                <th rowspan="2">Rektif</th>
                                <th rowspan="2">Stok Akhir</th>
                                <th rowspan="2">Keterangan</th>
                            </tr>
                            <tr>
                                <th>S/d kemarin</th>
                                <th>Masuk HI</th>
                                <th>S/D HI</th>
                                <th>Hari ini</th>
                                <th>S/d HI</th>
                            </tr>
                        </thead>
                        <tbody id="rekapBody"></tbody>
                        <tfoot id="rekapFooter" class="font-bold"></tfoot>
                    </table>
                 </div>
             </div>
         </div>
     </div>
</div>

{{-- MODAL PECAH DATA (SPLIT) --}}
<div class="modal fade" id="modalPecah" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('pengolahan-basah.pecahStore') }}" method="POST" id="formPecah">
                @csrf
                <input type="hidden" name="id_asal" id="pecahIdAsal">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-project-diagram"></i> Pecah Data Timbangan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> Data asli (Total) akan <strong>dihapus</strong> dan digantikan dengan rincian di bawah ini.
                    </div>

                    <div class="form-group text-center bg-light p-2 border rounded mb-3">
                        <label class="mb-0 text-muted">Target Total Netto Kering</label>
                        <div class="font-weight-bold text-dark" style="font-size: 1.5rem;">
                            <span id="pecahNettoAsalDisplay">0</span> Kg
                        </div>
                        <input type="hidden" id="pecahNettoAsal">
                    </div>
                    
                    <h6 class="fw-bold border-bottom pb-2">Rincian Pembagian Baru:</h6>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="small font-weight-bold">PT (Kg)</label>
                                <input type="number" name="split_pt" class="form-control input-pecah" placeholder="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="small font-weight-bold">DS (Kg)</label>
                                <input type="number" name="split_ds" class="form-control input-pecah" placeholder="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="small font-weight-bold">INHUT (Kg)</label>
                                <input type="number" name="split_inhut" class="form-control input-pecah" placeholder="0" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 p-2 rounded" style="background-color: #f8f9fa;">
                        <span class="small font-weight-bold">Total Terbagi: <span id="pecahTotalDisplay">0</span></span>
                        <span id="pecahSisaDisplay" class="small font-weight-bold text-danger">Kurang: 0</span>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanPecah" disabled>
                        <i class="fas fa-save"></i> Simpan Pecahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL GROUP (TABEL RINCIAN PECAHAN) --}}
{{-- MODAL DETAIL GROUP (TABEL RINCIAN + TOTAL) --}}
<div class="modal fade" id="modalDetailGroup" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-alt"></i> Rincian Data Pecahan (Grouping)</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Info Header Bak --}}
                <div class="callout callout-info py-2 mb-3 bg-light">
                    <h6 class="mb-0"><strong>Bak Maturasi:</strong> <span id="detailGroupBak" class="text-primary font-weight-bold" style="font-size: 1.1em;"></span></h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr>
                                <th style="width: 15%;">Jenis</th>
                                <th>Berat Truk (Kg)</th>
                                <th>Berat Timbang (Kg)</th>
                                <th>Netto Basah (Kg)</th>
                                <th style="width: 10%;">K3 (%)</th>
                                <th>Netto Kering (Kg)</th>
                            </tr>
                        </thead>
                        <tbody id="detailGroupBody">
                            {{-- Data akan diisi oleh JavaScript --}}
                        </tbody>
                        {{-- 🔥 INI TAMBAHANNYA: FOOTER TOTAL --}}
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td class="text-center">TOTAL</td>
                                <td id="sumTruk">0</td>
                                <td id="sumTimbang">0</td>
                                <td id="sumNetto">0</td>
                                <td>-</td>
                                <td id="sumKering" class="text-success" style="font-size: 1.1em;">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- 
======================================================================
SKRIP-SKRIP JAVASCRIPT
======================================================================
--}}

@include('template.script')
{{-- 🔥 GUNAKAN VERSI BOOTSTRAP 4 --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Menjalankan kode setelah semua elemen halaman selesai dimuat
$(document).ready(function() {

    // -----------------------------------------------------------------
    // 🔥 SWEETALERT UNTUK HAPUS GROUP (BANYAK DATA)
    // -----------------------------------------------------------------
    $(document).on('submit', '.form-hapus-group', function(e) {
        e.preventDefault(); // Tahan dulu, jangan submit formnya
        var form = this;

        Swal.fire({
            title: 'HAPUS GROUP?',
            text: "Anda akan menghapus SELURUH pecahan data (PT, DS, dll) di baris ini. Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus Semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika user klik Ya, baru submit form secara manual
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    // didOpen: () => { Swal.showLoading(); }
                });
                form.submit();
            }
        });
    });

    // -----------------------------------------------------------------
    // 🔥 SWEETALERT UNTUK HAPUS SINGLE (SATU DATA)
    // -----------------------------------------------------------------
    $(document).on('submit', '.form-hapus-single', function(e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: 'Hapus Data?',
            text: "Data ini akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
    
    // 1. NOTIFIKASI SUKSES (dari Session)
    @if (session('success'))
        Swal.fire({ 
            icon: 'success', 
            title: 'Berhasil!', 
            text: "{{ session('success') }}", 
            showConfirmButton: false, 
            timer: 2000 
        });
    @endif

    // 2. INISIALISASI FLATPICKR (Date Picker)
    var fpMin = flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });
    var fpMax = flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });
    flatpickr("#filter_tanggal_summary", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "{{ $today->format('Y-m-d') }}" });
    
    // ✅ TAMBAHAN: Inisialisasi Flatpickr untuk Modal Rektif
    flatpickr("#rektifTanggal", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "{{ $today->format('Y-m-d') }}" });


    // 3. LOGIKA FILTER DATA TABLES (Tabel Bawah)
    function parseDMY(dateStr){
        var parts = dateStr.split('-'); 
        if(parts.length !== 3) return null; 
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var min = $('#min-date').val(); var max = $('#max-date').val();       
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

    // 4. INISIALISASI DATA TABLES
    @if ($data_pengolahan->count() > 0)
        var table = $('#dataTable').DataTable({
            "order": [[1, "desc"]], 
            
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api();
                
                // 1. Siapkan variabel penampung
                var totalDS = 0;
                var totalPT = 0;
                var totalINHUT = 0;
                var grandTotal = 0;

                // 2. Loop semua baris yang TAMPIL (setelah difilter)
                api.rows({ search: 'applied' }).nodes().each(function(row, index) {
                    
                    // 3. Ambil data JSON tersembunyi dari baris tersebut
                    var jsonVal = $(row).find('.group-data-json').val();
                    
                    if (jsonVal) {
                        var groupData = JSON.parse(jsonVal);
                        
                        // 4. Loop data pecahan di dalam JSON (PT, DS, INHUT)
                        groupData.forEach(function(item) {
                            // Pastikan ambil Netto Kering, ubah ke Float, jika null jadi 0
                            var berat = Math.round(parseFloat(item.netto_kering) || 0);
                            var jenis = (item.jenis || '').toUpperCase().trim();

                            // 5. Kelompokkan penjumlahan
                            if (jenis === 'DS') {
                                totalDS += berat;
                            } else if (jenis === 'PT') {
                                totalPT += berat;
                            } else if (jenis === 'INHUT') {
                                totalINHUT += berat;
                            }
                            
                            // Total Keseluruhan
                            grandTotal += berat;
                        });
                    }
                });

                // 6. Tampilkan Hasil dengan Format Ribuan Indonesia
                $('#total_ds_netto_kering_display').html(totalDS.toLocaleString('id-ID'));
                $('#total_pt_netto_kering_display').html(totalPT.toLocaleString('id-ID'));
                $('#total_inhut_netto_kering_display').html(totalINHUT.toLocaleString('id-ID'));
                $('#jumlah_netto_kering_display').html(grandTotal.toLocaleString('id-ID'));
            }
        });
        table.draw();
    @else
        function initializeDatesOnly() {
            var minDate = $('#min-date').val();
            var maxDate = $('#max-date').val();
        }
        initializeDatesOnly();
    @endif


    // 5. EVENT LISTENER TOMBOL FILTER (Tabel Bawah)
    $('#filter-btn').on('click', function(e){ 
        e.preventDefault();
        @if ($data_pengolahan->count() > 0)
            table.draw();
        @endif
    });
    
    $('#reset-filter').on('click', function(e){
        e.preventDefault();
        fpMin.setDate("today"); 
        fpMax.setDate("today");
        setTimeout(function() { 
            @if ($data_pengolahan->count() > 0)
                table.search('').draw(); 
            @endif
        }, 100);
    });

    // 6. SETUP AJAX
    $.ajaxSetup({ 
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } 
    });

    // 7. FUNGSI HELPER FORMATTING
    function formatNumber(num, precision = 2) {
        if (num === null || num === undefined || num === '') return '-';
        num = parseFloat(num);
        if (isNaN(num)) return '-';
        return num.toLocaleString('id-ID', { 
            minimumFractionDigits: precision, 
            maximumFractionDigits: precision 
        });
    }

    function formatTanggalDetail(dateStr) {
        if (!dateStr) return '-';
        try {
            var dateObj = new Date(dateStr + 'T00:00:00');
            if (isNaN(dateObj.getTime())) return '-';
            return dateObj.toLocaleDateString('id-ID', { 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric' 
            });
        } catch (e) { return '-'; }
    }

    // 8. LOGIKA PERHITUNGAN OTOMATIS (Modal Tambah & Edit)
    function hitungSplit() {
        // 1. Hitung Netto Utama
        var truck = parseFloat($('#add_berat_truck').val()) || 0;
        var timbang = parseFloat($('#add_berat_timbang').val()) || 0;
        var netto_total = 0;

        if (timbang > truck) {
            netto_total = timbang - truck;
        }
        
        // Tampilkan Netto Total
        $('#add_netto_basah').val(netto_total.toFixed(2));

        // 2. Ambil nilai pecahan
        var pt = parseFloat($('input[name="split_pt"]').val()) || 0;
        var ds = parseFloat($('input[name="split_ds"]').val()) || 0;
        var inhut = parseFloat($('input[name="split_inhut"]').val()) || 0;

        // 3. Hitung Selisih
        var total_rincian = pt + ds + inhut;
        var selisih = netto_total - total_rincian;

        // 4. Update Tampilan Status
        $('#total_split_display').text(total_rincian.toLocaleString('id-ID'));
        
        var sisaElem = $('#sisa_split_display');
        var btnSimpan = $('#btnSimpanSplit');

        if (netto_total > 0) {
            // KONDISI A: Input Utuh (User tidak mengisi pecahan sama sekali)
            if (total_rincian === 0) {
                sisaElem.removeClass('text-danger text-success').addClass('text-warning')
                    .html('<i class="fas fa-info-circle"></i> Simpan Utuh (Belum Dipecah)');
                btnSimpan.prop('disabled', false); // BOLEH SIMPAN
            } 
            // KONDISI B: Input Pecahan (User mengisi pecahan dan PAS)
            else if (Math.abs(selisih) < 0.01) {
                sisaElem.removeClass('text-danger text-warning').addClass('text-success')
                    .html('<i class="fas fa-check-circle"></i> Pas / Balance');
                btnSimpan.prop('disabled', false); // BOLEH SIMPAN
            } 
            // KONDISI C: Input Pecahan (User mengisi tapi BELUM PAS)
            else {
                sisaElem.removeClass('text-success text-warning').addClass('text-danger')
                    .text('Selisih: ' + selisih.toFixed(2) + ' Kg');
                btnSimpan.prop('disabled', true); // TIDAK BOLEH SIMPAN
            }
        } else {
            // Belum input berat truk/timbang
            sisaElem.text('Menunggu Input Berat...');
            btnSimpan.prop('disabled', true);
        }
    }

    // Pasang Event Listener
    $('#add_berat_truck, #add_berat_timbang, .split-input').on('input keyup change', hitungSplit);

    function hitungNettoEdit() {
        var truck = parseFloat($('#editBeratTruck').val()) || 0;
        var timbang = parseFloat($('#editBeratTimbang').val()) || 0;
        var netto_basah = 0;
        
        if (timbang > truck) {
            netto_basah = timbang - truck;
        }
        
        $('#editNettoBasah').val(netto_basah.toFixed(2));
    }
    
    // Trigger saat mengetik di modal edit
    $(document).on('input', '#editBeratTruck, #editBeratTimbang', hitungNettoEdit);

    // 9. AJAX UNTUK MODAL (Detail & Edit)
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id');
        var url = "{{ url('pengolahan-basah') }}/" + id;
        
        $.get(url, function(data){
            $('#detailTanggal').text(formatTanggalDetail(data.tanggal));
            // 🔥 Perbaikan Tampilan Detail: Ambil nama bak dari relasi
            $('#detailBakMaturasi').text(data.maturasi ? data.maturasi.uraian : '-');
            $('#detailJenis').text(data.jenis ?? '-');
            $('#detailBeratTruck').text(formatNumber(data.berat_truck, 0) + ' Kg');
            $('#detailBeratTimbang').text(formatNumber(data.berat_timbang, 0) + ' Kg');
            $('#detailNettoBasah').text(formatNumber(data.netto_basah, 0) + ' Kg');
            var k3Val = formatNumber(data.k3, 0);
            $('#detailK3').text(k3Val !== '-' ? k3Val + ' %' : '-');
            $('#detailNettoKering').text(formatNumber(data.netto_kering, 0) + ' Kg');
            
            $('#modalDetail').modal('show');
        }).fail(function(){ 
            alert('Gagal memuat detail.'); 
        });
    });

    $(document).on('click','.btn-edit',function(){
        var id = $(this).data('id');
        var maturasiId = $(this).data('maturasi-id');
        
        // 🔥 PERBAIKAN UTAMA DI SINI:
        // Tutup dulu modal detail group biar tidak tabrakan/gelap
        $('#modalDetailGroup').modal('hide'); 
        
        // Setup URL
        var urlGet = "{{ url('pengolahan-basah') }}/" + id + "/edit";
        var urlPost = "{{ url('pengolahan-basah') }}/" + id;
        
        // Ambil Data via AJAX
        $.get(urlGet, function(data){
            // Isi form di Modal Edit
            $('#editTanggal').val(data.tanggal);
            $('#editBakMaturasi').val(maturasiId); 
            $('#editJenis').val(data.jenis);
            $('#editBeratTruck').val(data.berat_truck);
            $('#editBeratTimbang').val(data.berat_timbang);
            
            // Panggil fungsi hitung netto (agar field readonly terisi)
            hitungNettoEdit();
            
            // Set action form
            $('#formEdit').attr('action', urlPost);
            
            // 🔥 Trik Kecil: Beri jeda sedikit (200ms) sebelum membuka modal edit
            // Ini supaya animasi tutup modal sebelumnya selesai dulu
            setTimeout(function() {
                $('#modalEdit').modal('show');
            }, 200);
            
        }).fail(function(){ 
            alert('Gagal memuat data edit.'); 
        });
    });
    
    // ✅ 10. LOGIKA MODAL INPUT REKTIF
    $('#btnInputRektif').on('click', function() {
        var selectedDate = $('#filter_tanggal_summary').val();
        $('#rektifTanggal').val(selectedDate);
        $('#modalInputRektif').modal('show');
    });

    $('#formInputRektif').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Memperbarui Rektifikasi...',
            text: 'Mohon tunggu sebentar.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        var formData = {
            tanggal: $('#rektifTanggal').val(),
            jenis: $('#rektifJenis').val(),
            rektif: $('#rektifNilai').val()
        };

        $.ajax({
            url: "{{ route('pengolahan-basah.updateRektif') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 2500
                }).then(function() {
                    $('#modalInputRektif').modal('hide');
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('tanggal', formData.tanggal);
                    window.location.href = currentUrl.toString();
                });
            },
            error: function(xhr) {
                var message = 'Terjadi kesalahan saat menyimpan rektif.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Gagal!', message, 'error');
            }
        });
    });

    // 11. AJAX UNTUK MODAL REKAPITULASI (Logic tetap sama)
    function formatRekap(num) {
        num = parseFloat(num);
        if (isNaN(num) || num === 0) return '-';
        return num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    $('#btnDetailRekap').on('click', function() {
        Swal.fire({
            title: 'Memuat Rekapitulasi...',
            text: 'Sedang mengambil data dari API dan database.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        var selectedDate = $('#filter_tanggal_summary').val(); 
        
        $.get("{{ route('pengolahan-basah.rekap') }}", 
            { tanggal: selectedDate },
            function(response) {
                Swal.close();
                $('#rekapBulan').text(response.bulan);
                $('#rekapTanggal').text(response.hari_tanggal);
                
                var body = $('#rekapBody');
                var footer = $('#rekapFooter');
                body.empty();
                footer.empty();

                body.append(`
                    <tr class="font-bold">
                        <td>I.</td>
                        <td class="text-left" colspan="11">PENGADAAN BOKAR / Raw Material</td>
                    </tr>
                `);

                response.data.forEach(function(item, index) {
                    body.append(`
                        <tr>
                            <td>${index + 1}.</td>
                            <td class="text-left">${item.uraian || '-'}</td>
                            <td>${formatRekap(item.stok_awal)}</td>
                            <td>${formatRekap(item.penerimaan_sd_kemarin)}</td> 
                            <td>${formatRekap(item.masuk_hi)}</td>
                            <td>${formatRekap(item.penerimaan_sid_hi)}</td>
                            <td>${formatRekap(item.jumlah_stock_bokar)}</td>
                            <td>${formatRekap(item.diolah_hi)}</td>
                            <td>${formatRekap(item.diolah_sdhi)}</td>
                            <td>${formatRekap(item.rektif) || '-'}</td>
                            <td>${formatRekap(item.stok_akhir)}</td>
                            <td>${item.keterangan || '-'}</td>
                        </tr>
                    `);
                });

                var total = response.total;
                footer.append(`
                    <tr class="font-bold">
                        <td colspan="2">Total</td> 
                        <td>${formatRekap(total.stok_awal)}</td>
                        <td>${formatRekap(total.penerimaan_sd_kemarin)}</td>
                        <td>${formatRekap(total.masuk_hi)}</td>
                        <td>${formatRekap(total.penerimaan_sid_hi)}</td>
                        <td>${formatRekap(total.jumlah_stock_bokar)}</td>
                        <td>${formatRekap(total.diolah_hi)}</td>
                        <td>${formatRekap(total.diolah_sdhi)}</td>
                        <td>-</td>
                        <td>${formatRekap(total.stok_akhir)}</td>
                        <td>-</td>
                    </tr>
                `);

                $('#modalDetailRekap').modal('show');
            }
        ).fail(function(jqXHR, textStatus, errorThrown) {
            Swal.fire('Gagal!', 'Gagal memuat data rekapitulasi. Coba lagi nanti.', 'error');
            console.error("AJAX Error:", textStatus, errorThrown);
        });
    });
    
    $('#btnSyncApi').click(function(e) {
        e.preventDefault();

        // 1. Ambil tanggal dari input filter
        var selectedDate = $('#filter_tanggal_summary').val(); 

        // Validasi sederhana (opsional)
        var pesan = selectedDate ? 'Melakukan sinkronisasi data tanggal ' + selectedDate : 'Melakukan sinkronisasi data hari ini';

        Swal.fire({
            title: 'Menarik Data...',
            text: pesan,
            didOpen: () => { Swal.showLoading() }
        });

        $.ajax({
            url: "/sync-bokar-manual",
            type: "POST",
            data: { 
                _token: $('meta[name="csrf-token"]').attr('content'),
                tanggal: selectedDate // 2. Kirim tanggal ke server
            },
            success: function(response) {
                Swal.fire('Berhasil!', response.message, 'success')
                .then(() => { location.reload(); });
            },
            error: function(xhr) {
                var errorMsg = 'Gagal sinkronisasi';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire('Gagal', errorMsg, 'error');
            }
        });
    });

    // 1. Buka Modal saat tombol diklik
    $(document).on('click', '.btn-pecah', function() {
        var id = $(this).data('id');
        
        // Ambil data netto (yang sudah kita format bersih di PHP tadi)
        var rawNetto = $(this).attr('data-netto'); 
        
        // Konversi ke Float lalu Bulatkan ke Atas
        var netto = Math.round(parseFloat(rawNetto));

        // Cek di Console (Tekan F12) untuk memastikan angka masuk
        console.log("ID Data:", id);
        console.log("Netto Asli:", rawNetto);
        console.log("Target Bulat:", netto);

        $('#pecahIdAsal').val(id);
        $('#pecahNettoAsal').val(netto); // Simpan ke hidden input
        $('#pecahNettoAsalDisplay').text(netto.toLocaleString('id-ID')); // Tampilkan
        
        // Reset inputan form
        $('.input-pecah').val(''); 
        $('#pecahTotalDisplay').text('0');
        
        // Set status awal
        $('#pecahSisaDisplay').removeClass('text-success').addClass('text-danger').text('Kurang: ' + netto.toLocaleString('id-ID') + ' Kg');
        $('#btnSimpanPecah').prop('disabled', true); // Matikan tombol dulu

        // 🔥 Tambahkan ini agar judul modal balik jadi "Pecah Data" (Bukan Edit)
        $('#modalPecah .modal-title').html('<i class="fas fa-project-diagram"></i> Pecah Data Timbangan');
        $('#modalPecah .modal-header').removeClass('bg-warning text-dark').addClass('bg-primary text-white');
        $('#modalPecah #btnSimpanPecah').text('Simpan Pecahan').removeClass('btn-warning').addClass('btn-primary');
        $('#modalPecah').modal('show');
    });

    // 2. Hitung Realtime (DENGAN SELECTOR SPESIFIK #modalPecah)
    $(document).on('input keyup', '#modalPecah .input-pecah', function() {
        // Ambil target dari hidden input & PAKSA BULAT KE ATAS (Safety)
        var rawTarget = $('#pecahNettoAsal').val();
        var target = Math.round(parseFloat(rawTarget) || 0);
        
        // Jaga-jaga jika target NaN atau 0
        if (isNaN(target) || target <= 0) {
            console.error("Target Netto Error/Nol");
            $('#btnSimpanPecah').prop('disabled', true);
            return;
        }
        
        // Ambil inputan user
        var pt = parseFloat($('#modalPecah input[name="split_pt"]').val()) || 0;
        var ds = parseFloat($('#modalPecah input[name="split_ds"]').val()) || 0;
        var inhut = parseFloat($('#modalPecah input[name="split_inhut"]').val()) || 0;

        var total = pt + ds + inhut;
        var selisih = target - total;

        // Tampilkan Total
        $('#pecahTotalDisplay').text(total.toLocaleString('id-ID'));

        var sisaLabel = $('#pecahSisaDisplay');
        var btn = $('#btnSimpanPecah');

        // Debugging di Console (Tekan F12 untuk cek)
        // console.log("Target:", target, "Total Input:", total, "Selisih:", selisih);

        // Toleransi selisih 0.1 Kg
        if (Math.abs(selisih) < 0.1) {
            sisaLabel.removeClass('text-danger').addClass('text-success').html('<i class="fas fa-check-circle"></i> Pas / Balance');
            btn.prop('disabled', false); // 🔥 AKTIFKAN TOMBOL
            btn.removeClass('btn-secondary').addClass('btn-primary'); // Ubah warna jadi biru
        } else {
            if(selisih > 0) {
                sisaLabel.removeClass('text-success').addClass('text-danger').text('Kurang: ' + selisih.toLocaleString('id-ID'));
            } else {
                sisaLabel.removeClass('text-success').addClass('text-danger').text('Kelebihan: ' + Math.abs(selisih).toLocaleString('id-ID'));
            }
            btn.prop('disabled', true); // MATIKAN TOMBOL
        }
    });

    // --- LOGIKA DETAIL GROUP (Melihat Rincian Pecahan) ---
    $(document).on('click', '.btn-detail-group', function() {
        var btn = $(this);
        var bakName = btn.data('bak');
        var groupData = JSON.parse(btn.siblings('.group-data-json').val());

        $('#detailGroupBak').text(bakName);
        var tbody = $('#detailGroupBody');
        tbody.empty(); 

        var totals = { truk: 0, timbang: 0, netto: 0, kering: 0 };

        $.each(groupData, function(index, item) {
            var vTruk = Math.round(parseFloat(item.berat_truck) || 0);
            var vTimbang = Math.round(parseFloat(item.berat_timbang) || 0);
            var vNetto = Math.round(parseFloat(item.netto_basah) || 0);
            var vKering = Math.round(parseFloat(item.netto_kering) || 0);

            totals.truk += vTruk;
            totals.timbang += vTimbang;
            totals.netto += vNetto;
            totals.kering += vKering;

            tbody.append(`
                <tr>
                    <td class="font-weight-bold">${item.jenis}</td>
                    <td>${vTruk.toLocaleString('id-ID')}</td>
                    <td>${vTimbang.toLocaleString('id-ID')}</td>
                    <td class="font-weight-bold">${vNetto.toLocaleString('id-ID')}</td>
                    <td>${item.k3 ? parseFloat(item.k3).toFixed(2) + '%' : '-'}</td>
                    <td class="text-success font-weight-bold">${vKering > 0 ? vKering.toLocaleString('id-ID') : '-'}</td>
                </tr>
            `);
        });

        $('#sumTruk').text(totals.truk.toLocaleString('id-ID'));
        $('#sumTimbang').text(totals.timbang.toLocaleString('id-ID'));
        $('#sumNetto').text(totals.netto.toLocaleString('id-ID'));
        $('#sumKering').text(totals.kering > 0 ? totals.kering.toLocaleString('id-ID') : '-');

        $('#modalDetailGroup').modal('show');
    });

    $(document).on('click', '.btn-edit-pecahan-group', function() {
        var btn = $(this);
        var targetKering = Math.round(parseFloat(btn.data('netto')));
        var groupData = JSON.parse(btn.siblings('.group-data-json').val());

        // Setup Modal Pecah
        $('#pecahIdAsal').val(btn.data('id-asal'));
        $('#pecahNettoAsal').val(targetKering);
        $('#pecahNettoAsalDisplay').text(targetKering.toLocaleString('id-ID'));
        
        // Reset & Fill Inputs
        $('.input-pecah').val('');
        groupData.forEach(function(item) {
            var field = 'input[name="split_' + item.jenis.toLowerCase() + '"]';
            $(field).val(Math.round(parseFloat(item.netto_kering)));
        });

        // Trigger hitung otomatis
        $('#modalPecah .input-pecah').first().trigger('input');

        // Styling Modal Mode Edit
        $('#modalPecah .modal-title').html('<i class="fas fa-edit"></i> Perbarui Rincian Pecahan');
        $('#modalPecah .modal-header').removeClass('bg-primary').addClass('bg-warning text-dark');
        $('#modalPecah #btnSimpanPecah').text('Perbarui Data').removeClass('btn-primary').addClass('btn-warning');

        $('#modalPecah').modal('show');
    });
});
</script>
</body>
</html>