<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Memanggil bagian head template --}}
    @include('template.head')
    {{-- Token CSRF untuk keamanan AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Maturasi</title>

    {{-- CSS Library Eksternal --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Style CSS Kustom --}}
    <style>
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
            text-align: center;
        }
        #dataTable th,
        #dataTable td {
             text-align: center !important;
             vertical-align: middle !important;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 5px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
{{-- Wrapper Utama --}}
<div class="wrapper">

    {{-- Bagian Navbar --}}
    @include('template.navbar')
    {{-- Bagian Sidebar --}}
    @include('template.sidebar')

    {{-- Konten Utama Halaman --}}
    <div class="content-wrapper">
        {{-- Header Konten --}}
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-success fw-bold">Hasil Uji Maturasi</h1>
                    </div>
                    <div class="col-sm-6">
                        {{-- Tombol Tambah Data --}}
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
                                    <i class="fas fa-plus-circle"></i> Tambah Data
                                </button>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        {{-- Akhir Header Konten --}}

        {{-- Area Konten Utama --}}
        <div class="content">
            <div class="container-fluid">
                {{-- Card Konten --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        Daftar Hasil Uji Maturasi
                    </div>
                    <div class="card-body table-responsive">
                        {{-- Menampilkan Error Validasi --}}
                         @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Area Filter Tanggal --}}
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
                        {{-- Akhir Area Filter Tanggal --}}

                        {{-- Tabel Data --}}
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            {{-- Header Tabel --}}
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No. Kamar</th>
                                    <th>K3 (%)</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            {{-- Body Tabel --}}
                            <tbody>
                                {{-- Looping data --}}
                                {{-- PERBAIKAN: @empty dihapus --}}
                                @foreach ($data_maturasi as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->no_kamar }}</td>
                                        <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td>
                                        <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                        <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                        <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                        <td>
                                            {{-- Tombol Aksi --}}
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"> <i class="fas fa-eye"></i> </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"> <i class="fas fa-edit"></i> </button>
                                                {{-- Sesuaikan nama route destroy jika perlu --}}
                                                <form action="{{ route('hasil_uji_maturasi.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline-block; margin:0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"> <i class="fas fa-trash"></i> </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{-- Akhir Tabel Data --}}
                    </div>
                    {{-- Akhir Body Card --}}
                </div>
                {{-- Akhir Card --}}
            </div>
        </div>
        {{-- Akhir Area Konten Utama --}}
    </div>
    {{-- Akhir Content Wrapper --}}

    {{-- Bagian Footer --}}
    <footer class="main-footer">
        @include('template.footer')
    </footer>
    {{-- Akhir Footer --}}

</div>
{{-- Akhir Wrapper --}}

{{-- ================================================================= --}}
{{--                           BAGIAN MODAL                             --}}
{{-- ================================================================= --}}

