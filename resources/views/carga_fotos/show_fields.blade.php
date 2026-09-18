@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-bold text-primary"><i class="fas fa-image me-2"></i> Detalle de la Imagen</h1>
                <div>
                    <a href="{{ route('carga_fotos.index') }}"
                        class="btn btn-primary btn-lg shadow-sm rounded d-flex align-items-center">
                        <i class="fas fa-arrow-left me-2"></i>
                        <span>&nbsp;Volver a la Lista</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="row g-4">

            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Información General</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 fw-semibold text-secondary">Fecha de Carga:</dt>
                            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($foto->fot_fecha)->format('d/m/Y') }}</dd>

                            <dt class="col-sm-5 fw-semibold text-secondary">N° OT:</dt>
                            <dd class="col-sm-7">{{ $foto->fot_ot }}</dd>

                            <dt class="col-sm-5 fw-semibold text-secondary">Precio Costo:</dt>
                            <dd class="col-sm-7">{{ number_format($foto->fot_costo, 0, ',', '.') }} Gs.</dd>

                            <dt class="col-sm-5 fw-semibold text-secondary">Precio Venta:</dt>
                            <dd class="col-sm-7">{{ number_format($foto->fot_venta, 0, ',', '.') }} Gs.</dd>

                            <dt class="col-sm-5 fw-semibold text-secondary">Descripción Articulo:</dt>
                            <dd class="col-sm-7">{{ $foto->fot_desc }}</dd>

                            <dt class="col-sm-5 fw-semibold text-secondary">Línea:</dt>
                            <dd class="col-sm-7 text-muted">{{ $foto->linea->linea_desc ?? $foto->linea_cod }}</dd>

                            <dt class="col-sm-5 fw-semibold text-secondary">Usuario:</dt>
                            <dd class="col-sm-7">{{ $foto->user->name ?? $foto->user_id }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Card de Imagen -->
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-camera me-2"></i> Imagen</h5>
                    </div>
                    <div class="card-body text-center">
                        @if ($foto->fot_img)
                            <img src="{{ Storage::url('fotos/' . $foto->fot_img) }}" alt="Imagen"
                                class="img-fluid rounded shadow-sm border img-clickable"
                                style="max-height: 400px; cursor: pointer;">
                        @else
                            <div class="alert alert-secondary py-3">
                                <i class="fas fa-exclamation-circle me-2"></i>Sin Imagen
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <!-- Botones de acción adicionales -->
        <div class="mt-4 text-end">
            <a href="{{ route('carga_fotos.edit', $foto->fot_cod) }}" class="btn btn-primary shadow-sm rounded">
                <i class="fas fa-edit me-1"></i> Editar Foto
            </a>
        </div>

    </div>
    <!-- Modal Imagen -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body p-0 text-center">
                    <img src="" id="modalImage" class="img-fluid rounded" alt="Imagen">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        dt {
            font-weight: 600;
        }

        dd {
            margin-bottom: 15px;
        }

        .card-header i {
            color: #fff;
        }

        .img-fluid {
            transition: transform 0.3s ease;
            cursor: zoom-in;
        }

        .img-fluid:hover {
            transform: scale(1.05);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:hover,
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            const modalImg = document.getElementById('modalImage');
            const imgs = document.querySelectorAll('.img-clickable');

            imgs.forEach(img => {
                img.addEventListener('click', () => {
                    modalImg.src = img.src;
                    modal.show();
                });
            });
        });
    </script>
@endpush
