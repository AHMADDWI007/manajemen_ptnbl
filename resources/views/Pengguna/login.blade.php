<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem PT NBL</title>
    @include('template.head')

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f3f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh; /* pastikan tetap di tengah */
        }

        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .login-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            text-align: center;
        }

        .logo-wrapper {
            width: 110px;
            height: 110px;
            margin: 0 auto 20px;
            border: 2px solid #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-wrapper img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 5px;
        }

        p.subtitle {
            color: #777;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 12px;
            margin-bottom: 15px;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76,175,80,0.15);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            border: none;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .btn-login:hover {
            background-color: #43a047;
        }

        .extra-links {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #555;
        }

        .extra-links a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        .alert {
            text-align: left;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-wrapper">
                <img src="{{ asset('gambar/nb_icon.png') }}" alt="Logo">
            </div>

            <h2>Selamat Datang Kembali</h2>
            <p class="subtitle">Silakan masuk untuk melanjutkan</p>

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
                <span>Tidak bisa masuk? <a href="#">Hubungi Admin</a></span>
            </div>
        </div>
    </div>

    @include('template.script')
</body>
</html>
