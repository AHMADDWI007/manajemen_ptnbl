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
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Bahan Dalam Proses (WIP)</h3>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Bahan Dalam Proses</strong>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- BAGIAN FILTER TANGGAL --}}
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
                                 <button id="filter-btn" class="btn btn-primary btn-sm me-2">Filter Tanggal</button>
                                 <button id="reset-filter" class="btn btn-secondary btn-sm" style="margin-left: 8px;">Reset</button>
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center align-middle" id="dataTable">
                                <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th> {{-- Kolom tanggal ditambahkan --}}
                                    <th>Uraian</th>
                                    <th>WIP Masuk (Kg)</th>
                                    <th>WIP Keluar (Kg)</th>
                                    <th>Produksi SIR/20 (Kg)</th>
                                    <th>Rekfif</th>
                                    <th>Saldo Akhir (Kg)</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($data_produksi as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->created_at->format('d-m-Y') }}</td> {{-- Menampilkan tanggal data dibuat --}}
                                        <td>{{ $item->uraian ?? '-' }}</td>
                                        <td>{{ number_format($item->wip_masuk ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->wip_keluar ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->produksi_sir20 ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ $item->rekfif ?? '-' }}</td>
                                        <td>{{ number_format($item->saldo_akhir ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('bahan-proses.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

{{-- MODAL (TAMBAH, DETAIL, EDIT) --}}
{{-- ... (Kode modal Anda tidak berubah) ... --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('bahan-proses.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Tambah Data Bahan Proses</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Uraian</label>
                            <select name="uraian" class="form-control" required>
                                <option value="" disabled selected>-- Pilih Uraian --</option>
                                <option value="Lantai Umpan Kering">Lantai Umpan Kering</option>
                                <option value="Di Blending Tank 4">Di Blending Tank 4</option>
                                <option value="Di Lump Breaker-2 (Di Blending Tank-4)">Di Lump Breaker-2 (Di Blending Tank-4)</option>
                                <option value="Di Pre Breaker-2 (Di Blending Tank-5)">Di Pre Breaker-2 (Di Blending Tank-5)</option>
                                <option value="Di Hammer Mill-2 (Di Blending Tank-6)">Di Hammer Mill-2 (Di Blending Tank-6)</option>
                                <option value="Di Blending Tank-7">Di Blending Tank-7</option>
                                <option value="Di Trolley">Di Trolley</option>
                                <option value="Di Dalam Dryer/Press Bale">Di Dalam Dryer/Press Bale</option>
                                <option value="Di Reproses Ex WS.">Di Reproses Ex WS.</option>
                            </select>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>WIP Masuk (Kg)</label>
                            <input type="number" name="wip_masuk" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>WIP Keluar (Kg)</label>
                            <input type="number" name="wip_keluar" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Produksi SIR/20 (Kg)</label>
                            <input type="number" name="produksi_sir20" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Rekfif</label>
                            <input type="text" name="rekfif" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Saldo Akhir (Kg)</label>
                            <input type="number" name="saldo_akhir" class="form-control" step="0.01">
                        </div>
                        <div class="col-12 mb-3">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2"></textarea>
                        </div>
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
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Data</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Uraian</dt><dd class="col-sm-7" id="detailUraian">-</dd>
                    <dt class="col-sm-5">WIP Masuk</dt><dd class="col-sm-7" id="detailWipMasuk">-</dd>
                    <dt class="col-sm-5">WIP Keluar</dt><dd class="col-sm-7" id="detailWipKeluar">-</dd>
                    <dt class="col-sm-5">Produksi SIR/20</dt><dd class="col-sm-7" id="detailProduksi">-</dd>
                    <dt class="col-sm-5">Rekfif</dt><dd class="col-sm-7" id="detailRekfif">-</dd>
                    <dt class="col-sm-5">Saldo Akhir</dt><dd class="col-sm-7" id="detailSaldoAkhir">-</dd>
                    <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7" id="detailKeterangan">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Data Bahan Proses</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Uraian</label>
                            <select name="uraian" id="editUraian" class="form-control" required>
                                <option value="" disabled>-- Pilih Uraian --</option>
                                <option value="Lantai Umpan Kering">Lantai Umpan Kering</option>
                                <option value="Di Blending Tank 4">Di Blending Tank 4</option>
                                <option value="Di Lump Breaker-2 (Di Blending Tank-4)">Di Lump Breaker-2 (Di Blending Tank-4)</option>
                                <option value="Di Pre Breaker-2 (Di Blending Tank-5)">Di Pre Breaker-2 (Di Blending Tank-5)</option>
                                <option value="Di Hammer Mill-2 (Di Blending Tank-6)">Di Hammer Mill-2 (Di Blending Tank-6)</option>
                                <option value="Di Blending Tank-7">Di Blending Tank-7</option>
                                <option value="Di Trolley">Di Trolley</option>
                                <option value="Di Dalam Dryer/Press Bale">Di Dalam Dryer/Press Bale</option>
                                <option value="Di Reproses Ex WS.">Di Reproses Ex WS.</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>WIP Masuk (Kg)</label>
                            <input type="number" name="wip_masuk" id="editWipMasuk" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>WIP Keluar (Kg)</label>
                            <input type="number" name="wip_keluar" id="editWipKeluar" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Produksi SIR/20 (Kg)</label>
                            <input type="number" name="produksi_sir20" id="editProduksi" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Rekfif</label>
                            <input type="text" name="rekfif" id="editRekfif" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Saldo Akhir (Kg)</label>
                            <input type="number" name="saldo_akhir" id="editSaldoAkhir" class="form-control" step="0.01">
                        </div>
                        <div class="col-12 mb-3">
                            <label>Keterangan</label>
                            <textarea name="keterangan" id="editKeterangan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('template.script')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    var table = $('#dataTable').DataTable({
        "searching": false // Mematikan search bawaan
    });

    // ----- LOGIKA FILTER TANGGAL -----
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var minStr = $('#min-date').val();
            var maxStr = $('#max-date').val();
            var dateStr = data[1] || ''; // Mengambil data dari kolom ke-2 (indeks 1), yaitu 'Tanggal'

            if ( ( minStr === '' && maxStr === '' ) || dateStr === '-' ) {
                return true;
            }
            
            var parts = dateStr.split('-');
            if (parts.length !== 3) return false;
            var tableDate = new Date(parts[2], parts[1] - 1, parts[0]);

            var min = minStr ? new Date(minStr) : null;
            var max = maxStr ? new Date(maxStr) : null;
            
            if (max) max.setHours(23, 59, 59, 999);

            if (
                ( min === null && max === null ) ||
                ( min === null && tableDate <= max ) ||
                ( min <= tableDate && max === null ) ||
                ( min <= tableDate && tableDate <= max )
            ) {
                return true;
            }
            return false;
        }
    );

    // Event listener untuk TOMBOL FILTER
    $('#filter-btn').on('click', function() {
        table.draw();
    });
    
    // Tombol untuk mereset filter tanggal
    $('#reset-filter').on('click', function() {
        $('#min-date').val('');
        $('#max-date').val('');
        table.draw();
    });

    // ----- LOGIKA NOTIFIKASI & MODAL -----
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
    
    @if (session('success'))
        alert("{{ session('success') }}");
    @endif

    // === DETAIL ===
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');
        $.get('/bahan-proses/' + id, function (data) {
            $('#detailUraian').text(data.uraian || '-');
            $('#detailWipMasuk').text(data.wip_masuk ? Number(data.wip_masuk).toLocaleString('id-ID', { minimumFractionDigits: 2 }) : '-');
            $('#detailWipKeluar').text(data.wip_keluar ? Number(data.wip_keluar).toLocaleString('id-ID', { minimumFractionDigits: 2 }) : '-');
            $('#detailProduksi').text(data.produksi_sir20 ? Number(data.produksi_sir20).toLocaleString('id-ID', { minimumFractionDigits: 2 }) : '-');
            $('#detailRekfif').text(data.rekfif || '-');
            $('#detailSaldoAkhir').text(data.saldo_akhir ? Number(data.saldo_akhir).toLocaleString('id-ID', { minimumFractionDigits: 2 }) : '-');
            $('#detailKeterangan').text(data.keterangan || '-');
            $('#modalDetail').modal('show');
        });
    });

    // === EDIT ===
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/bahan-proses/' + id + '/edit', function (data) {
            $('#editUraian').val(data.uraian); 
            $('#editWipMasuk').val(data.wip_masuk);
            $('#editWipKeluar').val(data.wip_keluar);
            $('#editProduksi').val(data.produksi_sir20);
            $('#editRekfif').val(data.rekfif);
            $('#editSaldoAkhir').val(data.saldo_akhir);
            $('#editKeterangan').val(data.keterangan);
            $('#formEdit').attr('action', '/bahan-proses/' + id);
            $('#modalEdit').modal('show');
        });
    });
});
</script>

</body>
</html>

