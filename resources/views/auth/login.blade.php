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