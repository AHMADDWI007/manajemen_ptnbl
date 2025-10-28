<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | PT Nusantara Batulicin</title>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: url('{{ asset('gambar/PT.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* ANIMASI MASUK HALUS UNTUK FRAME */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ANIMASI GRADIENT BERGERAK */
        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .login-container {
            display: flex;
            width: 850px;
            height: 440px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            background-color: #fff;
            animation: fadeInUp 1s ease forwards;
        }

        /* Bagian kiri (welcome) */
        .login-left {
            flex: 1;
            background: linear-gradient(-45deg, #00d4a1, #6fe3bb, #00b488, #9df7d7);
            background-size: 300% 300%;
            animation: gradientMove 6s ease infinite;
            color: white;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-left img {
            width: 85px;
            margin-bottom: 20px;
            animation: floatLogo 3s ease-in-out infinite;
        }

        /* Logo mengambang */
        @keyframes floatLogo {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        .login-left h1 {
            font-size: 25px;
            font-weight: 700;
            line-height: 1.5;
            margin-bottom: 15px;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .login-left p {
            font-size: 14px;
            color: #e8fff5;
            line-height: 1.6;
        }

        /* Bagian kanan (form login) */
        .login-right {
            flex: 1;
            background-color: #fff;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            animation: fadeInUp 1.2s ease forwards;
        }

        .login-right h2 {
            color: #028161;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 22px;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid #b2f2d4;
            margin-bottom: 15px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #00b488;
            box-shadow: 0 0 10px rgba(0, 180, 136, 0.3);
            transform: scale(1.02);
        }

        .btn-login {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 20px;
            background: linear-gradient(to right, #00b488, #34e2b5);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 180, 136, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(to right, #02a97d, #2cd4a3);
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(0, 180, 136, 0.4);
        }

        .extra-links {
            margin-top: 15px;
            text-align: center;
            font-size: 13px;
        }

        .extra-links a {
            color: #00b488;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .extra-links a:hover {
            text-decoration: underline;
            color:  #008b6f;
        }

        .alert {
            text-align: left;
            font-size: 0.85rem;
            margin-bottom: 15px;   
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Bagian kiri -->
        <div class="login-left">
            <img src="{{ asset('gambar/nb_icon.png') }}" alt="Logo">
            <h1>SELAMAT DATANG<br>DI SISTEM INFORMASI PRODUKSI<br>PT NUSANTARA BATULICIN</h1>
            <p>Silakan login untuk mengakses Sistem.</p>
        </div>

        <!-- Bagian kanan -->
        <div class="login-right">
            <h2>Login Akun</h2>

            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <input type="text" name="username" class="form-control" placeholder="Username" required autofocus value="{{ old('username') }}">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="extra-links">
                <a href="#">Lupa Password?</a><br>
                Tidak bisa masuk? <a href="#">Hubungi Admin</a>
            </div>
        </div>
    </div>

    @include('template.script')
</body>
</html>
