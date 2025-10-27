<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Memanggil bagian head template --}}
    @include('template.head')
    {{-- Token CSRF untuk keamanan AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji SIR 20</title>

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
                        <h1 class="m-0 text-success fw-bold">Hasil Uji SIR 20</h1>
                    </div>
                    <div class="col-sm-6">
                        {{-- Tombol Tambah Data --}}
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                {{-- Ganti data-target ke ID modal tambah SIR 20 --}}
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambahSir">
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
                        Daftar Hasil Uji SIR 20
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
                                    <th>Jenis Kemasan</th>
                                    <th>No. Palet</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Dirt(%)</th>
                                    <th>Ash(%)</th>
                                    <th>VM(%)</th>
                                    <th>Money</th> {{-- Ejaan 'Money' sesuai kode JS Anda --}}
                                    <th>Nitrogen(%)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            {{-- Body Tabel --}}
                            <tbody>
                                {{-- Looping data --}}
                                {{-- PERBAIKAN: @empty dihapus --}}
                                @foreach ($data_sir_20 as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->jenis_kemasan ?? '-' }}</td>
                                        <td>{{ $item->no_palet }}</td>
                                        {{-- Format Angka --}}
                                        <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                        <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                        <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                        <td>{{ is_numeric($item->dirt) ? (fmod($item->dirt, 1) == 0 ? (int)$item->dirt : $item->dirt) : '-' }}</td>
                                        <td>{{ is_numeric($item->ash) ? (fmod($item->ash, 1) == 0 ? (int)$item->ash : $item->ash) : '-' }}</td>
                                        <td>{{ is_numeric($item->vm) ? (fmod($item->vm, 1) == 0 ? (int)$item->vm : $item->vm) : '-' }}</td>
                                        <td>{{ is_numeric($item->money) ? (fmod($item->money, 1) == 0 ? (int)$item->money : $item->money) : '-' }}</td>
                                        <td>{{ is_numeric($item->nitrogen) ? (fmod($item->nitrogen, 1) == 0 ? (int)$item->nitrogen : $item->nitrogen) : '-' }}</td>
                                        <td>
                                            {{-- Tombol Aksi --}}
                                            <div class="action-buttons">
                                                <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                {{-- Sesuaikan nama route destroy --}}
                                                <form action="{{ route('hasil_uji_sir_20.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?')" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
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

{{-- MODAL TAMBAH DATA SIR 20 --}}
<div class="modal fade" id="modalTambahSir" tabindex="-1" role="dialog" aria-labelledby="modalTambahSirLabel" aria-hidden="true">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- Sesuaikan nama route store --}}
            <form action="{{ route('hasil_uji_sir_20.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTambahSirLabel">Tambah Hasil Uji SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label for="tambahTanggalSir">Tanggal</label><input type="date" id="tambahTanggalSir" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label for="tambahJenisKemasan">Jenis Kemasan</label><input type="text" id="tambahJenisKemasan" name="jenis_kemasan" class="form-control" placeholder="Contoh: Plastik, Kayu..."></div>
                    <div class="form-group"><label for="tambahNoPalet">No. Palet</label><input type="text" id="tambahNoPalet" name="no_palet" class="form-control" required></div>
                    <div class="form-group"><label for="tambahPoSir">Po</label><input type="number" id="tambahPoSir" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahPaSir">Pa</label><input type="number" id="tambahPaSir" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahPriSir">PRI</label><input type="number" id="tambahPriSir" name="pri" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahDirtSir">Dirt (%)</label><input type="number" id="tambahDirtSir" name="dirt" class="form-control" step="0.001"></div>
                    <div class="form-group"><label for="tambahAshSir">Ash (%)</label><input type="number" id="tambahAshSir" name="ash" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahVmSir">VM (%)</label><input type="number" id="tambahVmSir" name="vm" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahMoneySir">Mooney</label><input type="number" id="tambahMoneySir" name="money" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahNitrogenSir">Nitrogen (%)</label><input type="number" id="tambahNitrogenSir" name="nitrogen" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL DATA SIR 20 --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalDetailLabel">Detail Hasil Uji SIR 20</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                    <dt class="col-sm-4">Jenis Kemasan</dt><dd class="col-sm-8" id="detailJenisKemasan">-</dd>
                    <dt class="col-sm-4">No. Palet</dt><dd class="col-sm-8" id="detailNoPalet">-</dd>
                    <dt class="col-sm-4">Po</dt><dd class="col-sm-8" id="detailPo">-</dd>
                    <dt class="col-sm-4">Pa</dt><dd class="col-sm-8" id="detailPa">-</dd>
                    <dt class="col-sm-4">PRI</dt><dd class="col-sm-8" id="detailPri">-</dd>
                    <dt class="col-sm-4">Dirt</dt><dd class="col-sm-8" id="detailDirt">-</dd>
                    <dt class="col-sm-4">Ash</dt><dd class="col-sm-8" id="detailAsh">-</dd>
                    <dt class="col-sm-4">VM</dt><dd class="col-sm-8" id="detailVm">-</dd>
                    <dt class="col-sm-4">Mooney</dt><dd class="col-sm-8" id="detailMoney">-</dd> {{-- ID 'detailMoney' --}}
                    <dt class="col-sm-4">Nitrogen</dt><dd class="col-sm-8" id="detailNitrogen">-</dd>
                </dl>
            </div>
             <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT DATA SIR 20 --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST"> {{-- Action diisi JS --}}
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalEditLabel">Edit Hasil Uji SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label for="editTanggal">Tanggal</label><input type="date" id="editTanggal" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label for="editJenisKemasan">Jenis Kemasan</label><input type="text" id="editJenisKemasan" name="jenis_kemasan" class="form-control" placeholder="Contoh: Plastik, Kayu..."></div>
                    <div class="form-group"><label for="editNoPalet">No. Palet</label><input type="text" id="editNoPalet" name="no_palet" class="form-control" required></div>
                    <div class="form-group"><label for="editPo">Po</label><input type="number" id="editPo" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editPa">Pa</label><input type="number" id="editPa" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editPri">PRI</label><input type="number" id="editPri" name="pri" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editDirt">Dirt (%)</label><input type="number" id="editDirt" name="dirt" class="form-control" step="0.001"></div>
                    <div class="form-group"><label for="editAsh">Ash (%)</label><input type="number" id="editAsh" name="ash" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editVm">VM (%)</label><input type="number" id="editVm" name="vm" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editMoney">Mooney</label><input type="number" id="editMoney" name="money" class="form-control" step="0.01"></div> {{-- ID 'editMoney' --}}
                    <div class="form-group"><label for="editNitrogen">Nitrogen (%)</label><input type="number" id="editNitrogen" name="nitrogen" class="form-control" step="0.01"></div>
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

