<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <title>Pengolahan Maturasi</title>
    <style>
        /* (Style Anda tetap sama) */
        .form-control[readonly] { background-color: #e9ecef; opacity: 1; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; padding: 0.5rem; text-align: center; }
        label { margin-bottom: 0.2rem; font-weight: 500;}
        .form-group label { display: block; }
        .filter-search-row .col-md-3 { margin-bottom: 1rem; }
        @media (min-width: 768px) {
            .filter-search-row { display: flex; align-items: flex-end; }
            #search-input-container { margin-left: auto; }
        }
        .action-buttons .btn-group { display: flex; gap: 0.25rem; }
        .data-default td { color: #6c757d; font-style: italic; }
        .detail-list dt { display: flex; justify-content: space-between; padding-right: 0.5rem; }
        .detail-list dt::after { content: ":"; }
        .detail-list dd { text-align: left; }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Pengolahan Maturasi</h3>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Input Diolah/Mutasi
                </button>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong>Data Pengolahan Maturasi (Status per: {{ \Carbon\Carbon::parse($selected_date)->isoFormat('D MMMM YYYY') }})</strong>
                        
                        <form method="GET" action="{{ route('maturasi.index') }}" class="form-inline ml-auto">
                            <label for="filter_tanggal" class="mr-2 text-white">Tampilkan Tanggal:</label>
                            <input type="date" name="filter_tanggal" class="form-control form-control-sm mr-2" value="{{ $selected_date ?? \Carbon\Carbon::today()->format('Y-m-d') }}">

                            <button type="submit" class="btn btn-light btn-sm">Tampilkan</button>
                        </form>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                            </div>
                        @endif
                        @if(session('edit_error'))
                            <div class="alert alert-warning mb-3">
                                Gagal menyimpan perubahan. Silakan cek kembali input Anda.
                                <script> $(function() { $('#modalEdit[data-error-id="{{ session('edit_id') }}"]').modal('show'); }); </script>
                            </div>
                        @endif

                        <div class="row mb-3 filter-search-row">
                            <div class="col-md-3" id="search-input-container" style="margin-left: auto;">
                                <label for="searchInput">Cari (Uraian/Bak):</label>
                                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Ketik nomor bak...">
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center align-middle" id="dataTable">
                                <thead class="bg-light">
                                <tr>
                                    <th>Uraian</th>
                                    {{-- Tgl Update Terakhir dihilangkan dari tabel sesuai permintaan --}}
                                    <th>Stok Awal (Kg)</th>
                                    <th>Tgl Masuk Stok</th>
                                    <th>Umur</th>
                                    <th>Diolah (Kg)</th>
                                    <th>Mutasi (Kg)</th>
                                    <th>Masuk HI (Kg)</th>
                                    <th>K3 Masuk</th>
                                    <th>K3 Olah</th>
                                    <th>PO</th>
                                    <th>PRI</th>
                                    <th>Tgl Uji</th>
                                    <th>Stok Akhir (Kg)</th>
                                    <th>Asal Bokar</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($data_maturasi as $item)
                                    <tr>
                                        <td>{{ $item->uraian }}</td>
                                        {{-- kolom Tgl Update Terakhir sengaja dihapus dari baris tabel --}}
                                        <td>{{ number_format($item->stok_awal, 0, ',', '.') }}</td>
                                        <td>
                                            @if($item->tgl_masuk)
                                                {{-- tampilkan tanggal masuk jika ada --}}
                                                @if($item->tgl_masuk instanceof \Carbon\Carbon)
                                                    {{ $item->tgl_masuk->format('d-m-Y') }}
                                                @else
                                                    {{-- if stored as string --}}
                                                    {{ \Carbon\Carbon::parse($item->tgl_masuk)->format('d-m-Y') }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $item->umur ?? 0 }} hari</td>
                                        <td>{{ number_format($item->diolah, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->mutasi, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->masuk_hi, 0, ',', '.') }}</td>
                                        {{-- kolom baru --}}
                                        <td>{{ number_format($item->k3_masuk ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->k3_olah ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $item->po ?? '0' }}</td>
                                        <td>{{ $item->pri ?? '0' }}</td>
                                        <td>
                                            @if($item->tgl_uji)
                                            {{ \Carbon\Carbon::parse($item->tgl_uji)->format('d-m-Y') }}
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->stok_akhir, 0, ',', '.') }}</td>
                                        <td>{{ $item->asal_bokar ?? '-' }}</td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>
