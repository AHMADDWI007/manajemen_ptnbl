<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Memanggil bagian head template (CSS global, meta tags) --}}
    @include('template.head')
    {{-- Token CSRF untuk keamanan request AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Judul Halaman --}}
    <title>Hasil Uji Bokar Olah</title>

    {{-- CSS Library Eksternal --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"> {{-- DataTables Styling --}}
    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Style CSS Kustom --}}
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; text-align: center; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
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

        {{-- Header Konten --}}
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Hasil Uji Bokar Olah</h1>
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
                       Daftar Hasil Uji Bokar Olah
                    </div>
                    {{-- Body Card --}}
                    <div class="card-body table-responsive">

                        {{-- Menampilkan Error Validasi (jika ada) --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Tabel Data --}}
                        <table class="table table-bordered text-center align-middle" id="dataTable">
                            {{-- Header Tabel (thead) --}}
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Bak Maturasi</th>
                                    <th>Jenis</th>
                                    {{-- PERBAIKAN: Kolom Berat Truck & Timbang Dihapus --}}
                                    {{-- <th>Berat Truck (Kg)</th> --}}
                                    {{-- <th>Berat Timbang (Kg)</th> --}}
                                    <th>Netto Basah (Kg)</th>
                                    <th>K3 (%)</th>
                                    <th>Netto Kering (Kg)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            {{-- Body Tabel (tbody) --}}
                            <tbody>
                                {{-- Looping data dari Controller ($data_uji) --}}
                                @foreach ($data_uji as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->bak_maturasi }}</td>
                                        <td>{{ $item->jenis }}</td>
                                        {{-- PERBAIKAN: Kolom Berat Truck & Timbang Dihapus --}}
                                        {{-- <td>{{ number_format($item->berat_truck ?? 0, 2, ',', '.') }}</td> --}}
                                        {{-- <td>{{ number_format($item->berat_timbang ?? 0, 2, ',', '.') }}</td> --}}
                                        <td>{{ number_format($item->netto_basah ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->k3 ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->netto_kering ?? 0, 2, ',', '.') }}</td>
                                        <td>
                                            {{-- Tombol Aksi --}}
                                            <div class="action-buttons">
                                                <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                <form action="{{ route('hasil_uji_bokar_olah.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="margin: 0;">
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
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('hasil_uji_bokar_olah.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Hasil Uji Bokar Olah</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tambahTanggal">Tanggal</label>
                                <input type="date" class="form-control" id="tambahTanggal" name="tanggal" required>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="tambahBakMaturasi">Bak Maturasi</label>
                                <input type="text" class="form-control" id="tambahBakMaturasi" name="bak_maturasi" required>
                            </div>
                        </div>
                    </div>
                     <div class="row">
                       <div class="col-md-6">
                            <div class="form-group">
                                <label for="tambahJenis">Jenis</label>
                                <input type="text" class="form-control" id="tambahJenis" name="jenis">
                            </div>
                        </div>
                        {{-- PERBAIKAN: Input Berat Truck Dihapus --}}
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="tambahNettoBasah">Netto Basah (Kg)</label>
                                <input type="number" step="0.01" class="form-control" id="tambahNettoBasah" name="netto_basah">
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        {{-- PERBAIKAN: Input Berat Timbang Dihapus --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tambahK3">K3 (%)</label>
                                <input type="number" step="0.01" class="form-control" id="tambahK3" name="k3">
                            </div>
                        </div>
                         <div class="col-md-6">
                             <div class="form-group">
                                <label for="tambahNettoKering">Netto Kering (Kg)</label>
                                <input type="number" step="0.01" class="form-control" id="tambahNettoKering" name="netto_kering">
                            </div>
                        </div>
                    </div>
                    {{-- PERBAIKAN: Baris Input Berat Timbang & Netto Basah sebelumnya dihapus --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT DATA --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formEdit" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalEditLabel">Edit Hasil Uji Bokar Olah</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTanggal">Tanggal</label>
                                <input type="date" class="form-control" id="editTanggal" name="tanggal" required>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="editBakMaturasi">Bak Maturasi</label>
                                <input type="text" class="form-control" id="editBakMaturasi" name="bak_maturasi" required>
                            </div>
                        </div>
                    </div>
                     <div class="row">
                       <div class="col-md-6">
                            <div class="form-group">
                                <label for="editJenis">Jenis</label>
                                <input type="text" class="form-control" id="editJenis" name="jenis">
                            </div>
                        </div>
                         {{-- PERBAIKAN: Input Berat Truck Dihapus --}}
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="editNettoBasah">Netto Basah (Kg)</label>
                                <input type="number" step="0.01" class="form-control" id="editNettoBasah" name="netto_basah">
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        {{-- PERBAIKAN: Input Berat Timbang Dihapus --}}
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="editK3">K3 (%)</label>
                                <input type="number" step="0.01" class="form-control" id="editK3" name="k3">
                            </div>
                        </div>
                         <div class="col-md-6">
                             <div class="form-group">
                                <label for="editNettoKering">Netto Kering (Kg)</label>
                                <input type="number" step="0.01" class="form-control" id="editNettoKering" name="netto_kering">
                            </div>
                        </div>
                    </div>
                    {{-- PERBAIKAN: Baris Input Berat Timbang & Netto Basah sebelumnya dihapus --}}
                </div>
                <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL DETAIL DATA --}}
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalDetailLabel">Detail Hasil Uji Bokar Olah</h5>
                 <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="detailContent">
                <p>Memuat data...</p> {{-- Placeholder --}}
            </div>
             <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{--                       BAGIAN SCRIPT JavaScript                     --}}
{{-- ================================================================= --}}
@include('template.script') {{-- Script dasar (jQuery, Bootstrap JS, AdminLTE JS) --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
{{-- Flatpickr tidak digunakan lagi di view ini --}}

<script>
$(document).ready(function() { // Jalankan setelah DOM siap

    // Tampilkan notifikasi sukses
    @if(session('success'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
        } else {
            alert("{{ session('success') }}");
        }
    @endif

    // Inisialisasi DataTables
    $('#dataTable').DataTable({
        "order": [[1, "desc"]], // Urutkan default by tanggal descending
        "language": {
            "emptyTable": "Belum ada data hasil uji bokar olah.",
            "zeroRecords": "Data tidak ditemukan."
        }
    });

    // Setup CSRF token AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- LOGIKA MODAL EDIT ---
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        // Ganti URL prefix 'hasil_uji_bokar_olah' sesuai route resource Anda
        var urlGet = "{{ url('hasil_uji_bokar_olah') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil_uji_bokar_olah') }}/" + id;

        $.get(urlGet, function(data) {
            $('#formEdit').attr('action', urlPost); // Set action form
            $('#editTanggal').val(data.tanggal);
            $('#editBakMaturasi').val(data.bak_maturasi);
            $('#editJenis').val(data.jenis);
            // PERBAIKAN: Hapus pengisian input Berat Truck & Timbang
            // $('#editBeratTruck').val(data.berat_truck);
            // $('#editBeratTimbang').val(data.berat_timbang);
            $('#editNettoBasah').val(data.netto_basah);
            $('#editK3').val(data.k3);
            $('#editNettoKering').val(data.netto_kering);
            $('#modalEdit').modal('show'); // Tampilkan modal
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error [Edit]:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Gagal memuat data untuk diedit. Periksa console (F12).' });
        });
    });

    // --- LOGIKA MODAL DETAIL ---
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        // Ganti URL prefix 'hasil_uji_bokar_olah' sesuai route resource Anda
        var url = "{{ url('hasil_uji_bokar_olah') }}/" + id;

        $.get(url, function(data) {
            // Fungsi format angka
            const formatKg = (num) => num != null ? parseFloat(num).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Kg' : '-';
            const formatPercent = (num) => num != null ? parseFloat(num).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%' : '-';
            const formatDate = (dateStr) => {
                 if (!dateStr) return '-';
                 try { return new Date(dateStr + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }); } catch (e) { return 'Invalid Date'; }
            };

            // Buat konten HTML untuk modal detail
            // PERBAIKAN: Hapus baris Berat Truck & Timbang
            let html = `
                <dl class="row mb-0">
                    <dt class="col-sm-5">Tanggal:</dt>         <dd class="col-sm-7">${formatDate(data.tanggal)}</dd>
                    <dt class="col-sm-5">Bak Maturasi:</dt>    <dd class="col-sm-7">${data.bak_maturasi ?? '-'}</dd>
                    <dt class="col-sm-5">Jenis:</dt>           <dd class="col-sm-7">${data.jenis ?? '-'}</dd>
                    <dt class="col-sm-5">Netto Basah:</dt>     <dd class="col-sm-7">${formatKg(data.netto_basah)}</dd>
                    <dt class="col-sm-5">K3:</dt>              <dd class="col-sm-7">${formatPercent(data.k3)}</dd>
                    <dt class="col-sm-5">Netto Kering:</dt>    <dd class="col-sm-7">${formatKg(data.netto_kering)}</dd>
                </dl>`;
            $('#detailContent').html(html); // Masukkan HTML ke modal body
            $('#modalDetail').modal('show'); // Tampilkan modal
        }).fail(function(jqXHR, textStatus, errorThrown) {
             console.error("AJAX Error [Detail]:", textStatus, errorThrown, jqXHR.responseText);
             Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Gagal memuat data detail. Periksa console (F12).' });
        });
    });

}); // Akhir $(document).ready()
</script>
{{-- Akhir Bagian Script --}}

</body>
</html>