{{-- PERBAIKAN: Aktifkan @include('template.script') --}}
{{-- Ini seharusnya sudah berisi jQuery, Bootstrap, dan adminlte.min.js --}}
@include('template.script')

{{-- HAPUS PANGGILAN MANUAL JQUERY & BOOTSTRAP JIKA SUDAH ADA DI template.script --}}
{{-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> --}}

{{-- Panggil library spesifik halaman ini (SETELAH template.script) --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

{{-- Script kustom untuk halaman ini --}}
<script>
$(document).ready(function(){ // Jalankan setelah DOM siap

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
    fpMin = flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat:"Y-m-d", defaultDate: "today" });
    fpMax = flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat:"Y-m-d", defaultDate: "today" });

    // Fungsi filter DataTables
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var minVal = $('#min-date').val(), maxVal = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!minVal && !maxVal) return true; if (!tableDateStr || tableDateStr==='-') return true;
        var tableDate = parseDMY(tableDateStr); if (!tableDate) return true;
        var minDate = minVal ? new Date(minVal + 'T00:00:00') : null, maxDate = maxVal ? new Date(maxVal + 'T23:59:59') : null;
        if((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; return false;
    });

    // Inisialisasi DataTables
    var table = $('#dataTable').DataTable({
        "order":[[1,"desc"]], // Urutkan default by tanggal descending
        // PERBAIKAN: Pesan saat data kosong
        "language": {
            "emptyTable": "Belum ada data hasil uji SIR 20.",
            "zeroRecords": "Data tidak ditemukan berdasarkan filter."
        }
    });
    // Terapkan filter awal
    table.draw();

    // Event listener tombol Filter & Reset
    $('#filter-btn').click(function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').click(function(e){ e.preventDefault(); fpMin.setDate("today"); fpMax.setDate("today"); setTimeout(function(){ table.search('').columns().search('').draw(); },100); });

    // Setup CSRF token AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- LOGIKA MODAL DETAIL ---
    $(document).on('click', '.btn-detail', function(){
        var id = $(this).data('id');
        // Sesuaikan URL prefix 'hasil_uji_sir_20' jika berbeda di web.php
        var url = "{{ url('hasil_uji_sir_20') }}/" + id;
        $.get(url, function(data){
            $('#detailTanggal').text(data.tanggal ? new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }) : '-');
            $('#detailJenisKemasan').text(data.jenis_kemasan || '-');
            $('#detailNoPalet').text(data.no_palet || '-');

            // Fungsi helper format angka
            function formatNumber(num) {
                if (num == null || !$.isNumeric(num)) return '-';
                return (num % 1 === 0) ? parseInt(num) : parseFloat(num);
            }

            $('#detailPo').text(formatNumber(data.po));
            $('#detailPa').text(formatNumber(data.pa));
            $('#detailPri').text(formatNumber(data.pri));
            $('#detailDirt').text(formatNumber(data.dirt));
            $('#detailAsh').text(formatNumber(data.ash));
            $('#detailVm').text(formatNumber(data.vm));
            $('#detailMoney').text(formatNumber(data.money)); // ID 'detailMoney'
            $('#detailNitrogen').text(formatNumber(data.nitrogen));

            $('#modalDetail').modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){
            console.error("AJAX Error [Detail]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({ icon: 'error', title: 'Gagal Memuat Data!', text: 'Gagal memuat detail SIR 20. Periksa console (F12).' });
        });
    });

    // --- LOGIKA MODAL EDIT ---
    $(document).on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        // Sesuaikan URL prefix 'hasil_uji_sir_20' jika berbeda di web.php
        var urlGet = "{{ url('hasil_uji_sir_20') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil_uji_sir_20') }}/" + id;
        $.get(urlGet, function(data){
            $('#editTanggal').val(data.tanggal);
            $('#editJenisKemasan').val(data.jenis_kemasan);
            $('#editNoPalet').val(data.no_palet);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            $('#editDirt').val(data.dirt);
            $('#editAsh').val(data.ash);
            $('#editVm').val(data.vm);
            $('#editMoney').val(data.money); // ID 'editMoney'
            $('#editNitrogen').val(data.nitrogen);
            $('#formEdit').attr('action', urlPost); // Set action form
            $('#modalEdit').modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){
            console.error("AJAX Error [Edit]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({ icon: 'error', title: 'Gagal Memuat Data!', text: 'Gagal memuat data edit SIR 20. Periksa console (F12).' });
        });
    });

}); // Akhir document ready
</script>
{{-- Akhir Bagian Script --}}

</body>
</html>