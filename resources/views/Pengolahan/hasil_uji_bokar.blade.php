<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Memanggil bagian head template (CSS global, meta tags) --}}
    @include('template.head')

    {{-- Token CSRF untuk keamanan request AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Lab Bokar</title>

    {{-- CSS Library Eksternal --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"> {{-- DataTables --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> {{-- Flatpickr Date Picker --}}
    {{-- SweetAlert2 JS (biasanya ditaruh di bawah, tapi OK di sini jika dibutuhkan segera) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Style CSS Kustom untuk Halaman Ini --}}
    <style>
        /* Style dasar untuk sel tabel agar rapi */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;       /* Garis batas sel */
            vertical-align: middle;          /* Vertikal tengah */
            white-space: nowrap;             /* Mencegah teks turun baris */
            text-align: center;              /* Teks rata tengah */
        }
        /* Penegasan rata tengah (jika diperlukan override) */
        #dataTable th,
        #dataTable td {
             text-align: center !important;
             vertical-align: middle !important;
        }
        /* Style untuk tombol aksi agar sejajar di tengah */
        .action-buttons {
            display: flex;
            justify-content: center; /* Tombol rata tengah horizontal */
            gap: 5px;                /* Jarak antar tombol */
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
{{-- Wrapper Utama Aplikasi --}}
<div class="wrapper">

    {{-- Bagian Navbar Template --}}
    @include('template.navbar')
    {{-- Bagian Sidebar Template --}}
    @include('template.sidebar')

    {{-- Konten Utama Halaman --}}
    <div class="content-wrapper">

        {{-- Header Konten (Judul & Tombol Aksi Utama) --}}
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                {{-- Judul Halaman --}}
                <h1 class="m-0 text-success fw-bold">Hasil Uji Lab Bokar</h1>
                {{-- Tombol untuk memicu Modal Tambah Data --}}
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>
        {{-- Akhir Header Konten --}}

        {{-- Area Konten Utama --}}
        <div class="content">
            <div class="container-fluid">
                {{-- Card sebagai container utama --}}
                <div class="card shadow-sm">
                    {{-- Header Card --}}
                    <div class="card-header bg-success text-white fw-bold">
                        Daftar Hasil Uji Lab Bokar
                    </div>
                    {{-- Body Card --}}
                    <div class="card-body table-responsive">

                        {{-- Menampilkan Error Validasi Form (jika ada) --}}
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
                            <div class="col-md-3 d-flex align-items-end gap-2"> {{-- d-flex align-items-end agar tombol sejajar bawah --}}
                                <button id="filter-btn" class="btn btn-primary btn-sm">Filter</button>&nbsp; {{-- Tombol terapkan filter --}}
                                <button id="reset-filter" class="btn btn-secondary btn-sm">Reset</button>   {{-- Tombol reset filter --}}
                            </div>
                        </div>
                        <hr> {{-- Garis pemisah --}}
                        {{-- Akhir Area Filter Tanggal --}}

                        {{-- Tabel Data --}}
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            {{-- Header Tabel (thead) --}}
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>No Sampel</th>
                                    <th>K3 (%)</th>
                                    <th>Dirt (%)</th>
                                    <th>Ash (%)</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Aksi</th> {{-- Kolom untuk tombol --}}
                                </tr>
                            </thead>
                            {{-- Body Tabel (tbody) --}}
                            <tbody>
                                {{-- Looping data dari Controller --}}
                                {{-- PERBAIKAN: Menggunakan @foreach. @empty sudah dihapus untuk fix error DataTable. --}}
                                @foreach ($data_lab as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td> {{-- Nomor urut --}}
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td> {{-- Format tanggal d-m-Y --}}
                                        <td>{{ $item->suplier }}</td>
                                        <td>{{ $item->no_sampel }}</td>
                                        {{-- Format angka: tampilkan integer jika tidak ada desimal --}}
                                        <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td>
                                        <td>{{ is_numeric($item->dirt) ? (fmod($item->dirt, 1) == 0 ? (int)$item->dirt : $item->dirt) : '-' }}</td>
                                        <td>{{ is_numeric($item->ask) ? (fmod($item->ask, 1) == 0 ? (int)$item->ask : $item->ask) : '-' }}</td>
                                        <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                        <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                        <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                        <td>
                                            {{-- Tombol Aksi dalam satu div --}}
                                            <div class="action-buttons">
                                                {{-- Tombol Detail: memicu modal #modalDetail via JS --}}
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"> <i class="fas fa-eye"></i> </button>
                                                {{-- Tombol Edit: memicu modal #modalEdit via JS --}}
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"> <i class="fas fa-edit"></i> </button>
                                                {{-- Tombol Hapus: submit form langsung --}}
                                                <form action="{{ route('hasil_uji_lab_bokar.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block; margin:0;">
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

    {{-- Bagian Footer Template --}}
    <footer class="main-footer">
        @include('template.footer')
    </footer>
    {{-- Akhir Footer --}}

</div>
{{-- Akhir Wrapper Utama --}}

{{-- ================================================================= --}}
{{--                           BAGIAN MODAL                             --}}
{{-- ================================================================= --}}

{{-- MODAL TAMBAH DATA --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="modalTambahLabel" aria-hidden="true">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- Form mengarah ke route store --}}
            <form action="{{ route('hasil_uji_lab_bokar.store') }}" method="POST">
                @csrf {{-- Token CSRF --}}
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel">Tambah Hasil Uji Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    {{-- Input fields untuk data baru --}}
                    <div class="form-group"><label for="tambahTanggal">Tanggal</label><input type="date" id="tambahTanggal" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label for="tambahSupplier">Supplier</label><input type="text" id="tambahSupplier" name="suplier" class="form-control" required></div>
                    <div class="form-group"><label for="tambahNoSampel">No Sampel</label><input type="text" id="tambahNoSampel" name="no_sampel" class="form-control" required></div>
                    <div class="form-group"><label for="tambahK3">K3 (%)</label><input type="number" id="tambahK3" name="k3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahDirt">Dirt (%)</label><input type="number" id="tambahDirt" name="dirt" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahAsk">Ash (%)</label><input type="number" id="tambahAsk" name="ask" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahPo">Po</label><input type="number" id="tambahPo" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahPa">Pa</label><input type="number" id="tambahPa" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="tambahPri">PRI</label><input type="number" id="tambahPri" name="pri" class="form-control" step="0.01"></div>
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
                {{-- Daftar definisi (dl) untuk menampilkan detail data --}}
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                    <dt class="col-sm-4">Supplier</dt><dd class="col-sm-8" id="detailSupplier">-</dd>
                    <dt class="col-sm-4">No Sampel</dt><dd class="col-sm-8" id="detailNoSampel">-</dd>
                    <dt class="col-sm-4">K3 (%)</dt><dd class="col-sm-8" id="detailK3">-</dd>
                    <dt class="col-sm-4">Dirt (%)</dt><dd class="col-sm-8" id="detailDirt">-</dd>
                    <dt class="col-sm-4">Ash (%)</dt><dd class="col-sm-8" id="detailAsk">-</dd>
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
            {{-- Form action akan diisi oleh JavaScript saat tombol edit diklik --}}
            <form id="formEdit" method="POST">
                @csrf       {{-- Token CSRF --}}
                @method('PUT') {{-- Method spoofing untuk request PUT --}}
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalEditLabel">Edit Hasil Uji Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    {{-- Input fields untuk mengedit data, akan diisi oleh JS --}}
                    <div class="form-group"><label for="editTanggal">Tanggal</label><input type="date" id="editTanggal" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label for="editSupplier">Supplier</label><input type="text" id="editSupplier" name="suplier" class="form-control" required></div>
                    <div class="form-group"><label for="editNoSampel">No Sampel</label><input type="text" id="editNoSampel" name="no_sampel" class="form-control" required></div>
                    <div class="form-group"><label for="editK3">K3 (%)</label><input type="number" id="editK3" name="k3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editDirt">Dirt (%)</label><input type="number" id="editDirt" name="dirt" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editAsk">Ash (%)</label><input type="number" id="editAsk" name="ask" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editPo">Po</label><input type="number" id="editPo" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editPa">Pa</label><input type="number" id="editPa" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label for="editPri">PRI</label><input type="number" id="editPri" name="pri" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="saveEditBtn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{--                       BAGIAN SCRIPT JavaScript                     --}}
{{-- ================================================================= --}}

{{-- PERBAIKAN: Panggil script template utama --}}
{{-- Ini seharusnya sudah berisi jQuery, Bootstrap, dan adminlte.min.js --}}
@include('template.script')

{{-- Memanggil library JS eksternal (HANYA JIKA BELUM ADA di template.script) --}}
{{-- Jika template.script sudah memuat jQuery & Bootstrap, Anda bisa hapus 2 baris di bawah --}}
{{-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> --}}

{{-- Panggil library spesifik halaman ini (SETELAH template.script) --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

{{-- Script kustom untuk halaman ini --}}
<script>
$(document).ready(function() { // Jalankan script hanya setelah seluruh halaman HTML selesai dimuat

    // Tampilkan notifikasi sukses (jika ada) menggunakan SweetAlert
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}", // Ambil pesan dari session flash
            showConfirmButton: false,         // Sembunyikan tombol OK
            timer: 2000                       // Tutup otomatis setelah 2 detik
        });
    @endif

    // Inisialisasi Flatpickr untuk input filter tanggal
    var fpMin, fpMax; // Variabel untuk menyimpan instance Flatpickr

    // Fungsi bantuan untuk mengubah format tanggal dd-mm-yyyy dari tabel menjadi objek Date JavaScript
    function parseDMY(dateStr){
        var parts = dateStr.split('-');
        if(parts.length !== 3 || parts[0].length !== 2 || parts[1].length !== 2 || parts[2].length !== 4) return null; // Validasi format
        return new Date(parts[2], parts[1] - 1, parts[0]); // Ingat: bulan JS dimulai dari 0
    }

    // Konfigurasi Flatpickr untuk input "Dari Tanggal"
    fpMin = flatpickr("#min-date", {
        altInput: true,         // Tampilkan input tambahan yang mudah dibaca
        altFormat: "d/m/Y",     // Format tampilan dd/mm/yyyy
        dateFormat: "Y-m-d",    // Format nilai yang dikirim yyyy-mm-dd
        defaultDate: "today"    // PERMINTAAN: Set tanggal default hari ini
    });
    // Konfigurasi Flatpickr untuk input "Sampai Tanggal"
    fpMax = flatpickr("#max-date", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        defaultDate: "today"    // PERMINTAAN: Set tanggal default hari ini
    });

    // Menambahkan fungsi filter custom ke DataTables
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex){
            // Ambil nilai tanggal dari Flatpickr (format YYYY-MM-DD)
            var minVal = $('#min-date').val();
            var maxVal = $('#max-date').val();
            // Ambil nilai tanggal dari kolom kedua tabel (index 1), formatnya dd-mm-yyyy
            var tableDateStr = data[1] || '';

            // Jika filter tanggal kosong atau tanggal di tabel kosong/invalid, tampilkan baris
            if (!minVal && !maxVal) return true;
            if (!tableDateStr || tableDateStr === '-') return true;

            // Ubah tanggal tabel (dd-mm-yyyy) menjadi objek Date
            var tableDate = parseDMY(tableDateStr);
            if (!tableDate) return true; // Jika format tanggal tabel salah, tampilkan saja

            // Ubah tanggal filter (yyyy-mm-dd) menjadi objek Date
            // Tambahkan T00:00:00 dan T23:59:59 untuk memastikan perbandingan mencakup seluruh hari
            var minDate = minVal ? new Date(minVal + 'T00:00:00') : null;
            var maxDate = maxVal ? new Date(maxVal + 'T23:59:59') : null;

            // Lakukan perbandingan: tampilkan jika tanggal tabel >= minDate dan <= maxDate
            if (
                (!minDate || tableDate >= minDate) &&
                (!maxDate || tableDate <= maxDate)
            ) {
                return true; // Tampilkan baris
            }
            return false; // Sembunyikan baris
        }
    );

    // Inisialisasi DataTables pada tabel dengan ID 'dataTable'
    var table = $('#dataTable').DataTable({
        "order": [[1, "desc"]], // Urutkan default berdasarkan kolom Tanggal (index 1) descending
        // PERBAIKAN: Atasi error "Incorrect column count" saat data kosong
        "language": {
            // Teks yang ditampilkan jika tbody kosong
            "emptyTable": "Belum ada data hasil uji lab.",
            // Teks jika filter tidak menghasilkan data
            "zeroRecords": "Data tidak ditemukan berdasarkan filter yang dipilih."
        }
    });

    // Terapkan filter tanggal saat halaman dimuat (karena tanggal default terisi)
    table.draw();

    // Ketika tombol 'Filter' (#filter-btn) diklik
    $('#filter-btn').on('click', function(e){
        e.preventDefault(); // Mencegah submit form (jika tombol ada dalam form)
        table.draw();       // Terapkan filter (fungsi $.fn.dataTable.ext.search akan dipanggil)
    });

    // Ketika tombol 'Reset' (#reset-filter) diklik
    $('#reset-filter').on('click', function(e){
        e.preventDefault();
        // Kembalikan tanggal Flatpickr ke hari ini
        fpMin.setDate("today");
        fpMax.setDate("today");
        // Hapus semua filter DataTables dan gambar ulang tabel
        // setTimeout memberi jeda agar Flatpickr selesai update sebelum DataTables draw
        setTimeout(function() {
             table.search('').columns().search('').draw(); // Hapus filter global & kolom
        }, 100);
    });

    // Setup CSRF token header untuk semua request AJAX (penting untuk POST, PUT, DELETE)
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // --- LOGIKA MODAL DETAIL ---
    // Gunakan event delegation '.on' agar listener berlaku juga untuk tombol di halaman DataTables berikutnya
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id'); // Ambil 'id' dari atribut 'data-id' tombol yang diklik
        // Buat URL endpoint API untuk mengambil data detail
        // Pastikan 'hasil_uji_lab_bokar' sesuai dengan prefix URL di routes/web.php
        var url = "{{ url('hasil_uji_lab_bokar') }}/" + id;

        // Kirim request GET menggunakan jQuery AJAX
        $.get(url, function(data){ // 'data' adalah JSON response dari controller show()
            // Isi elemen-elemen di dalam #modalDetail dengan data yang diterima
            let formattedDate = '-'; // Default jika tanggal null atau invalid
            if (data.tanggal) {
                // 1. Pastikan format YYYY-MM-DD
                const dateParts = String(data.tanggal).split('-'); // Pisahkan tahun, bulan, hari
                if (dateParts.length === 3) {
                    try {
                        // 2. Buat objek Date secara manual menggunakan UTC
                        // parseInt(dateParts[1]) - 1 karena bulan JS dimulai dari 0
                        const dateObj = new Date(Date.UTC(
                            parseInt(dateParts[0]), // Tahun
                            parseInt(dateParts[1]) - 1, // Bulan (0-11)
                            parseInt(dateParts[2]) // Hari
                        ));

                        // 3. Format tanggal ke locale Indonesia (dd NamaBulan yyyy)
                        formattedDate = dateObj.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                            timeZone: 'UTC' // Penting agar tidak terpengaruh timezone browser
                        });
                    } catch (e) {
                        // Jika parsing gagal (misal data tanggal aneh)
                        console.error("Error parsing date:", data.tanggal, e);
                        formattedDate = 'Invalid Date'; // Tampilkan pesan error
                    }
                } else {
                    // Jika format dari server bukan YYYY-MM-DD
                    console.warn("Format tanggal dari server tidak sesuai:", data.tanggal);
                    formattedDate = 'Invalid Format';
                }
            }
            $('#detailTanggal').text(formattedDate);
            $('#detailSupplier').text(data.suplier ?? '-'); // Gunakan null coalescing operator
            $('#detailNoSampel').text(data.no_sampel ?? '-');
            // Format angka: Tampilkan integer jika tidak ada desimal, '-' jika null/bukan angka
            $('#detailK3').text(data.k3 != null ? (data.k3 % 1 === 0 ? parseInt(data.k3) : parseFloat(data.k3)) : '-');
            $('#detailDirt').text(data.dirt != null ? (data.dirt % 1 === 0 ? parseInt(data.dirt) : parseFloat(data.dirt)) : '-');
            $('#detailAsk').text(data.ask != null ? (data.ask % 1 === 0 ? parseInt(data.ask) : parseFloat(data.ask)) : '-');
            $('#detailPo').text(data.po != null ? (data.po % 1 === 0 ? parseInt(data.po) : parseFloat(data.po)) : '-');
            $('#detailPa').text(data.pa != null ? (data.pa % 1 === 0 ? parseInt(data.pa) : parseFloat(data.pa)) : '-');
            $('#detailPri').text(data.pri != null ? (data.pri % 1 === 0 ? parseInt(data.pri) : parseFloat(data.pri)) : '-');
            
            // Tampilkan modal detail menggunakan fungsi Bootstrap
            $('#modalDetail').modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){ // Fungsi yang dijalankan jika AJAX gagal
            // Tampilkan error menggunakan SweetAlert dan log detail ke console
            console.error("AJAX Error [Detail]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memuat Data!',
                text: 'Terjadi kesalahan saat mengambil data detail. Periksa console browser (F12) untuk detail teknis.'
             });
        });
    });

    // --- LOGIKA MODAL EDIT ---
    $(document).on('click','.btn-edit',function(){
        var id = $(this).data('id'); // Ambil 'id' dari atribut 'data-id'
        
        // Buat URL endpoint API untuk mengambil data yang akan diedit
        var urlGet = "{{ url('hasil_uji_lab_bokar') }}/" + id + "/edit";
        // Buat URL endpoint untuk mengirim data update (akan jadi 'action' form)
        var urlPost = "{{ url('hasil_uji_lab_bokar') }}/" + id;
        
        // Kirim request GET menggunakan jQuery AJAX untuk mengambil data
        $.get(urlGet, function(data){ // 'data' adalah JSON response dari controller edit()
            // Isi nilai (value) input fields di dalam #modalEdit
            $('#editTanggal').val(data.tanggal); // Format tanggal YYYY-MM-DD cocok untuk input type="date"
            $('#editSupplier').val(data.suplier);
            $('#editNoSampel').val(data.no_sampel);
            $('#editK3').val(data.k3);
            $('#editDirt').val(data.dirt);
            $('#editAsk').val(data.ask);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            
            // Set atribut 'action' pada form #formEdit ke URL update
            $('#formEdit').attr('action', urlPost);
            // Tampilkan modal edit
            $('#modalEdit').modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){ // Fungsi jika AJAX gagal
            // Tampilkan error menggunakan SweetAlert dan log detail ke console
            console.error("AJAX Error [Edit]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memuat Data!',
                text: 'Terjadi kesalahan saat mengambil data untuk diedit. Periksa console browser (F12) untuk detail teknis.'
             });
        });
    });

}); // Akhir $(document).ready()
</script>
{{-- Akhir Bagian Script --}}

</body>
</html>