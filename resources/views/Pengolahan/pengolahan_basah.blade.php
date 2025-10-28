<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pengolahan Basah</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; text-align: center; }
        #dataTable th, #dataTable td, #summaryTable th, #summaryTable td { text-align: center !important; vertical-align: middle !important; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
        tfoot tr, thead tr { background-color: #f8f9fa; font-weight: bold; }
        .total-label { text-align: right !important; font-weight: bold; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Pengolahan Basah (Bokar)</h1>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white fw-bold"> Ringkasan Stok </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered" id="summaryTable">
                            <thead class="bg-light">
                                <tr>
                                    <th rowspan="2">Stok Awal</th> <th colspan="2">Bokar Masuk</th>
                                    <th colspan="2">Bokar Diolah</th> <th rowspan="2">Stok Akhir</th>
                                </tr>
                                <tr>
                                    <th>Hi</th> <th>Sd.Hi</th> <th>Hi</th> <th>Sd.Hi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ number_format($summary_data['stok_awal'], 2) }}</td>
                                    <td>{{ number_format($summary_data['masuk_hi'], 2) }}</td>
                                    <td>{{ number_format($summary_data['masuk_sdhi'], 2) }}</td>
                                    <td>{{ number_format($summary_data['diolah_hi'], 2) }}</td>
                                    <td>{{ number_format($summary_data['diolah_sdhi'], 2) }}</td>
                                    <td>{{ number_format($summary_data['stok_akhir'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold"> Daftar Pengolahan Basah </div>
                    <div class="card-body table-responsive">
                        @if ($errors->any())
                            <div class="alert alert-danger"><ul class="mb-0">
                                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul></div>
                        @endif
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
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th> <th>Tanggal</th> <th>Bak Maturasi</th> <th>Jenis</th>
                                    <th>Berat Truck (Kg)</th> <th>Berat Timbang (Kg)</th> <th>Netto Basah (Kg)</th>
                                    <th>K3%</th> <th>Netto Kering (Kg)</th> <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_pengolahan as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->bak_maturasi ?? '-' }}</td>
                                        <td>{{ $item->jenis ?? '-' }}</td>
                                        <td>{{ number_format($item->berat_truck, 2) }}</td>
                                        <td>{{ number_format($item->berat_timbang, 2) }}</td>
                                        <td>{{ number_format($item->netto_basah, 2) }}</td>
                                        <td>{{ is_numeric($item->k3) ? number_format($item->k3, 2) : '-' }}</td>
                                        <td>{{ is_numeric($item->netto_kering) ? number_format($item->netto_kering, 2) : '-' }}</td>
                                        <td>
                                            <div class="action-buttons">
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
                        <tfoot>
                            
                            <tr>
                                <td colspan="8"></td> <td class="total-label">Total DS</td> <td>{{ number_format($total_data['total_ds_netto_kering'], 2) }}</td> </tr>
                            <tr>
                                <td colspan="8"></td> <td class="total-label">Total PT</td> <td>{{ number_format($total_data['total_pt_netto_kering'], 2) }}</td> </tr>
                            <tr>
                                <td colspan="8"></td> <td class="total-label">Jumlah</td> <td>{{ number_format($total_data['jumlah_netto_kering'], 2) }}</td> </tr>
                        </tfoot>
                        </table>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="main-footer"> @include('template.footer') </footer>
</div>
@include('template.script')

{{-- MODAL TAMBAH (K3 DIHAPUS) --}}
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
                        <label>Bak Maturasi</label>
                        <select name="bak_maturasi" class="form-control" required>
                            <option value="">-- Pilih Bak --</option>
                            @for ($i = 1; $i <= 49; $i++)
                                @php $bakName = "Bak Maturasi " . $i; @endphp
                                <option value="{{ $bakName }}" {{ old('bak_maturasi') == $bakName ? 'selected' : '' }}>{{ $bakName }}</option>
                            @endfor
                        </select>
                     </div>
                     <div class="form-group">
                        <label>Jenis</label>
                        <select name="jenis" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="PT" {{ old('jenis') == 'PT' ? 'selected' : '' }}>PT</option>
                            <option value="DS" {{ old('jenis') == 'DS' ? 'selected' : '' }}>DS</option>
                        </select>
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
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                     <button type="submit" class="btn btn-success">Simpan</button>
                 </div>
             </form>
         </div>
     </div>
</div>

{{-- MODAL EDIT (K3 DIHAPUS) --}}
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
                            <option value="MB5">MB5</option>
                            <option value="SW">SW</option>
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

{{-- MODAL DETAIL (Tidak berubah) --}}
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

{{-- SCRIPTS (Kalkulasi Kering DIHAPUS) --}}
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function() {
    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
    @endif

    // --- Inisialisasi Filter Tanggal ---
    var fpMin = flatpickr("#min-date", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        // === TAMBAHAN 1: Set default ke hari ini ===
        defaultDate: "today"
    });
    var fpMax = flatpickr("#max-date", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        // === TAMBAHAN 2: Set default ke hari ini ===
        defaultDate: "today"
    });

    function parseDMY(dateStr){
        var parts = dateStr.split('-'); if(parts.length!==3) return null; return new Date(parts[2], parts[1]-1, parts[0]);
    }

    // --- Fungsi Filter DataTables (Tetap Sama) ---
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var min = $('#min-date').val(), max = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!tableDateStr || tableDateStr === '-') return true; var tableDate = parseDMY(tableDateStr); if (!tableDate) return true;
        var minDate = min ? new Date(min + 'T00:00:00') : null, maxDate = max ? new Date(max + 'T23:59:59') : null;
        if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; return false;
    });

    // --- Inisialisasi DataTables ---
    var table = $('#dataTable').DataTable({"order": [[1,"desc"]]});

    // === TAMBAHAN 3: Terapkan filter tanggal hari ini saat load ===
    table.draw();

    // --- Event Listener Tombol Filter & Reset ---
    $('#filter-btn').on('click', function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').on('click', function(e){
        e.preventDefault();
        fpMin.setDate("today"); // Kembalikan ke hari ini saat reset
        fpMax.setDate("today");
        // Beri sedikit jeda agar flatpickr selesai update sebelum draw
        setTimeout(function() { table.search('').draw(); }, 100);
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- Format Angka dan Tanggal (Tetap Sama) ---
    function formatNumber(num, precision = 2) {
        if (num === null || num === undefined || num === '') return '-';
        num = parseFloat(num);
        return num.toLocaleString('id-ID', { minimumFractionDigits: precision, maximumFractionDigits: precision });
    }
    function formatTanggalDetail(dateStr) {
        if (!dateStr) return '-';
        try {
            var dateObj = new Date(dateStr + 'T00:00:00');
            if (isNaN(dateObj.getTime())) return '-';
            return dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        } catch (e) { return '-'; }
    }

    // --- Perhitungan Otomatis Modal Tambah (Tetap Sama) ---
    function hitungNettoTambah() {
        var truck = parseFloat($('#add_berat_truck').val()) || 0;
        var timbang = parseFloat($('#add_berat_timbang').val()) || 0;
        var netto_basah = timbang > truck ? timbang - truck : 0;
        $('#add_netto_basah').val(netto_basah.toFixed(2));
    }
    $('#add_berat_truck, #add_berat_timbang').on('input', hitungNettoTambah);

    // --- Perhitungan Otomatis Modal Edit (Tetap Sama) ---
    function hitungNettoEdit() {
        var truck = parseFloat($('#editBeratTruck').val()) || 0;
        var timbang = parseFloat($('#editBeratTimbang').val()) || 0;
        var netto_basah = timbang > truck ? timbang - truck : 0;
        $('#editNettoBasah').val(netto_basah.toFixed(2));
    }
    $(document).on('input', '#editBeratTruck, #editBeratTimbang', hitungNettoEdit);

    // --- AJAX DETAIL (Tetap Sama) ---
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id');
        var url = "{{ url('pengolahan_basah') }}/" + id;
        $.get(url, function(data){
            $('#detailTanggal').text(formatTanggalDetail(data.tanggal));
            $('#detailBakMaturasi').text(data.bak_maturasi ?? '-');
            $('#detailJenis').text(data.jenis ?? '-');
            $('#detailBeratTruck').text(formatNumber(data.berat_truck) + ' Kg');
            $('#detailBeratTimbang').text(formatNumber(data.berat_timbang) + ' Kg');
            $('#detailNettoBasah').text(formatNumber(data.netto_basah) + ' Kg');
            var k3Val = formatNumber(data.k3);
            $('#detailK3').text(k3Val !== '-' ? k3Val + ' %' : '-');
            $('#detailNettoKering').text(formatNumber(data.netto_kering) + ' Kg');
            $('#modalDetail').modal('show');
        }).fail(function(){ alert('Gagal memuat detail.'); });
    });

    // --- AJAX EDIT (Tetap Sama) ---
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
        }).fail(function(){ alert('Gagal memuat data edit.'); });
    });
});
</script>
</body>
</html>