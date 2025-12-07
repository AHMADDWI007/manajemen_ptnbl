<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Produksi SIR 20</title>
    
    {{-- CSS Libraries Standard --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Style Tabel Konsisten */
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            white-space: nowrap;
            padding: 0.5rem;
            vertical-align: middle !important;
        }
        .table thead th {
            text-align: center !important;
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        /* Helper Alignment */
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .font-weight-bold { font-weight: 700 !important; }

        /* Modal Styling yang Lebih Rapi */
        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
        }
        .form-section-title {
            font-size: 0.9rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        label { font-weight: 600; font-size: 0.85rem; margin-bottom: 4px; }
        .form-control-sm { font-size: 0.85rem; }
        
        .bg-highlight { background-color: #fff3cd; border: 1px solid #ffeeba; } 
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Data Produksi SIR 20</h3>
                <button class="btn btn-success btn-sm fw-bold shadow-sm" onclick="bukaModalTambah()">
                    <i class="fas fa-plus-circle"></i> Input Laporan
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong>Riwayat Produksi Harian</strong>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            {{-- Tabel Standar Responsive --}}
                            <table class="table table-bordered table-striped table-hover dt-responsive nowrap" id="dataTable" width="100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Tanggal</th>
                                        <th>Jam Kerja</th>
                                        <th>Trolly Masuk</th>
                                        <th>Trolly Keluar</th>
                                        <th>Bales Dipress</th>
                                        <th>Total Produksi (Kg)</th>
                                        <th>Solar/Ton</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($history as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td class="text-center">{{ $item->jam_kerja }} Jam</td>
                                        <td class="text-center">{{ $item->dryer_troli_masuk }}</td>
                                        <td class="text-center">{{ $item->dryer_troli_keluar }}</td>
                                        <td class="text-center">{{ $item->jml_bales_press }}</td>
                                        <td class="text-center font-weight-bold text-success">{{ number_format($item->kg_press, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ $item->r_solar_ton }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-info btn-sm btn-detail" data-data="{{ json_encode($item) }}" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm btn-edit" data-data="{{ json_encode($item) }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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

    <footer class="main-footer">@include('template.footer')</footer>

</div>

{{-- ================================================== --}}
{{-- MODAL INPUT & EDIT (LAYOUT SESUAI LHP) --}}
{{-- ================================================== --}}
<div class="modal fade" id="modalFormProduksi" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form action="{{ route('produksi_sir20.store') }}" method="POST" id="formProduksi">
                @csrf
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle">Input Laporan Produksi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body bg-light">
                    {{-- TANGGAL LAPORAN --}}
                    <div class="row justify-content-center mb-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text font-weight-bold">TANGGAL LAPORAN</span></div>
                                <input type="date" name="tanggal" id="inputTanggal" class="form-control font-weight-bold text-center" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- KOLOM KIRI: REMAHAN --}}
                        <div class="col-md-4">
                            <div class="form-section">
                                <div class="form-section-title"><i class="fas fa-cubes mr-2"></i>1. REMAHAN</div>
                                <div class="form-group row">
                                    <label class="col-5 col-form-label">Ruang Maturasi</label>
                                    <div class="col-7"><input type="text" name="remah_ruang_maturasi" id="remah_ruang_maturasi" class="form-control form-control-sm"></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-5 col-form-label">Berat (Kg)</label>
                                    <div class="col-7"><input type="number" name="remah_berat" id="remah_berat" class="form-control form-control-sm" step="0.01"></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-5 col-form-label">Tgl Masuk</label>
                                    <div class="col-7"><input type="date" name="remah_tgl_masuk" id="remah_tgl_masuk" class="form-control form-control-sm"></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-5 col-form-label">Umur (Hari)</label>
                                    <div class="col-7"><input type="number" name="remah_umur" id="remah_umur" class="form-control form-control-sm"></div>
                                </div>
                            </div>

                            <div class="form-section mt-3">
                                <div class="form-section-title"><i class="fas fa-box-open mr-2"></i>3. PACKING ROOM</div>
                                <div class="form-group row">
                                    <label class="col-5 col-form-label">Pallet Diisi</label>
                                    <div class="col-7"><input type="number" name="pack_pallet_sw" id="pack_pallet_sw" class="form-control form-control-sm"></div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-5 col-form-label">Nomor Batch</label>
                                    <div class="col-7"><input type="text" name="pack_nomor" id="pack_nomor" class="form-control form-control-sm" placeholder="cth: 2635"></div>
                                </div>
                                <div class="form-group">
                                    <label>Catatan Shift</label>
                                    <textarea name="keterangan" id="keterangan" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM TENGAH: DRYER --}}
                        <div class="col-md-4">
                            <div class="form-section h-100">
                                <div class="form-section-title"><i class="fas fa-fire mr-2"></i>2. PENGERINGAN (DRYER)</div>
                                
                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Jam Start</label>
                                        <input type="time" name="dryer_jam_start" id="dryer_jam_start" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 form-group">
                                        <label>Jam Stop</label>
                                        <input type="time" name="dryer_jam_stop" id="dryer_jam_stop" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Durasi Jalan (Jam)</label>
                                    <input type="number" name="dryer_jam_jalan" id="dryer_jam_jalan" class="form-control form-control-sm font-weight-bold text-center" step="0.1">
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Trolly Masuk</label>
                                        <input type="number" name="dryer_troli_masuk" id="dryer_troli_masuk" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 form-group">
                                        <label>Trolly Keluar</label>
                                        <input type="number" name="dryer_troli_keluar" id="dryer_troli_keluar" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Temp (°C)</label>
                                        <input type="text" name="dryer_aktual_temp" id="dryer_aktual_temp" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 form-group">
                                        <label>Cycle Time</label>
                                        <input type="text" name="dryer_waktu_cycle" id="dryer_waktu_cycle" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN: PRODUKSI & BAHAN BAKAR --}}
                        <div class="col-md-4">
                            <div class="form-section bg-highlight">
                                <div class="form-section-title text-dark"><i class="fas fa-industry mr-2"></i>4. PRODUKSI & QUALITY</div>
                                
                                <div class="form-group row">
                                    <label class="col-6 col-form-label text-primary">Jml Bales Press</label>
                                    <div class="col-6">
                                        <input type="number" name="jml_bales_press" id="jml_bales_press" class="form-control form-control-sm font-weight-bold text-center">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-6 col-form-label text-success">Total Berat (Kg)</label>
                                    <div class="col-6">
                                        <input type="number" name="kg_press" id="kg_press" class="form-control form-control-sm font-weight-bold text-center text-success" readonly>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-4 form-group px-1"><label>Cap/Jam</label><input type="number" name="capacity_per_jam" id="capacity_per_jam" class="form-control form-control-sm" step="0.01"></div>
                                    <div class="col-4 form-group px-1"><label>Produktif</label><input type="number" name="produktivitas" id="produktivitas" class="form-control form-control-sm" step="0.01"></div>
                                    <div class="col-4 form-group px-1"><label>Kg/Cake</label><input type="number" name="kg_sir_cake" id="kg_sir_cake" class="form-control form-control-sm" step="0.01"></div>
                                </div>
                                <div class="form-group row mt-2">
                                    <label class="col-6 col-form-label">Kontaminasi</label>
                                    <div class="col-6">
                                        <select name="kontaminasi_logam" id="kontaminasi_logam" class="form-control form-control-sm">
                                            <option value="Tidak">Tidak</option><option value="Ada">Ada</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section mt-3">
                                <div class="form-section-title"><i class="fas fa-gas-pump mr-2"></i>5. BAHAN BAKAR</div>
                                <div class="row">
                                    <div class="col-4 form-group px-1"><label>Solar (L)</label><input type="number" name="bb_solar" id="bb_solar" class="form-control form-control-sm" step="0.01"></div>
                                    <div class="col-4 form-group px-1"><label>Batu (Kg)</label><input type="number" name="bb_batubara" id="bb_batubara" class="form-control form-control-sm" step="0.01"></div>
                                    <div class="col-4 form-group px-1"><label>Cangkang</label><input type="number" name="bb_cangkang" id="bb_cangkang" class="form-control form-control-sm" step="0.01"></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6 form-group"><label>Genset (Jam)</label><input type="number" name="jam_genset" id="jam_genset" class="form-control form-control-sm" step="0.1"></div>
                                    <div class="col-6 form-group"><label>Jam Kerja</label><input type="number" name="jam_kerja" id="jam_kerja" class="form-control form-control-sm" step="0.1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="fas fa-save mr-1"></i> SIMPAN DATA</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================== --}}
{{-- MODAL DETAIL (READ ONLY) --}}
{{-- ================================================== --}}
<div class="modal fade" id="modalDetailProduksi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-eye mr-2"></i>Detail Produksi</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary text-center font-weight-bold mb-4" id="det_tanggal"></div>
                
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="text-info font-weight-bold border-bottom pb-2">PROSES & PACKING</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Ruang Maturasi</td><td class="font-weight-bold" id="det_remah_ruang"></td></tr>
                            <tr><td class="text-muted">Berat Remah</td><td class="font-weight-bold" id="det_remah_berat"></td></tr>
                            <tr><td class="text-muted">Tgl Masuk</td><td class="font-weight-bold" id="det_remah_tgl"></td></tr>
                            <tr><td class="text-muted">Umur</td><td class="font-weight-bold" id="det_remah_umur"></td></tr>
                            <tr><td colspan="2"><hr class="my-1"></td></tr>
                            <tr><td class="text-muted">Pallet Diisi</td><td class="font-weight-bold" id="det_pack_pallet"></td></tr>
                            <tr><td class="text-muted">No Batch</td><td class="font-weight-bold" id="det_pack_nomor"></td></tr>
                            <tr><td class="text-muted">Catatan</td><td class="font-weight-bold" id="det_keterangan"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info font-weight-bold border-bottom pb-2">DRYER & PRODUKSI</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Jam Operasional</td><td class="font-weight-bold"><span id="det_dryer_start"></span> - <span id="det_dryer_stop"></span></td></tr>
                            <tr><td class="text-muted">Trolly (In/Out)</td><td class="font-weight-bold"><span id="det_trolly_in"></span> / <span id="det_trolly_out"></span></td></tr>
                            <tr><td class="text-muted">Temp / Cycle</td><td class="font-weight-bold"><span id="det_temp"></span> / <span id="det_cycle"></span></td></tr>
                            <tr><td colspan="2"><hr class="my-1"></td></tr>
                            <tr><td class="text-primary font-weight-bold">Bales Press</td><td class="text-primary font-weight-bold h6" id="det_bales"></td></tr>
                            <tr><td class="text-success font-weight-bold">Total Kg</td><td class="text-success font-weight-bold h6" id="det_kg_press"></td></tr>
                            <tr><td colspan="2"><hr class="my-1"></td></tr>
                            <tr><td class="text-muted">Bahan Bakar</td><td class="font-weight-bold">
                                Solar: <span id="det_bb_solar"></span> | Batu: <span id="det_bb_batu"></span>
                            </td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('template.script')
{{-- DataTables & Plugins --}}
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "ordering": false,
            "language": { "search": "Cari:", "lengthMenu": "Tampilkan _MENU_ data", "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data" }
        });

        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 1500 });
        @endif

        // Hitung Otomatis Kg Press (Bales * 35)
        $('#jml_bales_press').on('input', function() {
            let bales = parseFloat($(this).val()) || 0;
            $('#kg_press').val((bales * 35).toFixed(2));
        });

        // Buka Modal Tambah
        window.bukaModalTambah = function() {
            $('#formProduksi')[0].reset();
            $('#modalTitle').text('Input Laporan Produksi');
            $('#inputTanggal').val(new Date().toISOString().split('T')[0]);
            $('#modalFormProduksi').modal('show');
        }

        // Buka Modal Edit
        $('.btn-edit').click(function() {
            let data = $(this).data('data');
            $('#formProduksi')[0].reset();
            $('#modalTitle').text('Edit Laporan Produksi');
            
            $.each(data, function(key, value) {
                let input = $('#' + key);
                if(input.length) input.val(value);
            });
            $('#inputTanggal').val(data.tanggal);
            $('#modalFormProduksi').modal('show');
        });

        // Buka Modal Detail
        $('.btn-detail').click(function() {
            let data = $(this).data('data');
            const fmt = (num) => new Intl.NumberFormat('id-ID').format(num || 0);

            $('#det_tanggal').text('Laporan Tanggal: ' + data.tanggal);
            
            $('#det_remah_ruang').text(data.remah_ruang_maturasi || '-');
            $('#det_remah_berat').text(fmt(data.remah_berat) + ' Kg');
            $('#det_remah_tgl').text(data.remah_tgl_masuk || '-');
            $('#det_remah_umur').text((data.remah_umur || 0) + ' Hari');

            $('#det_dryer_start').text(data.dryer_jam_start || '-');
            $('#det_dryer_stop').text(data.dryer_jam_stop || '-');
            $('#det_trolly_in').text(data.dryer_troli_masuk || 0);
            $('#det_trolly_out').text(data.dryer_troli_keluar || 0);
            $('#det_temp').text(data.dryer_aktual_temp || '-');
            $('#det_cycle').text(data.dryer_waktu_cycle || '-');

            $('#det_bb_solar').text(fmt(data.bb_solar));
            $('#det_bb_batu').text(fmt(data.bb_batubara));
            $('#det_bb_cangkang').text(fmt(data.bb_cangkang));

            $('#det_bales').text(data.jml_bales_press || 0);
            $('#det_kg_press').text(fmt(data.kg_press) + ' Kg');

            $('#det_pack_pallet').text(data.pack_pallet_sw || 0);
            $('#det_pack_nomor').text(data.pack_nomor || '-');
            $('#det_keterangan').text(data.keterangan || '-');

            $('#modalDetailProduksi').modal('show');
        });
    });
</script>

</body>
</html>