<td class="text-center">
    <div class="dropdown">
        <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="dropdownMenu{{ $item->id }}" data-toggle="dropdown" aria-expanded="false">
            Aksi
        </button>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu{{ $item->id }}">
            <a class="dropdown-item btn-detail" href="javascript:void(0)"
               data-uraian="{{ $item->uraian }}"
               data-tanggal="{{ $selected_date }}">
                <i class="fas fa-eye text-info mr-2"></i> Detail
            </a>
            <a class="dropdown-item btn-edit" href="javascript:void(0)"
               data-id="{{ $item->id }}">
                <i class="fas fa-edit text-warning mr-2"></i> Edit
            </a>
            <form action="{{ route('maturasi.reset', $item->id) }}" method="POST"
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
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('template.footer')
</div>

{{-- ============================================= --}}
{{-- SEMUA MODAL DITEMPATKAN DI SINI --}}
{{-- ============================================= --}}

{{-- MODAL TAMBAH DATA --}}
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
                            <label>Umur (Hari)</label>
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
                            <label>K3 Masuk</label>
                            <input type="number" name="k3_masuk" id="editK3Masuk" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                            <label>K3 Olah</label>
                            <input type="number" name="k3_olah" id="editK3Olah" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                            <label>PO</label>
                            <input type="text" name="po" id="editPO" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-3">
                            <label>PRI</label>
                            <input type="text" name="pri" id="editPRI" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-3">
                            <label>Tanggal Uji</label>
                            <input type="date" name="tgl_uji" id="editTglUji" class="form-control form-control-sm">
                            </div>


                         {{-- PERBAIKAN: Input Asal Bokar menjadi Dropdown --}}
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
                            <input type="text" name="keterangan" id="keterangan" class="form-control form-control-sm" readonly>
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
                    <dt class="col-sm-5">K3 Masuk</dt><dd class="col-sm-7" id="detailK3Masuk">-</dd>
                    <dt class="col-sm-5">K3 Olah</dt><dd class="col-sm-7" id="detailK3Olah">-</dd>
                    <dt class="col-sm-5">PO</dt><dd class="col-sm-7" id="detailPO">-</dd>
                    <dt class="col-sm-5">PRI</dt><dd class="col-sm-7" id="detailPRI">-</dd>
                    <dt class="col-sm-5">Tgl Uji</dt><dd class="col-sm-7" id="detailTglUji">-</dd>

                    <dt class="col-sm-5">Stok Akhir (Kg)</dt><dd class="col-sm-7" id="detailStokAkhir">-</dd>
                    <dt class="col-sm-5">Asal Bokar</dt><dd class="col-sm-7" id="detailAsalBokar">-</dd>
                    <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7" id="detailKeterangan">-</dd>
                    <dt class="col-sm-5">Tgl Update Terakhir</dt><dd class="col-sm-7" id="detailTglInputAsli">-</dd>
                </dl>
            </div>
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

                        {{-- PERBAIKAN: Input Asal Bokar menjadi Dropdown --}}
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
                            <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm" readonly>
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

{{-- Script Libraries --}}

{{-- 
  BERHENTI! File 'template.script' kemungkinan besar memuat jQuery dan Bootstrap.
  Untuk menghindari duplikat, kita nonaktifkan baris ini.
--}}
{{-- @include('template.script') --}} 

