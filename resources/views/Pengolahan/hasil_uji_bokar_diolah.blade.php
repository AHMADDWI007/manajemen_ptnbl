<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Bokar Diolah</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; text-align: center; }
        #dataTable th, #dataTable td { text-align: center !important; vertical-align: middle !important; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Input K3 Bokar Diolah</h1>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambahK3">
                    <i class="fas fa-plus-circle"></i> Input K3
                </button>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold"> Daftar Hasil Uji Bokar Diolah </div>
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
                                    <th>Netto Basah (Kg)</th> <th>K3 (%)</th> <th>Netto Kering (Kg)</th> <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_diolah as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->bak_maturasi ?? '-' }}</td>
                                        <td>{{ $item->jenis ?? '-' }}</td>
                                        <td>{{ is_numeric($item->netto_basah) ? number_format($item->netto_basah, 2) : '-' }}</td>
                                        <td>{{ is_numeric($item->k3) ? number_format($item->k3, 2) : '-' }}</td>
                                        <td>{{ is_numeric($item->netto_kering) ? number_format($item->netto_kering, 2) : '-' }}</td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"> <i class="fas fa-eye"></i> </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit-k3" data-id="{{ $item->id }}" title="Edit K3"> <i class="fas fa-edit"></i> </button>
                                                <form action="{{ route('hasil_uji_bokar_diolah.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block; margin:0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"> <i class="fas fa-trash"></i> </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted">Belum ada data hasil uji bokar diolah.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="main-footer"> @include('template.footer') </footer>
</div>
@include('template.script')

{{-- MODAL TAMBAH K3 (BERBEDA DARI SEBELUMNYA) --}}
<div class="modal fade" id="modalTambahK3" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <form action="{{ route('hasil_uji_bokar_diolah.store') }}" method="POST">
                 @csrf
                 <div class="modal-header bg-success text-white">
                     <h5 class="modal-title fw-bold">Input K3 Bokar Diolah</h5>
                     <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                 </div>
                 <div class="modal-body">
                     <div class="form-group">
                        <label>Pilih Bak Maturasi (Hanya yang belum diuji)</label>
                        <select name="pengolahan_basah_id" class="form-control" required>
                            <option value="">-- Pilih Bak --</option>
                            @foreach ($daftar_bak_belum_uji as $bak)
                                <option value="{{ $bak->id }}" {{ old('pengolahan_basah_id') == $bak->id ? 'selected' : '' }}>
                                    {{ $bak->bak_maturasi }} (Tgl: {{ \Carbon\Carbon::parse($bak->tanggal)->format('d-m-Y') }}, Netto: {{ $bak->netto_basah }} Kg)
                                </option>
                            @endforeach
                        </select>
                     </div>
                     <div class="form-group">
                         <label>K3 (%)</label>
                         <input type="number" name="k3" class="form-control" step="0.01" value="{{ old('k3') }}" required>
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                     <button type="submit" class="btn btn-success">Simpan K3</button>
                 </div>
             </form>
         </div>
     </div>
</div>

{{-- MODAL EDIT K3 --}}
<div class="modal fade" id="modalEditK3" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEditK3" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Edit K3 Bokar Diolah</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                         <label>Bak Maturasi</label>
                         <input type="text" id="editBakInfo" class="form-control" readonly style="background-color: #e9ecef;">
                    </div>
                    <div class="form-group">
                        <label>K3 (%)</label>
                        <input type="number" name="k3" id="editK3" class="form-control" step="0.01" required>
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

{{-- MODAL DETAIL (Data diambil dari PengolahanBasah) --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Detail Hasil Uji Bokar Diolah</h5>
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

{{-- SCRIPTS --}}
{{-- SCRIPTS --}}
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

    // --- AWAL PERBAIKAN ---
    // 1. Dapatkan tanggal hari ini dalam format YYYY-MM-DD
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // Bulan mulai dari 0
    var dd = String(today.getDate()).padStart(2, '0');
    var todayStr = yyyy + '-' + mm + '-' + dd;

    // 2. Set tanggal hari ini sebagai default di Flatpickr
    var fpMin = flatpickr("#min-date", { 
        altInput: true, 
        altFormat: "d/m/Y", 
        dateFormat: "Y-m-d",
        defaultDate: todayStr // <-- Tambahkan ini
    });
    var fpMax = flatpickr("#max-date", { 
        altInput: true, 
        altFormat: "d/m/Y", 
        dateFormat: "Y-m-d",
        defaultDate: todayStr // <-- Tambahkan ini
    });
    // --- AKHIR PERBAIKAN ---

    function parseDMY(dateStr){
        var parts = dateStr.split('-'); if(parts.length!==3) return null; return new Date(parts[2], parts[1]-1, parts[0]);
    }
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var min = $('#min-date').val(), max = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!tableDateStr || tableDateStr === '-') return true; 
        var tableDate = parseDMY(tableDateStr); 
        if (!tableDate) return true;
        var minDate = min ? new Date(min + 'T00:00:00') : null, maxDate = max ? new Date(max + 'T23:59:59') : null;
        if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; 
        return false;
    });

    // Inisialisasi DataTable
    var table = $('#dataTable').DataTable({"order": [[1,"desc"]]});
    
    // 3. Terapkan filter (draw) SEKARANG setelah default di-set
    // Ini akan langsung memfilter tabel untuk menampilkan data hari ini saja
    table.draw();

    $('#filter-btn').on('click', function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').on('click', function(e){ e.preventDefault(); fpMin.clear(); fpMax.clear(); table.search('').draw(); });
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ... sisa kode Anda (formatNumber, formatTanggalDetail, AJAX, dll) ...
    // ... (Saya salin sisa kode Anda di bawah ini agar lengkap) ...

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

    // --- AJAX DETAIL (Mengambil data PengolahanBasah) ---
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id');
        var url = "{{ url('hasil_uji_bokar_diolah') }}/" + id; // Route 'show'
        $.get(url, function(data){
            $('#detailTanggal').text(formatTanggalDetail(data.tanggal));
            $('#detailBakMaturasi').text(data.bak_maturasi ?? '-');
            $('#detailJenis').text(data.jenis ?? '-');
            $('#detailNettoBasah').text(formatNumber(data.netto_basah) + ' Kg');
            var k3Val = formatNumber(data.k3);
            $('#detailK3').text(k3Val !== '-' ? k3Val + ' %' : '-');
            $('#detailNettoKering').text(formatNumber(data.netto_kering) + ' Kg');
            $('#modalDetail').modal('show');
        }).fail(function(){ alert('Gagal memuat detail.'); });
    });

    // --- AJAX EDIT K3 ---
    $(document).on('click','.btn-edit-k3',function(){
        var id = $(this).data('id');
        var urlGet = "{{ url('hasil_uji_bokar_diolah') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil_uji_bokar_diolah') }}/" + id;
        $.get(urlGet, function(data){
            $('#editBakInfo').val(data.bak_maturasi + ' (Tgl: ' + formatTanggalDetail(data.tanggal) + ')');
            $('#editK3').val(data.k3);
            $('#formEditK3').attr('action', urlPost);
            $('#modalEditK3').modal('show');
        }).fail(function(){ alert('Gagal memuat data edit K3.'); });
    });
});
</script>
</body>
</html>