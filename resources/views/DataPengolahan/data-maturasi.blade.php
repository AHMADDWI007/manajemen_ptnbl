<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- CSS Libraries --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <title>Pengolahan Maturasi</title>
    <style>
        /* Style untuk input readonly */
        .form-control[readonly] { background-color: #e9ecef; opacity: 1; }
        
        /* --- STYLE TABEL --- */
        #dataTable th, #dataTable td { 
            border: 1px solid #dee2e6; 
            white-space: nowrap; 
            padding: 0.5rem; 
            font-size: 0.9rem;
        }

        /* Paksa Header Rata Tengah (Horizontal & Vertikal) */
        #dataTable thead th {
            text-align: center !important;    
            vertical-align: middle !important; 
            background-color: #f8f9fa; 
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Paksa Isi Body Rata Tengah (kecuali yang ada class text-left) */
        #dataTable tbody td {
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Class khusus untuk rata kiri (misal uraian) */
        #dataTable tbody td.text-left {
            text-align: left !important;
        }

        /* --- Style Detail List di Modal --- */
        .detail-list dt { display: flex; justify-content: space-between; padding-right: 0.5rem; }
        .detail-list dt::after { content: ":"; }
        .detail-list dd { text-align: left; font-weight: bold; }
        
        /* Layout Search */
        .filter-search-row .col-md-3 { margin-bottom: 1rem; }
        @media (min-width: 768px) {
            .filter-search-row { display: flex; align-items: flex-end; }
            #search-input-container { margin-left: auto; }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        {{-- Header Content --}}
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Pengolahan Maturasi</h3>
                <div> 
                    <a href="{{ route('maturasi.cetak', ['filter_tanggal' => $selected_date]) }}" 
                    target="_blank" 
                    class="btn btn-warning btn-sm fw-bold mr-2">
                        <i class="fas fa-print"></i> Cetak PDF
                    </a>

                    <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                        <i class="fas fa-plus-circle"></i> Input Diolah/Mutasi
                    </button>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong>Data Maturasi (Per: {{ \Carbon\Carbon::parse($selected_date)->isoFormat('D MMMM YYYY') }})</strong>
                        
                        <form method="GET" action="{{ route('maturasi.index') }}" class="form-inline ml-auto">
                            <label for="filter_tanggal" class="mr-2 text-white">Tanggal:</label>
                            <input type="date" name="filter_tanggal" class="form-control form-control-sm mr-2" value="{{ $selected_date ?? \Carbon\Carbon::today()->format('Y-m-d') }}">
                            <button type="submit" class="btn btn-light btn-sm">Tampilkan</button>
                        </form>
                    </div>

                    <div class="card-body">
                        {{-- Error/Success Messages --}}
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                            </div>
                        @endif
                        @if(session('edit_error'))
                            <div class="alert alert-warning mb-3">
                                Gagal menyimpan perubahan.
                                <script> $(function() { $('#modalEdit[data-error-id="{{ session('edit_id') }}"]').modal('show'); }); </script>
                            </div>
                        @endif

                        {{-- Search Input --}}
                        <div class="row mb-3 filter-search-row">
                            <div class="col-md-3" id="search-input-container">
                                <label for="searchInput">Cari (Uraian/Bak):</label>
                                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari...">
                            </div>
                        </div>
                        <hr>

                        {{-- ✅ TABEL UTAMA --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable">
                                <thead class="bg-light text-center">
                                    <tr class="text-center">
                                        <th rowspan="2" style="width: 5%">NO.</th>
                                        <th rowspan="2">URAIAN PROSES</th>
                                        
                                        <th colspan="3">Stock Awal</th>
                                        <th colspan="2">Diproses HI</th>
                                        <th colspan="1">Masuk</th>

                                        {{-- Group: Quality (Judul diperjelas bahwa ini Lab Maturasi) --}}
                                        <th colspan="3">Quality (Lab Maturasi)</th>

                                        <th rowspan="2">Stock Akhir</th>
                                        <th rowspan="2">Asal Bokar</th>
                                        <th rowspan="2">Keterangan</th>
                                        <th rowspan="2" style="width: 8%">Aksi</th>
                                    </tr>
                                    <tr>
                                        {{-- Sub Columns --}}
                                        <th>Kg KK</th>
                                        <th>Tgl</th>
                                        <th>Umur</th>
                                        <th>Diolah</th>
                                        <th>Mutasi</th>
                                        <th>HI</th>
                                        <th>K3</th>
                                        <th>Po</th>
                                        <th>PRI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($data_maturasi as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="text-left">{{ $item->uraian }}</td>
                                        
                                        {{-- Stock Awal --}}
                                        <td>{{ number_format($item->stok_awal, 0, ',', '.') }}</td>
                                        <td>{{ $item->tgl_masuk ? \Carbon\Carbon::parse($item->tgl_masuk)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->umur ?? 0 }}</td>
                                        
                                        {{-- Diproses --}}
                                        <td>{{ number_format($item->diolah, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->mutasi, 0, ',', '.') }}</td>
                                        
                                        {{-- Masuk --}}
                                        <td>{{ number_format($item->masuk_hi, 0, ',', '.') }}</td>
                                        
                                        {{-- QUALITY: Murni dari Hasil Uji Maturasi ($item->k3 di Controller sudah diperbaiki) --}}
                                        <td class="text-center font-weight-bold text-dark">
                                            {{ $item->k3_olah > 0 ? number_format($item->k3_olah, 2) : '-' }}
                                        </td>
                                        <td>{{ $item->po > 0 ? $item->po : '-' }}</td>
                                        <td>{{ $item->pri > 0 ? $item->pri : '-' }}</td>
                                        
                                        {{-- Stock Akhir --}}
                                        <td class="font-weight-bold">{{ number_format($item->stok_akhir, 0, ',', '.') }}</td>
                                        
                                        <td>{{ $item->asal_bokar ?? '-' }}</td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>

                                        {{-- Aksi --}}
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="dropdownMenu{{ $item->id_maturasi }}" data-toggle="dropdown" aria-expanded="false">
                                                    Aksi
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu{{ $item->id_maturasi }}">
                                                    <a class="dropdown-item btn-detail" href="javascript:void(0)"
                                                       data-uraian="{{ $item->uraian }}"
                                                       data-tanggal="{{ $selected_date }}">
                                                        <i class="fas fa-eye text-info mr-2"></i> Detail
                                                    </a>
                                                    <a class="dropdown-item btn-edit" href="javascript:void(0)"
                                                       data-id="{{ $item->id_maturasi }}">
                                                        <i class="fas fa-edit text-warning mr-2"></i> Edit
                                                    </a>
                                                    <form action="{{ route('maturasi.reset', $item->id_maturasi) }}" method="POST"
                                                    class="reset-form" style="display:inline;">
                                                    @csrf 
                                                        <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-undo mr-2"></i> Reset
                                                         </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>

                                {{-- FOOTER SUMMARY --}}
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="2" class="text-center">Jumlah</td>
                                        <td class="text-center">{{ number_format($footer_data['total_stok_awal'], 0, ',', '.') }}</td>
                                        <td></td> <td></td>
                                        <td class="text-center">{{ number_format($footer_data['total_diolah'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($footer_data['total_mutasi'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($footer_data['total_masuk_hi'], 0, ',', '.') }}</td>
                                        <td colspan="3"></td>
                                        <td class="text-center">{{ number_format($footer_data['total_stok_akhir'], 0, ',', '.') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-center font-weight-bold" style="font-size: 1rem;">Maturasi Diolah</td>
                                        <td colspan="2" class="text-center">s/d Kemarin</td>
                                        <td colspan="2" class="text-center" style="background-color: #92D050; color: black; font-size: 1rem;">
                                            {{ number_format($footer_data['maturasi_diolah_sd_kemarin'], 0, ',', '.') }}
                                        </td>
                                        <td colspan="1" class="text-center">Hari ini</td>
                                        <td colspan="2" class="text-center" style="font-size: 1rem;">
                                            {{ number_format($footer_data['maturasi_diolah_hari_ini'], 0, ',', '.') }}
                                        </td>
                                        <td colspan="2" class="text-center">s/d Hari ini</td>
                                        <td colspan="3" class="text-center" style="font-size: 1rem;">
                                            {{ number_format($footer_data['maturasi_diolah_sd_hari_ini'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
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

{{-- ============================================= --}}
{{-- MODAL (TAMBAH, EDIT, DETAIL) --}}
{{-- ============================================= --}}

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('maturasi.store') }}" method="POST" id="formTambah">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Input Data Diolah / Mutasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Input Harian</label>
                            <input type="date" name="tanggal_input_harian" id="tanggal_input_tambah" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Uraian (Hanya Bak Aktif)</label>
                            <select name="uraian" id="uraian" class="form-control form-control-sm" required>
                                <option value="" disabled selected>-- Pilih Bak Maturasi --</option>
                                @forelse ($bak_aktif_list as $uraian_aktif)
                                    <option value="{{ $uraian_aktif }}">{{ $uraian_aktif }}</option>
                                @empty
                                    <option value="" disabled>Tidak ada Bak dengan stok untuk tanggal ini</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stok Awal (Kg)</label>
                            <input type="text" name="stok_awal" id="stok_awal" class="form-control form-control-sm" readonly required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Umur (Hari) (Otomatis)</label>
                            <input type="number" name="umur" id="umur" class="form-control form-control-sm" readonly required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Diolah (Kg)</label>
                            <input type="number" name="diolah" id="diolah" class="form-control form-control-sm" value="0" step="0.01" min="0">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Mutasi (Kg)</label>
                            <input type="number" name="mutasi" id="mutasi" class="form-control form-control-sm" value="0" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Masuk Hari Ini (Kg)</label>
                            <input type="text" name="masuk_hi" id="masuk_hi" class="form-control form-control-sm" value="0,00" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Asal Bokar</label>
                            <select name="asal_bokar" id="asal_bokar" class="form-control form-control-sm">
                                <option value="INHUT">INHUT</option>
                                <option value="PT">PT</option>
                                <option value="CMP">CMP</option>
                                <option value="Petani" selected>Petani</option>
                            </select>
                        </div>
                        <hr class="col-12 my-2">
                        <div class="col-md-6 mb-3">
                            <label>Perkiraan Stok Akhir (Kg)</label>
                            <input type="text" id="stok_akhir_display" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" id="keterangan" class="form-control form-control-sm" readonly style="background-color: #e9ecef;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST" data-error-id="{{ session('edit_id') }}">
                @csrf
                @method('PUT')
                 <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Edit Data Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Input Harian</label>
                            <input type="date" name="tanggal_input" id="editTanggalInput" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Uraian</label>
                            <input type="text" name="uraian" id="editUraian" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stok Awal (Kg)</label>
                            <input type="number" name="stok_awal" id="editStokAwal" class="form-control form-control-sm" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Umur (Hari)</label>
                            <input type="number" name="umur" id="editUmur" class="form-control form-control-sm" step="1" required>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Tanggal Masuk Bokar</label>
                            <input type="date" name="tgl_masuk" id="editTglMasuk" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Diolah (Kg)</label>
                            <input type="number" name="diolah" id="editDiolah" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Mutasi (Kg)</label>
                            <input type="number" name="mutasi" id="editMutasi" class="form-control form-control-sm" step="0.01">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Masuk HI (Kg)</label>
                            <input type="number" name="masuk_hi" id="editMasukHi" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Asal Bokar</label>
                            <select name="asal_bokar" id="editAsalBokar" class="form-control form-control-sm">
                                <option value="" disabled>-- Pilih Asal --</option>
                                <option value="INHUT">INHUT</option>
                                <option value="PT">PT</option>
                                <option value="CMP">CMP</option>
                            </select>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Keterangan</label>
                           <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm" readonly style="background-color: #e9ecef;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Detail Data Maturasi</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 detail-list">
                    <dt class="col-sm-5">Uraian</dt><dd class="col-sm-7" id="detailUraian">-</dd>
                    
                    <dt class="col-sm-5">Stok Awal (Kg)</dt><dd class="col-sm-7" id="detailStokAwal">-</dd>
                    <dt class="col-sm-5">Tgl Masuk Stok</dt><dd class="col-sm-7" id="detailTglMasuk">-</dd>
                    <dt class="col-sm-5">Umur</dt><dd class="col-sm-7" id="detailUmur">-</dd>
                    <dt class="col-sm-5">Diolah (Kg)</dt><dd class="col-sm-7" id="detailDiolah">-</dd>
                    <dt class="col-sm-5">Mutasi (Kg)</dt><dd class="col-sm-7" id="detailMutasi">-</dd>
                    <dt class="col-sm-5">Masuk HI (Kg)</dt><dd class="col-sm-7" id="detailMasukHi">-</dd>
                    
                    {{-- ✅ INFO K3 DIPISAH (MASUK VS OLAH) --}}
                    <div class="col-12"><hr class="my-1"></div>
                    <dt class="col-sm-5 text-secondary">K3 Masuk (Bokar)</dt><dd class="col-sm-7 text-secondary" id="detailK3Masuk">-</dd>
                    <dt class="col-sm-5 text-primary">K3 Olah (Maturasi)</dt><dd class="col-sm-7 text-primary" id="detailK3Olah">-</dd>
                    <dt class="col-sm-5">PO</dt><dd class="col-sm-7" id="detailPO">-</dd>
                    <dt class="col-sm-5">PRI</dt><dd class="col-sm-7" id="detailPRI">-</dd>
                    <dt class="col-sm-5">Tgl Uji</dt><dd class="col-sm-7" id="detailTglUji">-</dd>
                    <div class="col-12"><hr class="my-1"></div>

                    <dt class="col-sm-5">Stok Akhir (Kg)</dt><dd class="col-sm-7" id="detailStokAkhir">-</dd>
                    <dt class="col-sm-5">Asal Bokar</dt><dd class="col-sm-7" id="detailAsalBokar">-</dd>
                    <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7" id="detailKeterangan">-</dd>
                    <dt class="col-sm-5">Tgl Update Terakhir</dt><dd class="col-sm-7" id="detailTglInputAsli">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@include('template.script')

{{-- SCRIPT JAVASCRIPT --}}
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {
    // ----- INISIALISASI DATATABLE -----
     var table = $('#dataTable').DataTable({
        "ordering": false, 
        "paging": false,
        "info": false,
        "searching": true,
        "language": { "zeroRecords": "Tidak ada data yang cocok"},
        "dom": 'rt'
    });

     // --- Listener search input custom ---
     $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
     });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    
    $(document).on('submit', '.reset-form', function(e) {
        e.preventDefault(); 
        const form = $(this); 
        Swal.fire({
            title: 'Yakin ingin reset data?',
            text: "Data akan dikembalikan ke status KOSONG.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) { form[0].submit(); }
        });
    });

    function formatNumber(num, precision = 0) { if (num === null || typeof num === 'undefined' || num === '') return '0'; let parsedNum = parseFloat(String(num).replace(/[^0-9,.-]+/g,"").replace(',','.')); if (isNaN(parsedNum)) return '0'; return parsedNum.toLocaleString('id-ID', { minimumFractionDigits: precision, maximumFractionDigits: precision }); }
    function formatTanggalModal(dateStr) { if (!dateStr) return ''; try { if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr; let dateObj = new Date(dateStr); if (isNaN(dateObj.getTime())) return ''; let year = dateObj.getFullYear(); let month = ('0' + (dateObj.getMonth() + 1)).slice(-2); let day = ('0' + dateObj.getDate()).slice(-2); return `${year}-${month}-${day}`; } catch(e) { return ''; } }
    
    function calculateStokAkhirDisplay() {
        let stok_awal = parseFloat($('#stok_awal').val().replace(/[^0-9,.-]+/g,"").replace(',','.')) || 0;
        let diolah = parseFloat($('#diolah').val()) || 0;
        let mutasi = parseFloat($('#mutasi').val()) || 0;
        let masuk_hi = parseFloat($('#masuk_hi').val().replace(/[^0-9,.-]+/g,"").replace(',','.')) || 0;
        let stok_akhir = stok_awal - diolah - mutasi + masuk_hi;
        $('#stok_akhir_display').val(formatNumber(stok_akhir, 2));
    }

    function updateKeterangan(prefix = '') {
        let tanggalInputId = (prefix === 'edit') ? '#editTanggalInput' : '#tanggal_input_tambah';
        let keteranganId = (prefix === 'edit') ? '#editKeterangan' : '#keterangan';
        let tanggalInput = $(tanggalInputId).val();
        if (tanggalInput) {
            try {
                let dateObj = new Date(tanggalInput + 'T00:00:00Z');
                if (!isNaN(dateObj.getTime())) {
                    const day = ('0' + dateObj.getUTCDate()).slice(-2);
                    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
                    const month = monthNames[dateObj.getUTCMonth()];
                    const year = dateObj.getUTCFullYear();
                    $(keteranganId).val(`${day} ${month} ${year}`);
                }
            } catch (e) {}
        }
    }

    function fetchPreviousData() {
        const selectedUraian = $('#uraian').val();
        const selectedDate = $('#tanggal_input_tambah').val();
        if (selectedUraian && selectedDate) {
            $.ajax({
                url: "{{ route('maturasi.getPreviousData') }}", type: 'GET',
                data: { uraian: selectedUraian, tanggal_filter: selectedDate },
                dataType: 'json',
                success: function(data) {
                    $('#stok_awal').val(formatNumber(data.stok_awal));
                    $('#umur').val(data.umur !== null ? data.umur : 0);
                    $('#masuk_hi').val(formatNumber(data.netto_kering_hi));
                    calculateStokAkhirDisplay();
                },
                error: function() {
                    $('#stok_awal').val('0,00'); $('#umur').val(0); $('#masuk_hi').val('0,00');
                    calculateStokAkhirDisplay();
                }
            });
        }
    }

    $('#uraian').on('change', fetchPreviousData);
    $('#tanggal_input_tambah').on('change', function() { fetchPreviousData(); });
    $('#diolah, #mutasi').on('input keyup', calculateStokAkhirDisplay);
    $('#modalTambah').on('show.bs.modal', function () {
         $('#formTambah')[0].reset();
         $('#asal_bokar').val('Petani');
         $('#diolah, #mutasi').val('0');
         $('#masuk_hi').val('0,00');
         $('#stok_awal, #umur').val('');
         $('#stok_akhir_display, #keterangan').val('');
         let defaultDate = "{{ $selected_date }}";
         $('#tanggal_input_tambah').val(defaultDate).trigger('change');
    });
    $(document).on('change', '#editTanggalInput', function() { updateKeterangan('edit'); });

    // --- LOGIKA TOMBOL DETAIL (UPDATE) ---
    $(document).on('click', '.btn-detail', function () {
        const uraian = $(this).data('uraian');
        const tanggal = $(this).data('tanggal');
        $.ajax({
            url: "{{ route('maturasi.getPreviousData') }}",
            method: "GET",
            data: { uraian: uraian, tanggal_filter: tanggal },
            dataType: "json",
            success: function (data) {
                const formatTanggal = (tgl) => {
                    if (!tgl) return 'KOSONG';
                    const d = new Date(tgl);
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                };
                $('#detailUraian').text(uraian);
                $('#detailStokAwal').text(formatNumber(data.stok_awal));
                $('#detailTglMasuk').text(data.tgl_masuk ? formatTanggal(data.tgl_masuk) : 'KOSONG');
                $('#detailUmur').text((data.umur ?? 0) + ' hari');
                $('#detailDiolah').text(formatNumber(data.diolah));
                $('#detailMutasi').text(formatNumber(data.mutasi));
                $('#detailMasukHi').text(formatNumber(data.netto_kering_hi));
                
                // ✅ UPDATE BAGIAN INI: Membedakan K3 Masuk dan K3 Olah
                $('#detailK3Masuk').text(data.k3_masuk ? formatNumber(data.k3_masuk, 2) : '-');
                $('#detailK3Olah').text(data.k3_olah ? formatNumber(data.k3_olah, 2) : '-');
                $('#detailPO').text(data.po || '-');
                $('#detailPRI').text(data.pri || '-');
                $('#detailTglUji').text(data.tgl_uji ? formatTanggal(data.tgl_uji) : '-');
                
                $('#detailStokAkhir').text(formatNumber(data.stok_akhir));
                $('#detailAsalBokar').text(data.asal_bokar ? data.asal_bokar : '-');
                $('#detailKeterangan').text(data.keterangan ?? '-');
                $('#detailTglInputAsli').text(formatTanggal(tanggal));
                $('#modalDetail').modal('show');
            }
        });
    });

    $(document).on('click', '.btn-edit', function () {
        console.log('Tombol Edit Diklik');

        var id = $(this).data('id');
        
        // 🔥 PERBAIKAN: Ambil tanggal dari input filter di atas tabel
        var filterTanggal = $('input[name="filter_tanggal"]').val(); 
        
        var urlGet = "{{ url('maturasi') }}/" + id + "/edit";
        var urlPost = "{{ url('maturasi') }}/" + id;

        // Swal.fire({title: 'Memuat Data...', didOpen: () => Swal.showLoading()});

        // 🔥 PERBAIKAN: Kirim parameter filter_tanggal ke Controller
        $.ajax({
            url: urlGet,
            type: 'GET',
            data: { filter_tanggal: filterTanggal }, // <-- INI KUNCINYA
            success: function(data) {
                Swal.close();
                console.log('Data Edit Diterima:', data);

                // --- Pengisian Form ---
                
                // Set Tanggal Input sesuai Filter Tanggal (dari updated_at controller)
                $('#editTanggalInput').val(data.updated_at);

                $('#editUraian').val(data.uraian);
                
                // Gunakan parseFloat agar angka '0.00' tidak error
                $('#editStokAwal').val(parseFloat(data.stok_awal || 0));
                $('#editUmur').val(data.umur || 0);
                
                // Tgl Masuk (sudah diformat Y-m-d di controller)
                $('#editTglMasuk').val(data.tgl_masuk || '');
                
                $('#editDiolah').val(parseFloat(data.diolah || 0));
                $('#editMutasi').val(parseFloat(data.mutasi || 0));
                $('#editMasukHi').val(parseFloat(data.masuk_hi || 0));
                
                // Logic CMP (Asal Bokar)
                let asal = data.asal_bokar;
                if (asal && asal.includes('CMP')) {
                    $('#editAsalBokar').val('CMP');
                } else {
                    $('#editAsalBokar').val(asal);
                }

                $('#editKeterangan').val(data.keterangan);

                // Update Action Form
                $('#formEdit').attr('action', urlPost);
                $('#formEdit').attr('data-error-id', id);

                $('#modalEdit').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal memuat data. Cek Console.', 'error');
                console.error(xhr);
            }
        });
    });

    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2500 });
    @endif
    @if (session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", showConfirmButton: false, timer: 2500 });
    @endif
});
</script>

</body>
</html>