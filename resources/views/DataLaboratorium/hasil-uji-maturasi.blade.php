<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Maturasi</title>
    
    {{-- CSS Libraries --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- SweetAlert (Opsional, jika template belum ada) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; text-align: center; }
        #dataTable th, #dataTable td { text-align: center !important; vertical-align: middle !important; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
        .page-item.active .page-link { background-color: #28a745; border-color: #28a745; }
        .page-link { color: #28a745; }
        .page-link:hover { color: #1e7e34; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Hasil Uji Maturasi</h1>
                <div>
                    <a href="javascript:void(0)" class="btn btn-warning btn-sm fw-bold mr-1" id="btnExportExcel">
                        <i class="fas fa-file-excel"></i> Cetak Excel
                    </a>
                    {{-- 🔥 SEMBUNYIKAN TOMBOL TAMBAH JIKA ROLE USER --}}
                    @if(auth()->user()->role != 'user')
                    <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                        <i class="fas fa-plus-circle"></i> Tambah Data
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold"> 
                        <strong class="my-auto">Daftar Hasil Uji Maturasi</strong></div>
                    <div class="card-body">
                         @if ($errors->any())
                            <div class="alert alert-danger"><ul class="mb-0">
                                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul></div>
                        @endif
                        
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
                                <button id="filter-btn" class="btn btn-primary btn-sm fw-bold mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                                <button id="reset-filter" class="btn btn-secondary btn-sm fw-bold"><i class="fas fa-undo mr-1"></i> Reset</button>
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="dataTable" style="width:100%">
                                <thead class="text-center bg-light"> 
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal Uji</th>
                                        <th>No. Kamar</th>
                                        <th>K3 (%)</th>
                                        <th>Po</th>
                                        <th>Pa</th>
                                        <th>PRI</th>
                                        {{-- 🔥 SEMBUNYIKAN HEADER AKSI JIKA ROLE USER --}}
                                        @if(auth()->user()->role != 'user')
                                        <th>Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data_maturasi as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td> 
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                            
                                            {{-- Menampilkan Nama Bak dari Relasi --}}
                                            <td>{{ $item->maturasi->uraian ?? 'Bak Terhapus' }}</td> 
                                            
                                            <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td> 
                                            <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td> 
                                            <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td> 
                                            <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                            {{-- 🔥 SEMBUNYIKAN KOLOM AKSI JIKA ROLE USER --}}
                                            @if(auth()->user()->role != 'user')
                                            <td> 
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id_hasil_uji_lab_maturasi }}" title="Detail"> 
                                                        <i class="fas fa-eye"></i> 
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id_hasil_uji_lab_maturasi }}" title="Edit"> 
                                                        <i class="fas fa-edit"></i> 
                                                    </button>
                                                    
                                                    {{-- 🔥 TOMBOL HAPUS DENGAN CLASS BTN-HAPUS --}}
                                                    <button type="button" class="btn btn-danger btn-sm btn-hapus" 
                                                            data-id="{{ $item->id_hasil_uji_lab_maturasi }}" 
                                                            data-nama="{{ $item->maturasi->uraian ?? 'Bak' }}" title="Hapus"> 
                                                        <i class="fas fa-trash"></i> 
                                                    </button>
                                                    <form id="form-hapus-{{ $item->id_hasil_uji_lab_maturasi }}" action="{{ route('hasil-uji-maturasi.destroy', $item->id_hasil_uji_lab_maturasi) }}" method="POST" style="display:none;">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                </div>
                                            </td>
                                            @endif
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
    <footer class="main-footer"> @include('template.footer') </footer>
</div>

