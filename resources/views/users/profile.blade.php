@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h1 class="text-uppercase">
                        Perfil de <span class="text-primary">{{ strtolower(auth()->user()->name) }}</span>
                    </h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('home') }}" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        @include('sweetalert::alert')
        <div class="container-fluid">
            <div class="row">
                <!-- Profile Sidebar -->
                <div class="col-md-4">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <img class="profile-user-img img-fluid img-circle mb-3"
                                src="https://png.pngtree.com/png-vector/20210706/ourlarge/pngtree-blank-whatsapp-bussiness-man-photo-profile-png-image_3562846.jpg"
                                alt="User profile picture">
                            <h4 class="profile-username">{{ auth()->user()->name }}</h4>
                            <p class="text-muted mb-1">Software Engineer</p>
                            <p class="text-muted"><i class="fas fa-envelope mr-2"></i>{{ auth()->user()->email }}</p>
                            <a href="#" class="btn btn-primary btn-block">
                                <i class="fas fa-user-edit"></i> Editar Perfil
                            </a>
                        </div>
                    </div>

                    <div class="card shadow mt-4">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title">Detalles</h3>
                        </div>
                        <div class="card-body">
                            <strong><i class="fas fa-map-marker-alt mr-1"></i> Dirección</strong>
                            <p class="text-muted">{{ auth()->user()->direccion }}</p>
                            <hr>

                            <strong><i class="fas fa-graduation-cap mr-1"></i> Educación</strong>
                            <p class="text-muted">B.S. en Ciencias de la Computación, UT Knoxville</p>
                            <hr>

                            <strong><i class="fas fa-tools mr-1"></i> Habilidades</strong>
                            <p>
                                <span class="badge badge-danger">UI Design</span>
                                <span class="badge badge-success">Coding</span>
                                <span class="badge badge-info">Javascript</span>
                                <span class="badge badge-warning">PHP</span>
                                <span class="badge badge-primary">Node.js</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header p-0">
                            <ul class="nav nav-tabs nav-fill">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#password" data-toggle="tab">
                                        <i class="fas fa-lock"></i> Cambiar Contraseña
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#activity" data-toggle="tab">
                                        <i class="fas fa-history"></i> Actividad
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Cambiar Contraseña -->
                                <div class="tab-pane active" id="password">
                                    <h5 class="mb-4">Actualizar Contraseña</h5>
                                    <form action="{{ url('users/perfil/cambiar-password') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label>Nueva Contraseña</label>
                                            <div class="input-group">
                                                <input type="password" name="password" class="form-control"
                                                    placeholder="Ingresa tu nueva contraseña" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Confirmar Contraseña</label>
                                            <div class="input-group">
                                                <input type="password" name="confirm-password" class="form-control"
                                                    placeholder="Confirma tu nueva contraseña" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        @can('usuarios index')
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-save"></i> Guardar Cambios
                                            </button>
                                        @endcan
                                    </form>
                                </div>

                                <!-- Actividad -->
                                <div class="tab-pane" id="activity">
                                    <h5 class="mb-4">Actividad Reciente</h5>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur a ex elit. Morbi
                                        at lacus vel nunc pharetra interdum. Donec eu tortor sit amet quam mollis sodales.
                                    </p>
                                    <p><i class="fas fa-check text-success"></i> Inicio de sesión exitoso.</p>
                                    <p><i class="fas fa-times text-danger"></i> Intento fallido de inicio de sesión.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
