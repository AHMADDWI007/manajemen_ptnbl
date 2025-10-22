<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-control[readonly] { background-color: #e9ecef; opacity: 1; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; padding: 0.5rem; }
        label { margin-bottom: 0.2rem; font-weight: 500;}
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
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                 <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Data Pengolahan Maturasi</strong>
                    </div>
                     <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
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
                                 <button id="filter-btn" class="btn btn-primary btn-sm me-2">Filter</button>
                                 <button id="reset-filter" class="btn btn-secondary btn-sm" style="margin-left: 8px;">Reset</button>
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center align-middle" id="dataTable">
                                <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal Input</th>
                                    <th>Uraian</th>
                                    <th>Stok Awal (Kg)</th>
                                    <th>Tgl Masuk Stok</th>
                                    <th>Umur</th>
                                    <th>Diolah (Kg)</th>
                                    <th>Mutasi (Kg)</th>
                                    <th>Masuk HI (Kg)</th>
                                    <th>Stok Akhir (Kg)</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                {{-- Mengurutkan data berdasarkan nomor Bak Maturasi --}}
                                @forelse ($data_maturasi->sortBy(function($item) {
                                    return (int) preg_replace('/[^0-9]/', '', $item->uraian);
                                }) as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->created_at->format('d-m-Y') }}</td>
                                        <td>{{ $item->uraian ?? '-' }}</td>
                                        <td>{{ number_format($item->stok_awal ?? 0, 2, ',', '.') }}</td>
                                        <td>
                                            {{-- Logika Tgl Masuk Stok --}}
                                            @if($item->tgl_masuk)
                                                {{ $item->tgl_masuk->format('d-m-Y') }}
                                            @else
                                                KOSONG
                                            @endif
                                        </td>
                                        {{-- PERBAIKAN: Menampilkan kolom 'umur' langsung dari database --}}
                                        <td>{{ $item->umur ?? 0 }} hari</td>
                                        <td>{{ number_format($item->diolah ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->mutasi ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->masuk_hi ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->stok_akhir ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('maturasi.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline;">
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
                                        <td colspan="12" class="text-center text-muted">Belum ada data maturasi.</td>
                                    </tr>
                                @endforelse
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
                    <h5 class="modal-title fw-bold">Input Data Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">

                        {{-- PERBAIKAN: Tambah Input Tanggal Harian --}}
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Input Harian</label>
                            <input type="date" name="tanggal_input" id="tanggal_input_tambah" class="form-control form-control-sm" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label>Uraian</label>
                            <select name="uraian" id="uraian" class="form-control form-control-sm" required>
                                <option value="" disabled selected>-- Pilih Bak Maturasi --</option>
                                @for ($i = 1; $i <= 49; $i++)
                                    <option value="Di Bak Maturasi {{ $i }}">Di Bak Maturasi {{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Stok Awal (Kg)</label>
                            <input type="number" name="stok_awal" id="stok_awal" class="form-control form-control-sm" readonly required step="0.01">
                        </div>

                        {{-- PERBAIKAN: Ganti 'umur_display' menjadi input 'umur' --}}
                        <div class="col-md-6 mb-3">
                            <label>Umur (Hari)</label>
                            <input type="number" name="umur" id="umur" class="form-control form-control-sm" readonly required>
                        </div>

                        {{-- PERBAIKAN: Hapus field tgl_masuk_display dan tgl_masuk_hidden --}}

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
                            <input type="number" name="masuk_hi" id="masuk_hi" class="form-control form-control-sm" value="0" step="0.01" min="0">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Asal Bokar</label>
                            <input type="text" name="asal_bokar" id="asal_bokar" class="form-control form-control-sm" value="Petani">
                        </div>
                        <hr class="col-12 my-2">
                        <div class="col-md-6 mb-3">
                            <label>Perkiraan Stok Akhir (Kg)</label>
                            <input type="text" id="stok_akhir_display" class="form-control form-control-sm" readonly>
                        </div>
                        
                        {{-- PERBAIKAN: Ganti 'keterangan_display' menjadi input 'keterangan' --}}
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
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Data Maturasi</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Uraian</dt><dd class="col-sm-7" id="detailUraian">-</dd>
                    <dt class="col-sm-5">Stok Awal (Kg)</dt><dd class="col-sm-7" id="detailStokAwal">-</dd>
                    <dt class="col-sm-5">Tgl Masuk Stok</dt><dd class="col-sm-7" id="detailTglMasuk">-</dd>
                    <dt class="col-sm-5">Diolah (Kg)</dt><dd class="col-sm-7" id="detailDiolah">-</dd>
                    <dt class="col-sm-5">Mutasi (Kg)</dt><dd class="col-sm-7" id="detailMutasi">-</dd>
                    <dt class="col-sm-5">Masuk HI (Kg)</dt><dd class="col-sm-7" id="detailMasukHi">-</dd>
                    <dt class="col-sm-5">Stok Akhir (Kg)</dt><dd class="col-sm-7" id="detailStokAkhir">-</dd>
                    <dt class="col-sm-5">Asal Bokar</dt><dd class="col-sm-7" id="detailAsalBokar">-</dd>
                    <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7" id="detailKeterangan">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Data Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        
                        {{-- PERBAIKAN: Tambah Input Tanggal Harian untuk Edit --}}
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
                         
                         {{-- PERBAIKAN: Tambahkan input Umur --}}
                        <div class="col-md-6 mb-3">
                            <label>Umur (Hari)</label>
                            <input type="number" name="umur" id="editUmur" class="form-control form-control-sm" step="1" required>
                        </div>
                         
                         <div class="col-md-6 mb-3">
                            <label>Tanggal Masuk Bokar</label>
                            {{-- tgl_masuk bisa null, jadi tidak 'required' --}}
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
                        <div class="col-md-6 mb-3">
                            <label>Asal Bokar</label>
                            <input type="text" name="asal_bokar" id="editAsalBokar" class="form-control form-control-sm">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm" readonly> {{-- Keterangan di-set readonly karena di-update oleh tanggal_input --}}
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


@include('template.script')
{{-- PERBAIKAN: Menambahkan kembali script manual untuk memastikan semua library termuat --}}
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    
    // ----- SEMUA EVENT HANDLER DIDAFTARKAN DULU -----

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function formatNumber(num) {
        return Number(num).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // --- LOGIKA MODAL TAMBAH ---

    // PERBAIKAN: Fungsi hitung stok akhir display (dihapus logika keterangan)
    function calculateStokAkhirDisplay() {
        let stok_awal = parseFloat($('#stok_awal').val()) || 0;
        let diolah = parseFloat($('#diolah').val()) || 0;
        let mutasi = parseFloat($('#mutasi').val()) || 0;
        let masuk_hi = parseFloat($('#masuk_hi').val()) || 0;
        let stok_akhir = stok_awal - diolah - mutasi + masuk_hi;
        $('#stok_akhir_display').val(formatNumber(stok_akhir));
    }
    
    // PERBAIKAN: Fungsi baru untuk update keterangan berdasarkan tanggal
    function updateKeterangan() {
        let tanggalInput = $('#tanggal_input_tambah').val();
        if (tanggalInput) {
            try {
                let dateObj = new Date(tanggalInput + 'T00:00:00');
                let options = { day: 'numeric', month: 'long' };
                $('#keterangan').val(dateObj.toLocaleDateString('id-ID', options));
            } catch (e) {
                $('#keterangan').val('Tanggal Invalid');
            }
        } else {
            $('#keterangan').val('');
        }
    }

    // PERBAIKAN: Fungsi baru untuk fetch data (menggantikan logika lama)
    function fetchPreviousData() {
        const selectedUraian = $('#uraian').val();
        const selectedDate = $('#tanggal_input_tambah').val();

        // Hanya jalankan jika KEDUA field terisi
        if (selectedUraian && selectedDate) {
            $.ajax({
                // PERBAIKAN: Gunakan route name yang benar
                url: "{{ route('maturasi.getPreviousData') }}", 
                type: 'GET',
                // PERBAIKAN: Kirim 'uraian' dan 'tanggal_input'
                data: { 
                    uraian: selectedUraian,
                    tanggal_input: selectedDate 
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        $('#stok_awal').val(parseFloat(data.stok_awal).toFixed(2));
                        $('#umur').val(data.umur); // Isi input 'umur'
                    } else {
                        $('#stok_awal').val('0.00');
                        $('#umur').val(0);
                    }
                    calculateStokAkhirDisplay(); // Hitung ulang display stok akhir
                },
                error: function() {
                    alert('Gagal mengambil data sebelumnya. Mengatur ke nilai default.');
                    $('#stok_awal').val('0.00');
                    $('#umur').val(0);
                    calculateStokAkhirDisplay();
                }
            });
        } else {
            // Reset jika salah satu field kosong
            $('#stok_awal').val('0.00');
            $('#umur').val(0);
            calculateStokAkhirDisplay();
        }
    }

    // PERBAIKAN: Hapus blok $('#uraian').on('change', ...) yang lama

    // PERBAIKAN: Tambahkan listener baru untuk 'uraian' dan 'tanggal_input'
    $('#uraian').on('change', fetchPreviousData);
    $('#tanggal_input_tambah').on('change', function() {
        fetchPreviousData();    // Ambil data stok & umur
        updateKeterangan();     // Update field keterangan
    });

    // PERBAIKAN: Listener untuk input di modal tambah
    $('#diolah, #mutasi, #masuk_hi, #stok_awal').on('input', calculateStokAkhirDisplay);
    
    // Reset form tambah saat modal dibuka
    $('#modalTambah').on('show.bs.modal', function () {
         $('#formTambah')[0].reset();
         // Set default value
         $('#asal_bokar').val('Petani');
         $('#diolah, #mutasi, #masuk_hi').val('0');
         $('#stok_awal, #umur, #stok_akhir_display, #keterangan').val('');
         // Set tanggal input hari ini
         let today = new Date().toISOString().split('T')[0];
         $('#tanggal_input_tambah').val(today).trigger('change'); // Set dan trigger change
    });
    
    // PERBAIKAN: Listener untuk tanggal di modal edit
    $('#editTanggalInput').on('change', function() {
         let tanggalInput = $(this).val();
         if (tanggalInput) {
            try {
                let dateObj = new Date(tanggalInput + 'T00:00:00');
                let options = { day: 'numeric', month: 'long' };
                $('#editKeterangan').val(dateObj.toLocaleDateString('id-ID', options));
            } catch (e) {
                $('#editKeterangan').val('Tanggal Invalid');
            }
        } else {
            $('#editKeterangan').val('');
        }
    });


    // --- LOGIKA DETAIL & EDIT ---

    // === DETAIL ===
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');
        $.get('/maturasi/' + id, function (data) {
            $('#detailUraian').text(data.uraian || '-');
            $('#detailStokAwal').text(formatNumber(data.stok_awal));
            $('#detailTglMasuk').text(data.tgl_masuk ? new Date(data.tgl_masuk).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) : 'KOSONG');
            $('#detailDiolah').text(formatNumber(data.diolah));
            $('#detailMutasi').text(formatNumber(data.mutasi));
            $('#detailMasukHi').text(formatNumber(data.masuk_hi));
            $('#detailStokAkhir').text(formatNumber(data.stok_akhir));
            $('#detailAsalBokar').text(data.asal_bokar || '-');
            $('#detailKeterangan').text(data.keterangan || '-');
            $('#modalDetail').modal('show');
        });
    });

    // === EDIT ===
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/maturasi/' + id + '/edit', function (data) {
            
            // PERBAIKAN: Isi input tanggal harian (dari created_at)
            $('#editTanggalInput').val(data.tanggal_input_edit).trigger('change'); // Isi dan trigger change untuk update keterangan

            $('#editUraian').val(data.uraian);
            $('#editStokAwal').val(data.stok_awal);
            $('#editUmur').val(data.umur); // Isi input umur
            $('#editTglMasuk').val(data.tgl_masuk ? data.tgl_masuk.split('T')[0] : '');
            $('#editDiolah').val(data.diolah);
            $('#editMutasi').val(data.mutasi);
            $('#editMasukHi').val(data.masuk_hi);
            $('#editAsalBokar').val(data.asal_bokar);
            $('#editKeterangan').val(data.keterangan); // Keterangan akan di-override oleh trigger change di atas
            $('#formEdit').attr('action', '/maturasi/' + id);
            $('#modalEdit').modal('show');
        });
    });

    @if (session('success'))
        alert("{{ session('success') }}");
    @endif

    // ----- INISIALISASI DATATABLE DI AKHIR -----
    var table = $('#dataTable').DataTable({ 
        "searching": false,
        "ordering": false // Menonaktifkan sorting bawaan DataTables
    });

    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var minStr = $('#min-date').val();
            var maxStr = $('#max-date').val();
            var dateStr = data[1] || ''; // Ambil data dari kolom Tanggal Input (indeks 1)

            if ( ( minStr === '' && maxStr === '' ) ) { return true; }
            
            // Konversi tanggal dari format dd-mm-yyyy ke yyyy-mm-dd
            var parts = dateStr.split('-');
            if (parts.length !== 3) return false;
            var tableDate = new Date(parts[2], parts[1] - 1, parts[0]);
            
            var min = minStr ? new Date(minStr) : null;
            var max = maxStr ? new Date(maxStr) : null;
            
            if (min) min.setHours(0,0,0,0);
            if (max) max.setHours(23, 59, 59, 999);

            return ( min === null && tableDate <= max ) || ( min <= tableDate && max === null ) || ( min <= tableDate && tableDate <= max );
        }
    );

    $('#filter-btn').on('click', function() { table.draw(); });
    $('#reset-filter').on('click', function() {
        $('#min-date').val('');
        $('#max-date').val('');
        table.draw();
    });
});
</script>
</body>
</html>