{{-- INCLUDE SCRIPT UTAMA (JQUERY & BOOTSTRAP BAWAAN TEMPLATE) --}}
@include('template.script')

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('hasil-uji-maturasi.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Tambah Hasil Uji Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bak Maturasi</label>
                        {{-- Mengirim ID, bukan string --}}
                        <select name="id_maturasi" class="form-control" required>
                            <option value="" disabled selected>-- Pilih Bak Maturasi --</option>
                            @foreach ($bak_maturasi as $bak)
                                <option value="{{ $bak->id_maturasi }}">
                                    {{ $bak->uraian }} (Stok: {{ $bak->stok_akhir }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Tanggal Uji</label><input type="date" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label>K3 (%)</label><input type="number" name="k3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Po</label><input type="number" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"> 
                        <label>PRI</label> 
                        <input type="number" name="pri" id="tambahPri" class="form-control bg-light" step="0.01" readonly style="background-color: #e9ecef;"> 
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
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-success text-white"> 
                    <h5 class="modal-title">Edit Hasil Uji Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                     <div class="form-group"> <label>Tanggal</label> <input type="date" name="tanggal" id="editTanggal" class="form-control" required> </div>
                    
                    <div class="form-group"> 
                        <label>No. Kamar</label> 
                        <select name="id_maturasi" id="editMaturasiId" class="form-control" required>
                             @foreach ($bak_maturasi as $bak)
                                <option value="{{ $bak->id_maturasi }}">{{ $bak->uraian }}</option>
                             @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group"> <label>K3 (%)</label> <input type="number" name="k3" id="editK3" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label>Po</label> <input type="number" name="po" id="editPo" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label>Pa</label> <input type="number" name="pa" id="editPa" class="form-control" step="0.01"> </div>
                    <div class="form-group"> 
                        <label>PRI</label> 
                        <input type="number" name="pri" id="editPri" class="form-control bg-light" step="0.01" readonly style="background-color: #e9ecef;"> 
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
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"> 
                <h5 class="modal-title">Detail Hasil Uji</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 d-flex justify-content-between"><span>Tanggal</span><span>:</span></dt>
                    <dd class="col-sm-8" id="detailTanggal">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between"><span>No. Kamar</span><span>:</span></dt>
                    <dd class="col-sm-8" id="detailNoKamar">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between"><span>K3 (%)</span><span>:</span></dt>
                    <dd class="col-sm-8" id="detailK3">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between"><span>Po</span><span>:</span></dt>
                    <dd class="col-sm-8" id="detailPo">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between"><span>Pa</span><span>:</span></dt>
                    <dd class="col-sm-8" id="detailPa">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between"><span>PRI</span><span>:</span></dt>
                    <dd class="col-sm-8" id="detailPri">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT JAVASCRIPT --}}
{{-- 🔥 HAPUS JQUERY MANUAL DI SINI AGAR TIDAK DOUBLE LOAD --}}
{{-- Library Tambahan (DataTables & Flatpickr) --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {
    // 1. Notifikasi Sukses Global
    @if (session('success'))
        Swal.fire({ 
            icon: 'success', 
            title: 'Berhasil!', 
            text: "{{ session('success') }}", 
            showConfirmButton: false, 
            timer: 2000 
        });
    @endif

    // 2. Notifikasi Error Validasi Global
    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            html: '{!! implode("<br>", $errors->all()) !!}',
        });
    @endif

    // --- LOGIKA HITUNG PRI OTOMATIS ---
    function hitungPRI(poSelector, paSelector, priSelector) {
        let po = parseFloat($(poSelector).val()) || 0;
        let pa = parseFloat($(paSelector).val()) || 0;
        let priField = $(priSelector);

        if (po > 0) {
            let hasil = (pa / po) * 100;
            priField.val(hasil.toFixed(2)); 
        } else {
            priField.val(''); 
        }
    }

    // 🔥 LOGIKA EXCEL EXPORT (MATURASI)
    // 🔥 LOGIKA EXCEL EXPORT (Ganti route sesuai halaman)
    $('#btnExportExcel').on('click', function(e) {
        e.preventDefault();
        var minDate = $('#min-date').val();
        var maxDate = $('#max-date').val();
        
        // Ganti 'hasil-uji-xxx.export' sesuai route di halaman tersebut
        var exportUrl = "{{ route('hasil-uji-maturasi.export') }}"; 
        
        if (minDate && maxDate) {
            exportUrl += "?start_date=" + minDate + "&end_date=" + maxDate;
            window.location.href = exportUrl;
        } else {
            Swal.fire('Informasi', 'Silakan pilih rentang tanggal filter terlebih dahulu.', 'info');
        }
    });

    // Event untuk Modal Tambah
    $(document).on('input', '#modalTambah input[name="po"], #modalTambah input[name="pa"]', function() {
        hitungPRI('#modalTambah input[name="po"]', '#modalTambah input[name="pa"]', '#modalTambah input[name="pri"]');
    });

    // Event untuk Modal Edit
    $(document).on('input', '#editPo, #editPa', function() {
        hitungPRI('#editPo', '#editPa', '#editPri');
    });

    // --- SETUP TANGGAL (Flatpickr) ---
    var fpMin = flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });
    var fpMax = flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });
    
    function parseDMY(dateStr){
        var parts = dateStr.split('-'); if(parts.length!==3) return null; return new Date(parts[2], parts[1]-1, parts[0]);
    }
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var min = $('#min-date').val(), max = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!tableDateStr || tableDateStr === '-') return true; 
        var tableDate = parseDMY(tableDateStr); if (!tableDate) return true;
        var minDate = min ? new Date(min + 'T00:00:00') : null, maxDate = max ? new Date(max + 'T23:59:59') : null;
        if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; return false;
    });

    // --- DATATABLES CONFIG ---
    var table = $('#dataTable').DataTable({ 
        "order": [[1,"desc"]], 
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" } 
    });
    table.draw();
    
    $('#filter-btn').on('click', function(e) { e.preventDefault(); table.draw(); });
    $('#reset-filter').on('click', function(e) { 
        e.preventDefault(); 
        fpMin.setDate("today"); fpMax.setDate("today"); 
        setTimeout(function() { table.search('').draw(); }, 100); 
    });
    
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- 3. KONFIRMASI SIMPAN (TAMBAH DATA) ---
    $('#modalTambah form').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Simpan Data Uji?',
            text: "Pastikan input parameter lab sudah sesuai.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { form.submit(); }
        });
    });

    // --- 4. KONFIRMASI UPDATE (EDIT DATA) ---
    $('#formEdit').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Update Hasil Uji?',
            text: "Data lab maturasi akan diperbarui.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Update!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { form.submit(); }
        });
    });

    // --- 5. KONFIRMASI HAPUS DATA ---
    $(document).on('click', '.btn-hapus', function(e) {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        Swal.fire({
            title: 'Hapus Hasil Uji?',
            text: "Data uji kamar " + nama + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { $('#form-hapus-' + id).submit(); }
        });
    });

    // --- AJAX DETAIL ---
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id'); 
        var url = "{{ url('hasil-uji-maturasi') }}/" + id; 
        $.get(url, function (data) { 
            var tanggalFormatted = data.tanggal ? new Date(data.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-';
            $('#detailTanggal').text(tanggalFormatted);
            $('#detailNoKamar').text(data.maturasi ? data.maturasi.uraian : '-'); 
            $('#detailK3').text(data.k3 || '-');
            $('#detailPo').text(data.po || '-');
            $('#detailPa').text(data.pa || '-');
            $('#detailPri').text(data.pri || '-');
            $('#modalDetail').modal('show');
        }).fail(function() { Swal.fire('Gagal', 'Tidak dapat memuat detail.', 'error'); });
    });

    // --- AJAX EDIT ---
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id'); 
        var urlGet = "{{ url('hasil-uji-maturasi') }}/" + id + "/edit"; 
        var urlPost = "{{ url('hasil-uji-maturasi') }}/" + id; 
        $.get(urlGet, function (data) {
            var tanggalInput = data.tanggal ? data.tanggal.substring(0, 10) : "";
            $('#editTanggal').val(tanggalInput); 
            $('#editMaturasiId').val(data.id_maturasi); 
            $('#editK3').val(data.k3);
            $('#editPo').val(data.po); 
            $('#editPa').val(data.pa); 
            $('#editPri').val(data.pri);
            $('#formEdit').attr('action', urlPost);
            $('#modalEdit').modal('show');
        }).fail(function() { Swal.fire('Gagal', 'Tidak dapat memuat data edit.', 'error'); });
    });
});
</script>

</body>
</html>