{{-- MODAL TAMBAH DATA --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- Sesuaikan nama route store jika perlu --}}
            <form action="{{ route('hasil_uji_maturasi.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel">Tambah Hasil Uji Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"> <label for="tambahTanggal">Tanggal</label> <input type="date" id="tambahTanggal" name="tanggal" class="form-control" required> </div>
                    <div class="form-group"> <label for="tambahNoKamar">No. Kamar</label> <input type="text" id="tambahNoKamar" name="no_kamar" class="form-control" required> </div>
                    <div class="form-group"> <label for="tambahK3">K3 (%)</label> <input type="number" id="tambahK3" name="k3" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label for="tambahPo">Po</label> <input type="number" id="tambahPo" name="po" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label for="tambahPa">Pa</label> <input type="number" id="tambahPa" name="pa" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label for="tambahPri">PRI</label> <input type="number" id="tambahPri" name="pri" class="form-control" step="0.01"> </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL DATA --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalDetailLabel">Detail Hasil Uji</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                    <dt class="col-sm-4">No. Kamar</dt><dd class="col-sm-8" id="detailNoKamar">-</dd>
                    <dt class="col-sm-4">K3 (%)</dt><dd class="col-sm-8" id="detailK3">-</dd>
                    <dt class="col-sm-4">Po</dt><dd class="col-sm-8" id="detailPo">-</dd>
                    <dt class="col-sm-4">Pa</dt><dd class="col-sm-8" id="detailPa">-</dd>
                    <dt class="col-sm-4">PRI</dt><dd class="col-sm-8" id="detailPri">-</dd>
                </dl>
            </div>
             <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT DATA --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST"> {{-- Action diisi JS --}}
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalEditLabel">Edit Hasil Uji Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                     <div class="form-group"> <label for="editTanggal">Tanggal</label> <input type="date" id="editTanggal" name="tanggal" class="form-control" required> </div>
                    <div class="form-group"> <label for="editNoKamar">No. Kamar</label> <input type="text" id="editNoKamar" name="no_kamar" class="form-control" required> </div>
                    <div class="form-group"> <label for="editK3">K3 (%)</label> <input type="number" id="editK3" name="k3" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label for="editPo">Po</label> <input type="number" id="editPo" name="po" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label for="editPa">Pa</label> <input type="number" id="editPa" name="pa" class="form-control" step="0.01"> </div>
                    <div class="form-group"> <label for="editPri">PRI</label> <input type="number" id="editPri" name="pri" class="form-control" step="0.01"> </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{--                       BAGIAN SCRIPT JavaScript                     --}}
{{-- ================================================================= --}}
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() { // Jalankan setelah DOM siap

    // Tampilkan notifikasi sukses
    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
    @endif

    // Inisialisasi Flatpickr
    var fpMin, fpMax;
    function parseDMY(dateStr){ // Fungsi helper parse tanggal dd-mm-yyyy
        var parts = dateStr.split('-'); if(parts.length!==3 || parts[0].length !== 2 || parts[1].length !== 2 || parts[2].length !== 4) return null; return new Date(parts[2], parts[1]-1, parts[0]);
    }
    // Inisialisasi DENGAN tanggal default "today"
    fpMin = flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });
    fpMax = flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });

    // Fungsi filter DataTables
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var minVal = $('#min-date').val(), maxVal = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!minVal && !maxVal) return true; if (!tableDateStr || tableDateStr === '-') return true;
        var tableDate = parseDMY(tableDateStr); if (!tableDate) return true;
        var minDate = minVal ? new Date(minVal + 'T00:00:00') : null, maxDate = maxVal ? new Date(maxVal + 'T23:59:59') : null;
        if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; return false;
    });

    // Inisialisasi DataTables
    var table = $('#dataTable').DataTable({
        "order": [[1, "desc"]], // Urutkan default by tanggal descending
        // PERBAIKAN: Pesan saat data kosong
        "language": {
            "emptyTable": "Belum ada data hasil uji maturasi.",
            "zeroRecords": "Data tidak ditemukan berdasarkan filter."
        }
    });
    // Terapkan filter awal karena tanggal default terisi
    table.draw();

    // Event listener tombol Filter & Reset
    $('#filter-btn').on('click', function(e) { e.preventDefault(); table.draw(); });
    $('#reset-filter').on('click', function(e) {
        e.preventDefault(); fpMin.setDate("today"); fpMax.setDate("today");
        setTimeout(function() { table.search('').columns().search('').draw(); }, 100);
    });

    // Setup CSRF token AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- LOGIKA MODAL DETAIL ---
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');
        // Sesuaikan URL prefix 'hasil_uji_maturasi' jika berbeda di web.php
        var url = "{{ url('hasil_uji_maturasi') }}/" + id;
        $.get(url, function (data) {
            $('#detailTanggal').text(data.tanggal ? new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }) : '-');
            $('#detailNoKamar').text(data.no_kamar || '-');
            $('#detailK3').text(data.k3 != null ? (data.k3 % 1 === 0 ? parseInt(data.k3) : parseFloat(data.k3)) : '-');
            $('#detailPo').text(data.po != null ? (data.po % 1 === 0 ? parseInt(data.po) : parseFloat(data.po)) : '-');
            $('#detailPa').text(data.pa != null ? (data.pa % 1 === 0 ? parseInt(data.pa) : parseFloat(data.pa)) : '-');
            $('#detailPri').text(data.pri != null ? (data.pri % 1 === 0 ? parseInt(data.pri) : parseFloat(data.pri)) : '-');
            $('#modalDetail').modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){
            console.error("AJAX Error [Detail]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({ icon: 'error', title: 'Gagal Memuat Data!', text: 'Gagal memuat detail. Periksa console (F12).' });
        });
    });

    // --- LOGIKA MODAL EDIT ---
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        // Sesuaikan URL prefix 'hasil_uji_maturasi' jika berbeda di web.php
        var urlGet = "{{ url('hasil_uji_maturasi') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil_uji_maturasi') }}/" + id;
        $.get(urlGet, function (data) {
            $('#editTanggal').val(data.tanggal);
            $('#editNoKamar').val(data.no_kamar);
            $('#editK3').val(data.k3);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            $('#formEdit').attr('action', urlPost); // Set action form
            $('#modalEdit').modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){
            console.error("AJAX Error [Edit]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({ icon: 'error', title: 'Gagal Memuat Data!', text: 'Gagal memuat data edit. Periksa console (F12).' });
        });
    });

}); // Akhir document ready
</script>
{{-- Akhir Bagian Script --}}

</body>
</html>