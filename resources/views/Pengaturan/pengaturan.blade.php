<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card-header-custom {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        .info-box-icon-custom {
            background-color: #17a2b8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            width: 70px;
        }
        .section-title {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #343a40;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        {{-- Header Halaman --}}
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold text-dark">
                            <i class="fas fa-cogs mr-2 text-success"></i> Pengaturan Sistem
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                            <li class="breadcrumb-item active">Pengaturan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Konten Utama --}}
        <div class="content">
            <div class="container-fluid">
                
                {{-- Notifikasi Sukses --}}
                @if(session('success'))
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: '{{ session('success') }}',
                            showConfirmButton: false,
                            timer: 2000,
                            toast: true,
                            position: 'top-end'
                        });
                    </script>
                @endif

                <div class="row">
                    {{-- Kolom Kiri: Form Konfigurasi --}}
                    <div class="col-md-8">
                        <div class="card card-outline card-primary shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold">
                                    <i class="fas fa-network-wired mr-1"></i> Konfigurasi API Eksternal
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <form action="{{ route('pengaturan.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="card-body">
                                    <div class="callout callout-info mb-4">
                                        <h5><i class="fas fa-info-circle text-info"></i> Penting!</h5>
                                        <p>Konfigurasi ini menghubungkan sistem dengan server data eksternal (Bokar). Pastikan URL yang dimasukkan valid dan dapat diakses.</p>
                                    </div>

                                    <div class="form-group">
                                        <label for="url_api_bokar" class="font-weight-bold">URL Endpoint API Bokar</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="fas fa-globe text-primary"></i></span>
                                            </div>
                                            <input type="url" 
                                                   class="form-control @error('url_api_bokar') is-invalid @enderror" 
                                                   id="url_api_bokar" 
                                                   name="url_api_bokar" 
                                                   value="{{ old('url_api_bokar', $apiBokar->nilai ?? '') }}" 
                                                   placeholder="https://contoh.com/api/get_bokar.php"
                                                   style="height: 45px;">
                                            
                                            @error('url_api_bokar')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted mt-2">
                                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                            Digunakan untuk sinkronisasi data: <b>Petani, PTPN, dan Inhutani</b>.
                                        </small>
                                    </div>
                                </div>

                                <div class="card-footer bg-light text-right">
                                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    {{-- Kolom Kanan: Panel Informasi --}}
                    <div class="col-md-4">
                        <div class="card card-outline card-info shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold"><i class="fas fa-question-circle mr-1"></i> Bantuan</h3>
                            </div>
                            <div class="card-body">
                                <p class="text-justify">
                                    Halaman ini digunakan untuk mengatur parameter teknis sistem yang bersifat dinamis.
                                </p>
                                <hr>
                                <strong><i class="fas fa-sync-alt mr-1 text-primary"></i> Fitur Sync API</strong>
                                <p class="text-muted mt-1 mb-3">
                                    Fitur "Sync API" pada menu Pengolahan Basah akan menggunakan URL yang Anda tentukan di sini untuk menarik data terbaru.
                                </p>
                                
                                <strong><i class="fas fa-shield-alt mr-1 text-success"></i> Keamanan</strong>
                                <p class="text-muted mt-1">
                                    Perubahan pada pengaturan ini akan dicatat dalam log sistem untuk keperluan audit.
                                </p>
                            </div>
                        </div>

                        {{-- Status Koneksi (Visualisasi Sederhana) --}}
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-server"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Status API Saat Ini</span>
                                <span class="info-box-number">
                                    {{ !empty($apiBokar->nilai) ? 'Terkonfigurasi' : 'Belum Disetting' }}
                                </span>
                            </div>
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
@include('template.script')
</body>
</html>