<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- App CSS -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    <!-- Fuente moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;

            /* 🔥 Fondo imagen + degradado */
            background:
                linear-gradient(135deg, rgba(0, 0, 0, 0.75), rgba(9, 94, 179, 0.877), rgba(0, 0, 0, 0.75)),
                url("{{ asset('img/articulos/bg_sistema.png') }}");
                

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-box {
            width: 400px;
            animation: fadeIn 1s ease;
        }

        .login-card {
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            background: #ffffff;
        }

        .login-header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.85;
            margin-top: 5px;
        }

        .login-body {
            padding: 35px 30px;
        }

        .form-control {
            height: 50px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.5);
            border-color: #2563eb;
        }

        .btn-login {
            height: 50px;
            border-radius: 12px;
            font-weight: 600;
            background: #2563eb;
            border: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .btn-login:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }

        .input-group-text {
            background: transparent;
            border-left: 0;
            cursor: pointer;
        }

        .footer-text {
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            margin-top: 25px;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-group .fas {
            color: #2563eb;
        }

        /* Rediseño oscuro: limitado a la pantalla de autenticación. */
        html { min-height:100%; background:#060e1d; }
        body {
            min-height:100vh; min-height:100dvh; height:auto; margin:0;
            padding:24px; overflow-x:hidden; overflow-y:auto;
            background: radial-gradient(ellipse at 10% 85%,#133766 0,transparent 50%),
                radial-gradient(ellipse at 95% 15%,#153968 0,transparent 45%),
                linear-gradient(130deg,rgba(3,12,27,.95),rgba(6,17,34,.92)),
                url("{{ asset('img/articulos/bg_sistema.png') }}") center / cover no-repeat,#060e1d;
            color:#eaf2ff; font-family:'Inter',Arial,sans-serif;
        }
        .login-shell {
            display:grid; grid-template-columns:minmax(0,1fr) minmax(360px,.95fr);
            width:min(920px,100%); min-height:455px; overflow:hidden;
            border:1px solid rgba(143,185,237,.18); border-radius:23px;
            background:#0d192c; box-shadow:0 35px 90px rgba(0,3,13,.56);
        }
        .login-feature {
            display:flex; flex-direction:column; justify-content:space-between;
            padding:42px 39px 34px;
            border-right:1px solid rgba(143,185,237,.11);
            background:radial-gradient(ellipse at 5% 100%,rgba(45,106,209,.40),transparent 62%),
                linear-gradient(145deg,#142f59,#0b1c35 82%);
            position:relative; isolation:isolate;
        }
        .login-feature::before {
            content:""; position:absolute; width:240px; height:240px;
            right:-95px; top:-130px; border:1px solid rgba(142,184,244,.17);
            border-radius:50%; box-shadow:0 0 0 46px rgba(76,143,230,.045);
            z-index:-1;
        }
        .login-brand-mark {
            display:inline-flex; width:51px; height:51px; align-items:center; justify-content:center;
            border-radius:14px; background:linear-gradient(145deg,#3d91ff,#1454c4);
            border:1px solid rgba(172,204,251,.35); box-shadow:0 9px 23px #001a4680;
            font-size:21px; color:white;
        }
        .login-feature .brand-tag {
            margin:38px 0 11px; letter-spacing:1.8px;
            text-transform:uppercase; font-size:10px; color:#9dc6ff; font-weight:700;
        }
        .login-feature h2 {
            font-size:clamp(29px,3vw,42px); line-height:1.2; letter-spacing:-1px;
            font-weight:800; color:#fff; margin:0;
        }
        .login-feature p { max-width:330px; margin:16px 0 0; color:#b0c8e6; font-size:13px; line-height:1.85; }
        .login-feature .brand-bottom { color:#9ab9df; font-size:11px; }
        .login-feature .brand-bottom i { color:#8ebdff; margin-right:8px; }
        .login-box { width:100%; animation:fadeIn .55s ease both; }
        .login-card {
            background:linear-gradient(145deg,#12213a,#0b172a);
            border-radius:0; box-shadow:none; height:100%;
            display:flex; flex-direction:column; justify-content:center;
        }
        .login-header {
            background:transparent; text-align:left; padding:33px 40px 5px;
        }
        .login-header h1 { font-size:25px; font-weight:800; letter-spacing:-.6px; color:#f5f8ff; }
        .login-header p { color:#97afcf; opacity:1; font-size:12px; margin:8px 0 0; }
        .login-body { padding:21px 40px 27px; }
        .login-body label { color:#d6e3f5; font-size:12px; }
        .login-body .input-group {
            border:1px solid #2b405e; border-radius:10px;
            background:#091527; overflow:hidden;
            transition:border-color .18s,box-shadow .18s;
        }
        .login-body .input-group:focus-within {
            border-color:#589fff; box-shadow:0 0 0 3px rgba(52,131,245,.16);
        }
        .login-body .form-control {
            height:48px; background:transparent!important; border:0!important;
            color:#fff!important; box-shadow:none!important;
            padding:0 15px; font-size:13px; font-weight:500;
        }
        .login-body .form-control::placeholder { color:#6d87a9; }
        .login-body .form-control:-webkit-autofill {
            -webkit-text-fill-color:#fff!important;
            -webkit-box-shadow:0 0 0 1000px #091527 inset!important;
        }
        .login-body .input-group-text {
            height:48px; padding:0 15px; color:#93b5e3;
            background:transparent!important; border:0!important;
        }
        .login-body .input-group .fas { color:#7caeff; }
        .login-body .btn-login {
            margin-top:5px; height:48px; color:white; border-radius:10px;
            background:linear-gradient(115deg,#327dff,#1954c6);
            box-shadow:0 8px 22px rgba(24,90,219,.21);
            font-size:13px; font-weight:700;
        }
        .login-body .btn-login:hover { background:linear-gradient(115deg,#478cff,#2464df); }
        .login-body .footer-text {
            border-top:1px solid rgba(157,185,231,.13); padding-top:19px;
            margin-top:24px; color:#7d95b7; font-size:10px;
        }
        .login-body .invalid-feedback { font-size:12px; color:#f5a2af; }
        @media(max-width:760px) {
            body { padding:16px; }
            .login-shell { width:min(430px,100%); min-height:0; grid-template-columns:1fr; border-radius:18px; }
            .login-feature { padding:24px 27px 20px; border-right:0; border-bottom:1px solid rgba(143,185,237,.12); }
            .login-feature .brand-tag,.login-feature p,.login-feature .brand-bottom { display:none; }
            .login-feature h2 { font-size:23px; margin-top:12px; }
            .login-brand-mark { width:41px; height:41px; font-size:18px; border-radius:11px; }
            .login-header { padding:27px 27px 5px; }
            .login-body { padding:21px 27px 25px; }
        }
        @media(prefers-reduced-motion:reduce) {
            .login-box { animation:none; }
            .login-body .btn-login,.login-body .input-group { transition:none; }
            .login-body .btn-login:hover { transform:none; }
        }
    </style>
</head>

<body>

    <div class="login-box">
        <div class="login-card">

            <div class="login-header">
                <h1>{{ config('app.name') }}</h1>
                <p>Acceso seguro al sistema</p>
            </div>

            <div class="login-body">

                <form method="POST" action="{{ url('/login') }}">
                    @csrf

                    <div class="form-group mb-4">
                        <label class="mb-2 font-weight-bold">Usuario</label>
                        <div class="input-group">
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="form-control text-uppercase @error('name') is-invalid @enderror"
                                placeholder="USUARIO" required oninput="this.value = this.value.toUpperCase();">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                            </div>
                        </div>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label class="mb-2 font-weight-bold">Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="••••••••" required>
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>
                        @error('password')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-login btn-block">
                        <i class="fas fa-sign-in-alt mr-1"></i> Ingresar
                    </button>
                </form>

                <div class="footer-text">
                    © {{ date('Y') }} {{ config('app.name') }} · Todos los derechos reservados
                </div>

            </div>
        </div>
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
    @include('sweetalert::alert')

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>

</html>