{{-- 
  PERBAIKAN: 
  Kita akan memuat semua script secara manual HANYA SATU KALI
  dan dalam urutan yang benar.
--}}

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {

    // ----- INISIALISASI DATATABLE DI AWAL -----
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

    // ----- EVENT HANDLER LAINNYA -----
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ===================================================
    // ===== TAMBAHKAN KODE INI UNTUK TOMBOL RESET =====
    // ===================================================
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
                if (result.isConfirmed) {
                form[0].submit(); 
                }
                });
                });
    // ===================================================
    // ============ AKHIR TAMBAHAN KODE RESET ============
    // ===================================================

    // ----- FUNGSI FORMATTING -----
    function formatNumber(num, precision = 0) { if (num === null || typeof num === 'undefined' || num === '') return '0'; let parsedNum = parseFloat(String(num).replace(/[^0-9,.-]+/g,"").replace(',','.')); if (isNaN(parsedNum)) return '0'; return parsedNum.toLocaleString('id-ID', { minimumFractionDigits: precision, maximumFractionDigits: precision }); }
    function formatTanggalModal(dateStr) { if (!dateStr) return ''; try { if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr; let dateObj = new Date(dateStr); if (isNaN(dateObj.getTime())) return ''; let year = dateObj.getFullYear(); let month = ('0' + (dateObj.getMonth() + 1)).slice(-2); let day = ('0' + dateObj.getDate()).slice(-2); return `${year}-${month}-${day}`; } catch(e) { return ''; } }
    function formatTanggalDetailModal(dateStr) { if (!dateStr) return 'KOSONG'; try { let dateObj = new Date(dateStr + 'T00:00:00Z'); if (isNaN(dateObj.getTime())) return 'Invalid Date'; return dateObj.toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric', timeZone: 'UTC'}); } catch(e) { return 'Error'; } }

    // --- LOGIKA MODAL TAMBAH ---
    function calculateStokAkhirDisplay() {
        let stok_awal_str = $('#stok_awal').val();
        let stok_awal = parseFloat(stok_awal_str.replace(/[^0-9,.-]+/g,"").replace(',','.')) || 0;
        let diolah = parseFloat($('#diolah').val()) || 0;
        let mutasi = parseFloat($('#mutasi').val()) || 0;
        let masuk_hi_str = $('#masuk_hi').val();
        let masuk_hi = parseFloat(masuk_hi_str.replace(/[^0-9,.-]+/g,"").replace(',','.')) || 0;
        let stok_akhir = stok_awal - diolah - mutasi + masuk_hi;
        $('#stok_akhir_display').val(formatNumber(stok_akhir));
    }

    function updateKeterangan(prefix = '') {
    let tanggalInputId = (prefix === 'edit') ? '#editTanggalInput' : '#tanggal_input_tambah';
    let keteranganId = (prefix === 'edit') ? '#editKeterangan' : '#keterangan';
    let tanggalInput = $(tanggalInputId).val();

    if (tanggalInput) {
        try {
            let dateObj = new Date(tanggalInput + 'T00:00:00Z');
            if (isNaN(dateObj.getTime())) throw new Error("Invalid Date");

            // Ambil bagian tanggal, bulan singkat, dan tahun
            const day = ('0' + dateObj.getUTCDate()).slice(-2);
            const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
            const month = monthNames[dateObj.getUTCMonth()];
            const year = dateObj.getUTCFullYear();

            $(keteranganId).val(`${day} ${month} ${year}`);
        } catch (e) {
            console.error("Error parsing date:", e);
            $(keteranganId).val('Tanggal Invalid');
        }
    } else {
        $(keteranganId).val('');
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
                error: function(jqXHR) {
                    var errorMsg = jqXHR.responseJSON && jqXHR.responseJSON.error ? jqXHR.responseJSON.error : 'Gagal mengambil data sebelumnya.';
                    alert('Gagal: ' + errorMsg);
                    $('#stok_awal').val('0,00'); $('#umur').val(0); $('#masuk_hi').val('0,00');
                    calculateStokAkhirDisplay();
                }
            });
        } else {
             $('#stok_awal').val('0,00'); $('#umur').val(0); $('#masuk_hi').val('0,00');
             calculateStokAkhirDisplay();
        }
    }

    $('#uraian').on('change', fetchPreviousData);
    $('#tanggal_input_tambah').on('change', function() { fetchPreviousData(); updateKeterangan(''); });
    $('#diolah, #mutasi, #masuk_hi, #stok_awal').on('input keyup', calculateStokAkhirDisplay);

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

    // --- LOGIKA DETAIL (memanggil snapshot berdasarkan uraian + tanggal) ---
    $(document).on('click', '.btn-detail', function () {
        const uraian = $(this).data('uraian');
        const tanggal = $(this).data('tanggal'); // tanggal halaman aktif

        $.ajax({
            url: "{{ route('maturasi.getPreviousData') }}",
            method: "GET",
            data: {
                uraian: uraian,
                tanggal_filter: tanggal
            },
            dataType: "json",
            success: function (data) {
                // format tanggal tampil
                const formatTanggal = (tgl) => {
                    if (!tgl) return 'KOSONG';
                    const d = new Date(tgl);
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                };

                $('#detailUraian').text(uraian);
                // Tgl Update Terakhir ditampilkan di modal menggunakan selected tanggal snapshot
                $('#detailStokAwal').text(formatNumber(data.stok_awal));
                $('#detailTglMasuk').text(data.tgl_masuk ? formatTanggal(data.tgl_masuk) : 'KOSONG');
                $('#detailUmur').text((data.umur ?? 0) + ' hari');
                $('#detailDiolah').text(formatNumber(data.diolah));
                $('#detailMutasi').text(formatNumber(data.mutasi));
                $('#detailMasukHi').text(formatNumber(data.netto_kering_hi));
                $('#detailK3Masuk').text(formatNumber(data.k3_masuk));
                $('#detailK3Olah').text(formatNumber(data.k3_olah));
                $('#detailPO').text(data.po ?? '-');
                $('#detailPRI').text(data.pri ?? '-');
                $('#detailTglUji').text(data.tgl_uji ? formatTanggal(data.tgl_uji) : '-');
                $('#detailStokAkhir').text(formatNumber(data.stok_akhir));
                $('#detailAsalBokar').text(data.asal_bokar ?? '-');
                $('#detailKeterangan').text(data.keterangan ?? '-');
                $('#detailTglInputAsli').text(formatTanggal(tanggal));

                $('#modalDetail').modal('show');
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert('Gagal memuat data detail.');
            }
        });
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        var urlGet = "{{ url('maturasi') }}/" + id + "/edit";
        var urlPost = "{{ url('maturasi') }}/" + id;
        $.get(urlGet, function (data) {
             let tglModal = data.updated_at ? formatTanggalModal(data.updated_at.split('T')[0]) : '';
             $('#editTanggalInput').val(tglModal).trigger('change');

             $('#editUraian').val(data.uraian);
             $('#editStokAwal').val(data.stok_awal !== null ? parseFloat(data.stok_awal).toFixed(2) : '0');
             $('#editUmur').val(data.umur !== null ? data.umur : 0);
             $('#editTglMasuk').val(data.tgl_masuk ? formatTanggalModal(data.tgl_masuk) : '');
             $('#editDiolah').val(data.diolah !== null ? parseFloat(data.diolah).toFixed(2) : '0');
             $('#editMutasi').val(data.mutasi !== null ? parseFloat(data.mutasi).toFixed(2) : '0');
             $('#editMasukHi').val(data.masuk_hi !== null ? parseFloat(data.masuk_hi).toFixed(2) : '0');
             $('#editK3Masuk').val(data.k3_masuk ?? 0);
             $('#editK3Olah').val(data.k3_olah ?? 0);
             $('#editPO').val(data.po ?? '');
             $('#editPRI').val(data.pri ?? '');
             $('#editTglUji').val(data.tgl_uji ? formatTanggalModal(data.tgl_uji) : '');

             $('#editAsalBokar').val(data.asal_bokar);
             $('#formEdit').attr('action', urlPost);
             $('#formEdit').attr('data-error-id', id);
             $('#modalEdit').modal('show');
         }).fail(function() { alert('Gagal memuat data untuk diedit.'); });
    });

    // --- Notifikasi Sukses ---
    @if (session('success'))
        if (typeof Swal !== 'undefined') {
             Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
        } else { alert("{{ session('success') }}"); }
    @endif

    @if (session('error'))
        if (typeof Swal !== 'undefined') {
             Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", showConfirmButton: false, timer: 2500 });
        } else { alert("{{ session('error') }}"); }
    @endif

});
</script>
</body>
</html>
