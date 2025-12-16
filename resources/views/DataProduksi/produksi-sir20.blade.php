<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Produksi SIR 20</title>
    
    {{-- CSS Libraries --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- CSS Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    
    <style>
        /* --- STYLE TABEL --- */
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; white-space: nowrap; padding: 0.5rem; vertical-align: middle !important; }
        .table thead th { text-align: center !important; background-color: #f8f9fa; font-weight: bold; color: #333; }
        .font-weight-bold { font-weight: 700 !important; }

        /* --- PERBAIKAN CSS SELECT2 MODERN --- */
        
        /* 1. KOTAK INPUT UTAMA (Wadah) */
        .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: 38px !important;
            max-height: 120px !important; /* Batasi tinggi maksimal */
            overflow-y: auto !important;   /* Scroll jika penuh */
            border: 1px solid #ced4da !important;
            padding: 4px !important;
            border-radius: 4px;
        }

        /* 2. BADGE ITEM (Hijau) - Dibuat Pill Shape */
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            background-color: #28a745 !important; /* Hijau Sukses */
            color: #fff !important;
            border: none !important; /* Hilangkan border kotak */
            border-radius: 50rem !important; /* BENTUK PILL / BULAT */
            padding: 4px 12px 4px 8px !important; /* Spasi dalam badge */
            font-size: 0.85rem !important;
            margin: 3px !important;
            
            /* Flexbox agar teks dan X rata tengah */
            display: inline-flex !important;
            align-items: center !important;
            float: none !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* 3. TOMBOL HAPUS (X) - Dibuat Bersih Tanpa Garis */
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff !important;
            opacity: 0.8;
            margin-right: 8px !important; /* Jarak antara X dan Teks */
            border: none !important; /* HAPUS GARIS JELEK DI KANAN X */
            background: transparent !important;
            padding: 0 !important;
            font-size: 1.1rem !important;
            font-weight: 300 !important;
            line-height: 0.8 !important;
            float: none !important;
            cursor: pointer;
            transition: all 0.2s;
        }

        /* Efek Hover pada tombol X */
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
            opacity: 1;
            color: #ffdddd !important; /* Sedikit merah muda saat hover */
            transform: scale(1.1); /* Sedikit membesar */
        }

        /* 4. Input Search Placeholder */
        .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
            margin-top: 5px !important;
            margin-left: 5px !important;
        }

        /* Z-Index Fix untuk Modal */
        .select2-container { z-index: 9999 !important; }
        .select2-dropdown { z-index: 9999 !important; }
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
                    <div class="card-header bg-success text-white"><strong>Riwayat Produksi Harian</strong></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped dt-responsive nowrap" id="dataTable" width="100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Tanggal</th>
                                        <th>Ruang Maturasi</th> 
                                        <th>Jam Kerja</th>
                                        <th>Bales Dipress</th>
                                        <th>Total Produksi (Kg)</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($history as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>
                                            @php $ruangan = explode(', ', $item->remah_ruang_maturasi); @endphp
                                            @foreach($ruangan as $r)
                                                <span class="badge badge-info mb-1">{{ $r }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-center">{{ $item->jam_kerja }} Jam</td>
                                        <td class="text-center">{{ $item->jml_bales_press }}</td>
                                        <td class="text-center font-weight-bold text-success">{{ number_format($item->kg_press, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-warning btn-sm btn-edit" data-data="{{ json_encode($item) }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
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

{{-- MODAL FORM PRODUKSI --}}
<div class="modal fade" id="modalFormProduksi" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form action="{{ route('produksi-sir20.store') }}" method="POST" id="formProduksi">
                @csrf
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle">LAPORAN PRODUKSI HARIAN (F-PROD-06)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body bg-light">
                    {{-- TANGGAL LAPORAN --}}
                    <div class="row justify-content-center mb-3">
                        <div class="col-md-4">
                            <label class="font-weight-bold text-center w-100">TANGGAL LAPORAN</label>
                            <input type="date" name="tanggal" id="inputTanggal" class="form-control font-weight-bold text-center" required style="font-size: 1.2rem;">
                        </div>
                    </div>

                    <div class="row">
                        {{-- KOLOM 1: REMAHAN & PENGERINGAN --}}
                        <div class="col-md-4">
                            <div class="card card-outline card-success mb-3">
                                <div class="card-header py-1"><h6 class="card-title font-weight-bold mb-0">1. REMAHAN DIPROSES</h6></div>
                                <div class="card-body py-2">
                                    
                                    {{-- ✅ INPUT SELECT2 --}}
                                    <div class="form-group mb-2">
                                        <label class="small mb-1 font-weight-bold">Ruang Maturasi (Pilih 1 atau Lebih)</label>
                                        <select name="remah_ruang_maturasi[]" id="remah_ruang_maturasi" class="form-control select2" multiple="multiple" style="width: 100%;">
                                            @foreach($daftar_maturasi as $maturasi)
                                                <option value="{{ $maturasi->uraian }}">
                                                    {{ $maturasi->uraian }} (Stok: {{ number_format($maturasi->stok_akhir, 0, ',', '.') }} Kg)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">Berat (Kg)</label>
                                        <div class="col-7"><input type="number" name="remah_berat" id="remah_berat" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">Tgl Masuk</label>
                                        <div class="col-7"><input type="date" name="remah_tgl_masuk" id="remah_tgl_masuk" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">Umur (Hari)</label>
                                        <div class="col-7"><input type="number" name="remah_umur" id="remah_umur" class="form-control form-control-sm"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-warning">
                                <div class="card-header py-1"><h6 class="card-title font-weight-bold mb-0">2. PENGERINGAN (DRYER)</h6></div>
                                <div class="card-body py-2">
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">A. Jam Start</label>
                                        <div class="col-7"><input type="time" name="dryer_jam_start" id="dryer_jam_start" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">B. Trl Masuk</label>
                                        <div class="col-7"><input type="number" name="dryer_troli_masuk" id="dryer_troli_masuk" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">C. Akt Temp</label>
                                        <div class="col-7"><input type="text" name="dryer_aktual_temp" id="dryer_aktual_temp" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">D. Wkt Cycle</label>
                                        <div class="col-7"><input type="text" name="dryer_waktu_cycle" id="dryer_waktu_cycle" class="form-control form-control-sm"></div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">F. Trl Keluar</label>
                                        <div class="col-7"><input type="number" name="dryer_troli_keluar" id="dryer_troli_keluar" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-5 col-form-label-sm">G. Jam Stop</label>
                                        <div class="col-7"><input type="time" name="dryer_jam_stop" id="dryer_jam_stop" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1 bg-light p-1">
                                        <label class="col-5 col-form-label-sm text-danger">H. Jam Jalan</label>
                                        <div class="col-7"><input type="number" name="dryer_jam_jalan" id="dryer_jam_jalan" class="form-control form-control-sm font-weight-bold" step="0.1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM 2: HASIL PRODUKSI (BURNER/PRESS) --}}
                        <div class="col-md-4">
                            <div class="card card-outline card-primary h-100">
                                <div class="card-header py-1"><h6 class="card-title font-weight-bold mb-0">PRODUKSI / PRESS</h6></div>
                                <div class="card-body py-2">
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">I. Jml Bales Press</label>
                                        <div class="col-6"><input type="number" name="jml_bales_press" id="jml_bales_press" class="form-control form-control-sm font-weight-bold text-center bg-warning"></div>
                                    </div>
                                    <div class="form-group row mb-2 p-2" style="border: 2px dashed #28a745; background-color: #e8f5e9;">
                                        <label class="col-12 col-form-label-sm text-success text-center">J. KG YANG DI PRESS (1 x 35 Kg)</label>
                                        <div class="col-12">
                                            <input type="number" name="kg_press" id="kg_press" class="form-control font-weight-bold text-center text-success" style="font-size: 1.5rem;" readonly>
                                            <small class="text-muted d-block text-center">*Otomatis masuk ke WIP</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">K. Cap/Jam</label>
                                        <div class="col-6"><input type="number" name="capacity_per_jam" id="capacity_per_jam" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">L. Jam Kerja</label>
                                        <div class="col-6"><input type="number" name="jam_kerja" id="jam_kerja" class="form-control form-control-sm" step="0.1"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">M. Produktivitas</label>
                                        <div class="col-6"><input type="number" name="produktivitas" id="produktivitas" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">N. Kg SIR/Cake</label>
                                        <div class="col-6"><input type="number" name="kg_sir_cake" id="kg_sir_cake" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">O. Kontaminasi</label>
                                        <div class="col-6">
                                            <select name="kontaminasi_logam" id="kontaminasi_logam" class="form-control form-control-sm">
                                                <option value="Tidak">Tidak Ada</option>
                                                <option value="Ada">Ada</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">P. Berat (Gram)</label>
                                        <div class="col-6"><input type="number" name="berat_kontaminan" id="berat_kontaminan" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM 3: BAHAN BAKAR & PACKING --}}
                        <div class="col-md-4">
                            <div class="card card-outline card-secondary mb-3">
                                <div class="card-header py-1"><h6 class="card-title font-weight-bold mb-0">UTILITAS & BAHAN BAKAR</h6></div>
                                <div class="card-body py-2">
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">E(1). Solar (L)</label>
                                        <div class="col-6"><input type="number" name="bb_solar" id="bb_solar" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">E(2). Batu (Kg)</label>
                                        <div class="col-6"><input type="number" name="bb_batubara" id="bb_batubara" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">E(3). Cangkang</label>
                                        <div class="col-6"><input type="number" name="bb_cangkang" id="bb_cangkang" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <hr class="my-1">
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">Q. Jam Genset</label>
                                        <div class="col-6"><input type="number" name="jam_genset" id="jam_genset" class="form-control form-control-sm" step="0.1"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">R. Solar/Ton</label>
                                        <div class="col-6"><input type="number" name="r_solar_ton" id="r_solar_ton" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">S. Listrik (Kwh)</label>
                                        <div class="col-6"><input type="number" name="listrik_kwh" id="listrik_kwh" class="form-control form-control-sm" step="0.01"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-info">
                                <div class="card-header py-1"><h6 class="card-title font-weight-bold mb-0">3. PACKING ROOM</h6></div>
                                <div class="card-body py-2">
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">A. Pallet Diisi</label>
                                        <div class="col-6"><input type="number" name="pack_pallet_sw" id="pack_pallet_sw" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-6 col-form-label-sm">B. No Batch</label>
                                        <div class="col-6"><input type="text" name="pack_nomor" id="pack_nomor" class="form-control form-control-sm" placeholder="cth: 3635 s/d 3646"></div>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label class="col-form-label-sm">Catatan Shift</label>
                                        <textarea name="keterangan" id="keterangan" class="form-control form-control-sm" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fas fa-save mr-1"></i> SIMPAN LAPORAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('template.script')
{{-- JS Dependencies --}}
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
{{-- JS Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
        
        // JS Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: "-- Pilih Bak Maturasi --",
            allowClear: true,
            dropdownParent: $('#modalFormProduksi'),
            width: '100%' 
        });

        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 1500 });
        @endif

        // Hitung Otomatis Kg Press
        $('#jml_bales_press').on('input', function() {
            let bales = parseFloat($(this).val()) || 0;
            $('#kg_press').val((bales * 35).toFixed(2));
        });

        window.bukaModalTambah = function() {
            $('#formProduksi')[0].reset();
            // Reset Select2
            $('#remah_ruang_maturasi').val(null).trigger('change');
            
            $('#modalTitle').text('LAPORAN PRODUKSI HARIAN (F-PROD-06)');
            $('#inputTanggal').val(new Date().toISOString().split('T')[0]);
            $('#modalFormProduksi').modal('show');
        }

        // Logic EDIT
        $('.btn-edit').click(function() {
            let data = $(this).data('data');
            $('#formProduksi')[0].reset();
            
            $.each(data, function(key, value) {
                if (key !== 'remah_ruang_maturasi') {
                    let input = $('#' + key);
                    if(input.length) input.val(value);
                }
            });

            if (data.remah_ruang_maturasi) {
                let selectedValues = data.remah_ruang_maturasi.split(', ');
                $('#remah_ruang_maturasi').val(selectedValues).trigger('change');
            } else {
                $('#remah_ruang_maturasi').val(null).trigger('change');
            }

            $('#modalFormProduksi').modal('show');
        });
    });
</script>
</body>
</html>