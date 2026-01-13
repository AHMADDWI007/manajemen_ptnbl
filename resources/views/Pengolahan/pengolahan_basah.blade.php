<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pengolahan Basah</title>
    {{-- Impor CSS untuk DataTables dan Flatpickr (Date Picker) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- Impor SweetAlert2 untuk notifikasi --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* (Style Anda tidak berubah) */
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; text-align: center; }
        #dataTable th, #dataTable td, #summaryTable th, #summaryTable td { text-align: center !important; vertical-align: middle !important; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
        tfoot tr, thead tr { background-color: #f8f9fa; font-weight: bold; }
        .total-label { text-align: right !important; font-weight: bold; }
        .rekap-table { text-align: center; vertical-align: middle; }
        .rekap-table th { background-color: #f8f9fa; }
        /* ✅ PERBAIKAN: Memaksa SEMUA header di tabel rekap rata TENGAH */
        .rekap-table thead th { vertical-align: middle !important; }
        .rekap-table .text-left { text-align: left !important; }
        .rekap-table .indent { padding-left: 2.5em !important; }
        .rekap-table .font-bold { font-weight: bold; }
        /* ✅ PERBAIKAN: Style untuk merapikan info rekap (PKR, Bulan, Hari) */
        .info-rekap div { line-height: 1.4; }
        .info-rekap strong { display: inline-block; font-weight: bold; }
        .info-rekap .label { width: 80px; } /* Atur lebar label */
        .info-rekap .colon { width: 10px; }  /* Atur lebar titik dua */
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
                        <form action="{{ route('pengolahan_basah.index') }}" method="GET" class="form-inline mb-3 justify-content-start align-items-center">
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
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="min-date">Dari Tanggal:</label>
                                <input type="text" id="min-date" class="form-control form-control-sm" placeholder="Pilih tanggal...">
                            </div>
                            <div class="col-md-3">
                                <label for="max-date">Sampai Tanggal:</label>
                                <input type="text" id="max-date" class="form-control form-control-sm" placeholder="Pilih tanggal...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button id="filter-btn" class="btn btn-primary btn-sm">Filter</button>&nbsp;
                                <button id="reset-filter" class="btn btn-secondary btn-sm">Reset</button>
                            </div>
                        </div>
                        <hr>
                        
                        {{-- Tabel Utama (DataTables) --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="dataTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>No</th> <th>Tanggal</th> <th>Bak Maturasi</th> <th>Jenis</th>
                                        <th>Berat Truck (Kg)</th> <th>Berat Timbang (Kg)</th> <th>Netto Basah (Kg)</th>
                                        <th>K3%</th> <th>Netto Kering (Kg)</th> <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Loop data dari $data_pengolahan (Controller) --}}
                                    @forelse ($data_pengolahan as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                            <td>{{ $item->bak_maturasi ?? '-' }}</td>
                                            <td>{{ $item->jenis ?? '-' }}</td>
                                            <td>{{ number_format($item->berat_truck, 0) }}</td>
                                            <td>{{ number_format($item->berat_timbang, 0) }}</td>
                                            <td>{{ number_format($item->netto_basah, 0) }}</td>
                                            <td>{{ is_numeric($item->k3) ? number_format($item->k3, 0) : '-' }}</td>
                                            <td>{{ is_numeric($item->netto_kering) ? number_format($item->netto_kering, 0) : '-' }}</td>
                                            <td>
                                                <div class="action-buttons">
                                                    {{-- Tombol Aksi: Detail, Edit, Hapus --}}
                                                    <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"> <i class="fas fa-eye"></i> </button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"> <i class="fas fa-edit"></i> </button>
                                                    <form action="{{ route('pengolahan_basah.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block; margin:0;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus"> <i class="fas fa-trash"></i> </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center text-muted">Belum ada data pengolahan basah.</td></tr>
                                    @endforelse
                                </tbody>
                                {{-- ✅ PERBAIKAN KRITIS: Sembunyikan TFOOT jika data kosong untuk mencegah DataTables error --}}
                                <tfoot>
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
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <form action="{{ route('pengolahan_basah.store') }}" method="POST">
                 @csrf
                 <div class="modal-header bg-success text-white">
                     <h5 class="modal-title fw-bold">Tambah Pengolahan Basah</h5>
                     <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                 </div>
                 <div class="modal-body">
                     <div class="form-group">
                         <label>Tanggal</label>
                         <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                     </div>
                     <div class="form-group">
                         <label>Berat Truck (Kg)</label>
                         <input type="number" name="berat_truck" id="add_berat_truck" class="form-control" step="0.01" value="{{ old('berat_truck') }}" required>
                     </div>
                     <div class="form-group">
                         <label>Berat Timbang (Kg)</label>
                         <input type="number" name="berat_timbang" id="add_berat_timbang" class="form-control" step="0.01" value="{{ old('berat_timbang') }}" required>
                     </div>
                     <hr>
                     <div class="form-group">
                         <label>Netto Basah (Kg)</label>
                         <input type="number" id="add_netto_basah" class="form-control" step="0.01" readonly style="background-color: #e9ecef;">
                     </div>
                      <div class="form-group">
                         <label>Jenis</label>
                         <select name="jenis" class="form-control" required>
                             <option value="">-- Pilih Jenis --</option>
                             <option value="PT" {{ old('jenis') == 'PT' ? 'selected' : '' }}>PT</option>
                             <option value="DS" {{ old('jenis') == 'DS' ? 'selected' : '' }}>DS</option>
                             <option value="INHUT" {{ old('jenis') == 'INHUT' ? 'selected' : '' }}>INHUT</option>
                         </select>
                     </div>
                      <div class="form-group">
                         <label>Bak Maturasi</label>
                         <select name="bak_maturasi" class="form-control" required>
                             <option value="">-- Pilih Bak --</option>
                             @for ($i = 1; $i <= 49; $i++)
                                 @php $bakName = "Bak Maturasi " . $i; @endphp
                                 <option value="{{ $bakName }}" {{ old('bak_maturasi') == $bakName ? 'selected' : '' }}>{{ $bakName }}</option>
                             @endfor
                         </select>
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                     <button type="submit" class="btn btn-success">Simpan</button>
                 </div>
             </form>
         </div>
     </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <form id="formEdit" method="POST">
                 @csrf
                 @method('PUT')
                 <div class="modal-header bg-success text-white">
                     <h5 class="modal-title">Edit Pengolahan Basah</h5>
                     <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                 </div>
                 <div class="modal-body">
                     <div class="form-group">
                         <label>Tanggal</label>
                         <input type="date" name="tanggal" id="editTanggal" class="form-control" required>
                     </div>
                     <div class="form-group">
                         <label>Bak Maturasi</label>
                         <select name="bak_maturasi" id="editBakMaturasi" class="form-control" required>
                             <option value="">-- Pilih Bak --</option>
                             @for ($i = 1; $i <= 49; $i++)
                                 @php $bakName = "Bak Maturasi " . $i; @endphp
                                 <option value="{{ $bakName }}">{{ $bakName }}</option>
                             @endfor
                         </select>
                     </div>
                     <div class="form-group">
                         <label>Jenis</label>
                         <select name="jenis" id="editJenis" class="form-control" required>
                             <option value="">-- Pilih Jenis --</option>
                             <option value="PT">PT</option>
                             <option value="DS">DS</option>
                             <option value="INHUT">INHUT</option>
                         </select>
                     </div>
                      <div class="form-group">
                         <label>Berat Truck (Kg)</label>
                         <input type="number" name="berat_truck" id="editBeratTruck" class="form-control" step="0.01" required>
                     </div>
                     <div class="form-group">
                         <label>Berat Timbang (Kg)</label>
                         <input type="number" name="berat_timbang" id="editBeratTimbang" class="form-control" step="0.01" required>
                     </div>
                     <hr>
                     <div class="form-group">
                         <label>Netto Basah (Kg)</label>
                         <input type="number" id="editNettoBasah" class="form-control" step="0.01" readonly style="background-color: #e9ecef;">
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
                        {{-- Ambil tanggal dari filter ringkasan untuk default --}}
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
                                
                                {{-- ✅ PERBAIKAN: Diberi <br> agar "enter" --}}
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

{{-- 
======================================================================
SKRIP-SKRIP JAVASCRIPT
======================================================================
--}}

{{-- Include Script Wajib (Template, jQuery, Bootstrap, DataTables, Flatpickr) --}}
@include('template.script')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Menjalankan kode setelah semua elemen halaman selesai dimuat
$(document).ready(function() {
    
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
                
                // Fungsi helper untuk menghitung total berdasarkan jenis
                var totalByJenis = function (jenis) {
                    
                    // 🚀 PERBAIKAN: Gunakan map() untuk membersihkan dan mengonversi string ke angka
                    // Sebelum data masuk ke reduce, kita pastikan data tersebut murni angka
                    var dataNettoKering = api.rows({ filter: 'applied' }).data()
                        .filter(function(row) {
                            // Filter baris berdasarkan Jenis (indeks 3)
                            return row[3] && row[3].trim() === jenis; 
                        })
                        .map(function(row) {
                            // 💥 KONVERSI KRITIS: Ambil nilai Netto Kering (indeks 8)
                            var nettoKeringStr = row[8] || '0';
                            
                            // Hapus semua karakter non-digit (menghilangkan koma/titik ribuan)
                            var numericValue = nettoKeringStr.toString().replace(/[^0-9]/g, ''); 
                            
                            // Konversi ke integer (diasumsikan netto kering adalah bilangan bulat ribuan)
                            return parseInt(numericValue) || 0; 
                        }).toArray(); // Ubah ke array agar bisa di-reduce

                    // Lakukan penjumlahan pada array angka yang sudah bersih
                    return dataNettoKering.reduce(function (a, b) {
                        return a + b;
                    }, 0);
                };

                // Hitung total per Jenis
                var totalDs = totalByJenis('DS');
                var totalPt = totalByJenis('PT');
                var totalInhut = totalByJenis('INHUT');
                var totalSemua = totalDs + totalPt + totalInhut;

                // Update display footer dengan presisi 0
                $('#total_ds_netto_kering_display').html(formatNumber(totalDs, 0));
                $('#total_pt_netto_kering_display').html(formatNumber(totalPt, 0));
                $('#total_inhut_netto_kering_display').html(formatNumber(totalInhut, 0));
                $('#jumlah_netto_kering_display').html(formatNumber(totalSemua, 0));
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

    // Format tanggal (sisanya tetap sama)
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
    
    function hitungNettoTambah() {
        var truck = parseFloat($('#add_berat_truck').val()) || 0;
        var timbang = parseFloat($('#add_berat_timbang').val()) || 0;
        var netto_basah = timbang > truck ? timbang - truck : 0;
        $('#add_netto_basah').val(netto_basah.toFixed(2));
    }
    $('#add_berat_truck, #add_berat_timbang').on('input', hitungNettoTambah);

    function hitungNettoEdit() {
        var truck = parseFloat($('#editBeratTruck').val()) || 0;
        var timbang = parseFloat($('#editBeratTimbang').val()) || 0;
        var netto_basah = timbang > truck ? timbang - truck : 0;
        $('#editNettoBasah').val(netto_basah.toFixed(2));
    }
    $(document).on('input', '#editBeratTruck, #editBeratTimbang', hitungNettoEdit);

    // 9. AJAX UNTUK MODAL (Detail & Edit) (Tetap sama)
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id');
        var url = "{{ url('pengolahan_basah') }}/" + id;
        
        $.get(url, function(data){
            $('#detailTanggal').text(formatTanggalDetail(data.tanggal));
            $('#detailBakMaturasi').text(data.bak_maturasi ?? '-');
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
        var urlGet = "{{ url('pengolahan_basah') }}/" + id + "/edit";
        var urlPost = "{{ url('pengolahan_basah') }}/" + id;
        
        $.get(urlGet, function(data){
            $('#editTanggal').val(data.tanggal);
            $('#editBakMaturasi').val(data.bak_maturasi);
            $('#editJenis').val(data.jenis);
            $('#editBeratTruck').val(data.berat_truck);
            $('#editBeratTimbang').val(data.berat_timbang);
            hitungNettoEdit();
            
            $('#formEdit').attr('action', urlPost);
            $('#modalEdit').modal('show');
        }).fail(function(){ 
            alert('Gagal memuat data edit.'); 
        });
    });
    
    // ✅ 10. LOGIKA MODAL INPUT REKTIF
    
    $('#btnInputRektif').on('click', function() {
        // Set tanggal modal rektif sesuai dengan tanggal filter ringkasan
        var selectedDate = $('#filter_tanggal_summary').val();
        $('#rektifTanggal').val(selectedDate);
        $('#modalInputRektif').modal('show');
    });

    $('#formInputRektif').on('submit', function(e) {
        e.preventDefault();

        // Tampilkan loading SweetAlert
        Swal.fire({
            title: 'Memperbarui Rektifikasi...',
            text: 'Mohon tunggu sebentar.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        var formData = {
            tanggal: $('#rektifTanggal').val(),
            jenis: $('#rektifJenis').val(),
            rektif: $('#rektifNilai').val()
        };

        $.ajax({
            url: "{{ route('pengolahan_basah.updateRektif') }}",
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
                    // Muat ulang halaman untuk melihat perubahan di Ringkasan Stok
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

    // 11. AJAX UNTUK MODAL REKAPITULASI (Tetap sama)
    
    function formatRekap(num) {
        num = parseFloat(num);
        if (isNaN(num) || num === 0) {
            return '-';
        }
        return num.toLocaleString('id-ID', { 
            minimumFractionDigits: 0, 
            maximumFractionDigits: 0 
        });
    }

    $('#btnDetailRekap').on('click', function() {
        Swal.fire({
            title: 'Memuat Rekapitulasi...',
            text: 'Sedang mengambil data dari API dan database.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        var selectedDate = $('#filter_tanggal_summary').val(); 
        
        $.get("{{ route('pengolahan_basah.rekap') }}", 
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
                        
                        {{-- Data sisanya dimulai dari Stok Awal --}}
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
            Swal.fire(
                'Gagal!',
                'Gagal memuat data rekapitulasi. Coba lagi nanti.',
                'error'
            );
            console.error("AJAX Error:", textStatus, errorThrown);
        });
    });
});
</script>
</body>
</html>