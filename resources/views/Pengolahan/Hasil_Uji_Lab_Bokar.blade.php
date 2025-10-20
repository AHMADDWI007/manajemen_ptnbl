<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
        }
        /* Style untuk memberi jarak pada tombol aksi */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 5px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Hasil Uji Lab Bokar</h1>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        Daftar Hasil Uji Lab Bokar
                    </div>
                    <div class="card-body table-responsive">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="min-date">Dari Tanggal:</label>
                                <input type="date" id="min-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label for="max-date">Sampai Tanggal:</label>
                                <input type="date" id="max-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="filter-btn" class="btn btn-primary btn-sm me-2">Filter</button>
                                <button id="reset-filter" class="btn btn-secondary btn-sm ms-2" style="margin-left: 8px;">Reset</button>
                            </div>
                        </div>
                        <hr>

                        <table class="table table-bordered table-striped text-center align-middle" id="dataTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>No Sampel</th>
                                    <th>K3 (%)</th>
                                    <th>Dirt (%)</th>
                                    <th>Ask (%)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_lab as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->suplier }}</td>
                                        <td>{{ $item->no_sampel }}</td>
                                        <td>{{ $item->k3 ?? '-' }}</td>
                                        <td>{{ $item->dirt ?? '-' }}</td>
                                        <td>{{ $item->ask ?? '-' }}</td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('hasil-uji-lab.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada data hasil uji lab.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

{{-- ========================= MODALS ========================= --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('hasil-uji-lab.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Tambah Hasil Uji Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label>Supplier</label><input type="text" name="suplier" class="form-control" required></div>
                    <div class="form-group"><label>No Sampel</label><input type="text" name="no_sampel" class="form-control" required></div>
                    <div class="form-group"><label>K3 (%)</label><input type="number" name="k3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Dirt (%)</label><input type="number" name="dirt" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Ask (%)</label><input type="number" name="ask" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Hasil Uji</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                    <dt class="col-sm-4">Supplier</dt><dd class="col-sm-8" id="detailSupplier">-</dd>
                    <dt class="col-sm-4">No Sampel</dt><dd class="col-sm-8" id="detailNoSampel">-</dd>
                    <dt class="col-sm-4">K3 (%)</dt><dd class="col-sm-8" id="detailK3">-</dd>
                    <dt class="col-sm-4">Dirt (%)</dt><dd class="col-sm-8" id="detailDirt">-</dd>
                    <dt class="col-sm-4">Ask (%)</dt><dd class="col-sm-8" id="detailAsk">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Hasil Uji Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="editTanggal" class="form-control" required></div>
                    <div class="form-group"><label>Supplier</label><input type="text" name="suplier" id="editSupplier" class="form-control" required></div>
                    <div class="form-group"><label>No Sampel</label><input type="text" name="no_sampel" id="editNoSampel" class="form-control" required></div>
                    <div class="form-group"><label>K3 (%)</label><input type="number" name="k3" id="editK3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Dirt (%)</label><input type="number" name="dirt" id="editDirt" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Ask (%)</label><input type="number" name="ask" id="editAsk" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- PERBAIKAN: Menghapus @include('template.script') dan memuat semua script secara manual --}}
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
{{-- Anda mungkin perlu menambahkan script AdminLTE di sini jika template Anda membutuhkannya. Sesuaikan path-nya. --}}
{{-- <script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script> --}}


{{-- ========================= JAVASCRIPT ========================= --}}
<script>
$(document).ready(function() {
    console.log("Dokumen siap, jQuery dan Bootstrap seharusnya sudah dimuat.");

    try {
        var table = $('#dataTable').DataTable({
            "searching": false
        });
        console.log("DataTables berhasil diinisialisasi.");

        // === FILTER TANGGAL ===
        $.fn.dataTable.ext.search.push(function(settings, data) {
            var minStr = $('#min-date').val();
            var maxStr = $('#max-date').val();
            var dateStr = data[1] || '';

            if ((minStr === '' && maxStr === '') || dateStr === '-') return true;

            var parts = dateStr.split('-');
            if (parts.length !== 3) return false;
            var tableDate = new Date(parts[2], parts[1] - 1, parts[0]);
            var min = minStr ? new Date(minStr) : null;
            var max = maxStr ? new Date(maxStr) : null;
            if (max) max.setHours(23,59,59,999);

            return (!min || tableDate >= min) && (!max || tableDate <= max);
        });

        $('#filter-btn').on('click', function() {
            console.log("Tombol Filter diklik.");
            table.draw();
        });
        $('#reset-filter').on('click', function() {
            console.log("Tombol Reset diklik.");
            $('#min-date, #max-date').val('');
            table.draw();
        });
        console.log("Event handler untuk filter berhasil dipasang.");

        // === CSRF SETUP ===
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // === ALERT BERHASIL ===
        @if (session('success'))
            alert("{{ session('success') }}");
        @endif

        // === DETAIL DATA ===
        $(document).on('click', '.btn-detail', function () {
            var id = $(this).data('id');
            console.log("Tombol Detail diklik untuk ID: " + id);
            $.get('/hasil-uji-lab/' + id, function (data) {
                $('#detailTanggal').text(new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID'));
                $('#detailSupplier').text(data.suplier || '-');
                $('#detailNoSampel').text(data.no_sampel || '-');
                $('#detailK3').text(data.k3 || '-');
                $('#detailDirt').text(data.dirt || '-');
                $('#detailAsk').text(data.ask || '-');
                $('#modalDetail').modal('show');
            });
        });

        // === EDIT DATA ===
        $(document).on('click', '.btn-edit', function () {
            var id = $(this).data('id');
            console.log("Tombol Edit diklik untuk ID: " + id);
            $.get('/hasil-uji-lab/' + id + '/edit', function (data) {
                $('#editTanggal').val(data.tanggal);
                $('#editSupplier').val(data.suplier);
                $('#editNoSampel').val(data.no_sampel);
                $('#editK3').val(data.k3);
                $('#editDirt').val(data.dirt);
                $('#editAsk').val(data.ask);
                $('#formEdit').attr('action', '/hasil-uji-lab/' + id);
                $('#modalEdit').modal('show');
            });
        });
        console.log("Event handler untuk Detail dan Edit berhasil dipasang.");

    } catch (e) {
        console.error("Terjadi error saat inisialisasi script:", e);
        alert("Terjadi kesalahan JavaScript. Silakan buka console (F12) untuk melihat detail error.");
    }
});
</script>
</body>
</html>

