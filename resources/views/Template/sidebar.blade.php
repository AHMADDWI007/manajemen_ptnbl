<aside class="main-sidebar elevation-4" style="background-color: #355E3B;">
    <!-- Brand Logo -->
    <a href="#" class="brand-link d-flex align-items-center" style="background-color: #2E8B57; color: #fff;">
        <img src="{{ asset('gambar/logo.png') }}" 
             alt="Logo" 
             class="brand-image img-circle elevation-3" 
             style="opacity:.9; background-color:#fff; padding:3px;">
        <span class="brand-text fw-bolder text-white ms-2" style="font-size: 15px; letter-spacing: 0.5px;">
            PT. NUSANTARA BATULICIN
        </span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel d-flex align-items-center mt-3 pb-3 mb-3 border-bottom" 
             style="border-color: rgba(255,255,255,0.2);">
            <div class="image">
                <img src="{{ asset('gambar/user.png') }}" 
                     class="img-circle elevation-2" 
                     alt="User Image"
                     style="width:45px; height:45px; object-fit:cover; background:#fff; padding:2px;">
            </div>
            <div class="info ms-2">
                <a href="#" class="d-block text-white fw-bold" style="font-size: 16px;">Administrasi</a>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" 
                       type="search" placeholder="Cari..." aria-label="Search" 
                       style="background-color: #446644; color:#fff; border: none;">
                <div class="input-group-append">
                    <button class="btn btn-sidebar" style="background-color:#FFD700; color:#355E3B;">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- 🏠 Menu Beranda -->
                <li class="nav-item">
                    <a href="{{ url('/beranda') }}" 
                       class="nav-link {{ request()->is('beranda') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Beranda</p>
                    </a>
                </li>

                @php
                    // Cek apakah salah satu submenu Data Laboratorium aktif
                    $laboratoriumActive = request()->is('hasil_uji_lab_bokar*') 
                                        || request()->is('hasil_uji_maturasi*') 
                                        || request()->is('hasil_uji_trolli*') 
                                        || request()->is('hasil_uji_produksi*');
                @endphp

                <!-- 🧪 Data Laboratorium -->
                <li class="nav-item {{ $laboratoriumActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $laboratoriumActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-vials"></i>
                        <p>
                            Data Laboratorium
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('hasil_uji_lab_bokar') }}" 
                               class="nav-link {{ request()->is('hasil_uji_lab_bokar*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Hasil Uji Bokar</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('hasil_uji_maturasi') }}" 
                               class="nav-link {{ request()->is('hasil_uji_maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Hasil Uji Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('hasil_uji_trolli') }}" 
                               class="nav-link {{ request()->is('hasil_uji_trolli*') ? 'active' : '' }}">
                               <i class="far fa-circle nav-icon"></i>
                                <p>Hasil Uji Trolli</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('hasil_uji_produksi') }}" 
                               class="nav-link {{ request()->is('hasil_uji_produksi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Hasil Uji Produksi</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 🌿 Data Bokar -->
                <li class="nav-item">
                    <a href="{{ url('bokar') }}" 
                       class="nav-link {{ request()->is('bokar*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-leaf"></i>
                        <p>Data Bokar</p>
                    </a>
                </li>

                <!-- ⚗️ Data Maturasi -->
                <li class="nav-item">
                    <a href="{{ url('maturasi') }}" 
                       class="nav-link {{ request()->is('maturasi*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-flask"></i>
                        <p>Data Maturasi</p>
                    </a>
                </li>

                <!-- 🏭 Data Produksi -->
                 <li class="nav-item">
                    <a href="{{ url('produksi') }}" 
                       class="nav-link {{ request()->is('produksi*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-truck-loading"></i>
                        <p>Data Produksi</p>
                    </a>
                </li>
                <!-- 🏭 Laporan Akhir -->
                 <li class="nav-item">
                    <a href="{{ url('Laporan') }}" 
                       class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Laporan</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>

    <!-- Custom CSS -->
    <style>
        /* Sidebar link default */
        .nav-sidebar .nav-link {
            color: white !important;
        }

        /* Hover effect */
        .nav-sidebar .nav-link:hover {
            background-color: #3CB371 !important;
            color: #fff !important;
        }

        /* Active link effect */
        .nav-sidebar .nav-link.active {
            background-color: #2E8B57 !important;
            color: #fff !important;
        }

        /* Submenu default */
        .nav-sidebar .nav-treeview .nav-link {
            background-color: transparent !important;
            color: white !important;
        }

        /* Submenu aktif */
        .nav-sidebar .nav-treeview .nav-link.active {
            background-color: #2E8B57 !important;
            color: white !important;
        }

        /* Rotate arrow when open */
        .nav-item.menu-open > a > p > .right {
            transform: rotate(90deg);
            transition: transform 0.3s ease;
        }

        /* Smooth transition */
        .right {
            transition: transform 0.3s ease;
        }
    </style>
</aside>
