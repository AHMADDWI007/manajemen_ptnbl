<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <title>Dashboard | Manajemen PT. NBL</title>

    {{-- CSS Plugin AdminLTE --}}
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    
    {{-- CUSTOM CSS: TEMA HIJAU MODERN --}}
    <style>
        /* 1. Card Sambutan (Hero Section) - SINKRON DENGAN SIDEBAR */
        .card-welcome {
            background: linear-gradient(135deg, #0B6623 0%, #3BB143 100%); /* Gradasi Hijau Tua ke Hijau Terang */
            color: #fff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(11, 102, 35, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .card-welcome::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .card-welcome::after {
            content: '';
            position: absolute;
            bottom: -30px;
            right: 80px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        /* 2. Statistik Card Modern */
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            background: #fff;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .stat-icon-bg {
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(15deg);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
        }
        .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* WARNA BORDER KIRI (SINKRONISASI TEMA) */
        .border-left-primary { border-left: 5px solid #0B6623; }
        .text-primary-custom { color: #0B6623 !important; }

       /* 🔥 INI YANG SEBELUMNYA HILANG (Untuk Kotak Ke-2) 🔥 */
        .border-left-info { border-left: 5px solid #17a2b8; } /* Biru Teal */
        .text-info-custom { color: #17a2b8 !important; }

        .border-left-success { border-left: 5px solid #3BB143; } /* Hijau Terang */
        .text-success-custom { color: #3BB143 !important; }

        .border-left-warning { border-left: 5px solid #FFD700; }
        .text-warning-custom { color: #d4b106 !important; }


        /* 3. Card Grafik & Tabel */
        .card-modern {
            border-radius: 15px;
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .card-header-modern {
            background-color: transparent;
            border-bottom: 1px solid #f0f0f0;
            padding: 1.5rem;
        }
        .card-title-modern {
            font-weight: 700;
            color: #343a40;
            font-size: 1.1rem;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed" style="background-color: #f4f6f9;">
<div class="wrapper">

  @include('template.navbar')
  @include('template.sidebar')

  <div class="content-wrapper" style="background-color: #f4f6f9;">
    
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#" style="color: #0B6623;">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">

        {{-- 1. CARD SAMBUTAN (HIJAU TEMA) --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-welcome">
                    <div class="card-body p-4 welcome-content">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                               <h1 class="font-weight-bold mb-2">
                                    {{-- 🔥 MEMANGGIL NAMA USER YANG SEDANG LOGIN 🔥 --}}
                                    {{-- Menggunakan optional() untuk mencegah error jika user null --}}
                                    Halo, {{ Auth::check() ? optional(Auth::user())->fullname : 'Guest' }}! 👋
                                </h1>
                                <p class="mb-0" style="font-size: 1.1rem; opacity: 0.9;">
                                    Selamat datang di Sistem Manajemen Produksi PT. NBL.
                                    <br>
                                    Hari ini: <span class="font-weight-bold" id="currentDate"></span>
                                </p>
                            </div>
                            <div class="col-md-4 text-right d-none d-md-block">
                                <i class="fas fa-industry fa-5x" style="opacity: 0.4;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       {{-- 2. KARTU STATISTIK (ALUR PRODUKSI) --}}
        <div class="row mb-4">
            
            {{-- 1. Bokar Masuk --}}
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card border-left-primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-primary-custom mb-1">Bokar Masuk (Bln Ini)</div>
                                <div class="stat-value">{{ number_format($statBokar, 2, ',', '.') }} <small class="text-muted" style="font-size: 1rem">Ton</small></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-truck-loading fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <i class="fas fa-truck-loading stat-icon-bg"></i>
                    </div>
                </div>
            </div>

            {{-- 2. Bokar Diolah --}}
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card border-left-info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-info-custom mb-1">Bokar Diolah (Bln Ini)</div>
                                <div class="stat-value">{{ number_format($statBokarDiolah, 2, ',', '.') }} <small class="text-muted" style="font-size: 1rem">Ton</small></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-cogs fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <i class="fas fa-cogs stat-icon-bg"></i>
                    </div>
                </div>
            </div>

            {{-- 3. Stok Maturasi --}}
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card border-left-success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-success-custom mb-1">Stok Maturasi (Saat Ini)</div>
                                <div class="stat-value">{{ number_format($statMaturasi, 2, ',', '.') }} <small class="text-muted" style="font-size: 1rem">Ton</small></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-flask fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <i class="fas fa-flask stat-icon-bg"></i>
                    </div>
                </div>
            </div>

            {{-- 4. Gudang SIR 20 --}}
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card border-left-warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-warning-custom mb-1">Gudang SIR 20 (Siap Jual)</div>
                                <div class="stat-value">{{ number_format($statGudang, 2, ',', '.') }} <small class="text-muted" style="font-size: 1rem">Ton</small></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box-open fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <i class="fas fa-box-open stat-icon-bg"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. AREA GRAFIK & LIST --}}
        <div class="row">
            {{-- Grafik Produksi -> Grafik Penjualan Bulanan --}}
            <div class="col-lg-8">
                <div class="card card-modern mb-4">
                    <div class="card-header card-header-modern d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 card-title-modern text-primary-custom">
                            <i class="fas fa-chart-bar mr-2"></i> Grafik Penjualan SIR 20 (Tahun {{ $currentYear }})
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="salesChart" style="height: 320px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kalender / Aktivitas --}}
            <div class="col-lg-4">
                <div class="card card-modern mb-4">
                    <div class="card-header card-header-modern">
                        <h6 class="m-0 card-title-modern text-success-custom">
                            <i class="far fa-calendar-check mr-2"></i> Kalender
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="calendar" style="width: 100%"></div>
                    </div>
                </div>

                {{-- Quick Info --}}
                <div class="card card-modern text-white" style="background: linear-gradient(135deg, #0B6623 0%, #3BB143 100%);">
                    <div class="card-body">
                        <h5><i class="fas fa-info-circle"></i> Info Penting</h5>
                        <p class="mb-0">Jadwal maintenance mesin Dryer akan dilakukan pada tanggal 25 bulan ini.</p>
                    </div>
                </div>
            </div>
        </div>

      </div>
    </section>
  </div>

  <footer class="main-footer">
    @include('template.footer')
  </footer>
</div>

{{-- SCRIPT JAVASCRIPT --}}
@include('template.script')

<script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

<script>
    // 1. Tanggal Hari Ini
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('id-ID', options);

    // 2. Kalender
    $('#calendar').datetimepicker({
        format: 'L',
        inline: true
    });

    // 3. Grafik Penjualan (Bulanan) - MENGGUNAKAN LINE CHART
    $(function () {
        var ctx = document.getElementById('salesChart').getContext('2d');
        
        // Ambil data array dari PHP ke Javascript
        var chartLabels = {!! json_encode($chartLabels) !!};
        var dataPenjualan = {!! json_encode($chartPenjualan) !!};

        var salesChart = new Chart(ctx, {
            type: 'line', // 🔥 DIUBAH MENJADI 'line' 🔥
            data: {
                labels: chartLabels, 
                datasets: [{
                    label: 'Total Penjualan (Kg)',
                    data: dataPenjualan, 
                    backgroundColor: 'rgba(11, 102, 35, 0.1)', // Warna hijau transparan (isi bawah garis)
                    borderColor: '#0B6623', // Warna garis hijau tua
                    pointBackgroundColor: '#0B6623', // Warna titik
                    pointBorderColor: '#fff', // Warna pinggiran titik
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#0B6623',
                    borderWidth: 2,
                    fill: true, // Mengaktifkan arsiran warna di bawah garis
                    tension: 0.4 // Membuat garisnya melengkung halus (smooth curve)
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var value = data.datasets[0].data[tooltipItem.index];
                            return 'Total: ' + value.toLocaleString('id-ID') + ' Kg';
                        }
                    }
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        gridLines: { display: false, drawBorder: false }
                    }],
                    yAxes: [{
                        ticks: { 
                            beginAtZero: true,
                            callback: function(value) {
                                return value.toLocaleString('id-ID'); // Format ribuan di sumbu Y
                            }
                        },
                        gridLines: { color: "rgba(0, 0, 0, .05)" }
                    }]
                }
            }
        });
    });
</script>

</body>
</html>