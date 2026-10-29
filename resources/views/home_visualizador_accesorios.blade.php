@extends('layouts.app')

@section('content')
<main id="acc-access-page" class="acc-access-page">
    <div class="acc-access-container">
        <header class="acc-access-pagehead">
            <div>
                <div class="acc-access-breadcrumb"><i class="fas fa-home" aria-hidden="true"></i> Panel de inicio</div>
                <h1>Inicio</h1>
                <p>Acceso a tu espacio de consulta</p>
            </div>
            <span class="acc-access-role"><i class="far fa-eye" aria-hidden="true"></i> Acceso de solo lectura</span>
        </header>

        <section class="acc-access-card" aria-labelledby="acc-access-title">
            <div class="acc-access-card-top"></div>
            <div class="acc-access-decoration acc-access-decoration-left" aria-hidden="true"></div>
            <div class="acc-access-decoration acc-access-decoration-right" aria-hidden="true"></div>

            <div class="acc-access-visual" aria-hidden="true">
                <div class="acc-access-visual-orbit">
                    <div class="acc-access-shield">
                        <i class="fas fa-shield-alt"></i>
                        <span class="acc-access-shield-lock"><i class="fas fa-lock"></i></span>
                    </div>
                </div>
                <div class="acc-access-floating acc-access-floating-left">
                    <i class="fas fa-chart-bar"></i>
                    <span><b></b><b></b><b></b></span>
                </div>
                <div class="acc-access-floating acc-access-floating-right">
                    <i class="fas fa-chart-pie"></i>
                    <span><b></b><b></b><b></b></span>
                </div>
            </div>

            <div class="acc-access-body">
                <span class="acc-access-eyebrow"><i class="fas fa-info-circle" aria-hidden="true"></i> Acceso limitado</span>
                <h2 id="acc-access-title">No tienes permiso para visualizar esta pantalla</h2>
                <p class="acc-access-description">
                    Tu cuenta tiene acceso de <strong>solo lectura</strong> al Monitoreo de Accesorios.
                    Podés consultar sus indicadores, artículos y detalles, pero no modificar información.
                </p>

                <a href="{{ route('accesorios.index') }}" class="acc-access-action">
                    <span class="acc-access-action-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                    <span>Ir a Monitoreo de Accesorios</span>
                    <i class="fas fa-arrow-right acc-access-action-arrow" aria-hidden="true"></i>
                </a>

                <div class="acc-access-note">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>Podés seguir consultando toda la información disponible desde el módulo de Accesorios.</span>
                </div>
            </div>
        </section>

        <div class="acc-access-footnote">
            <i class="fas fa-lock" aria-hidden="true"></i>
            Tus permisos están administrados por el sistema.
        </div>
    </div>
</main>
@endsection

@push('page_css')
<style>
    /* Pantalla exclusiva del rol visualizador_accesorios. Sin afectar otros módulos. */
    #acc-access-page {
        --acc-ink: #142c53;
        --acc-text: #405878;
        --acc-primary: #1465ee;
        --acc-primary-dark: #074ab7;
        --acc-line: #e2eaf5;
        min-height: calc(100vh - 58px);
        min-height: calc(100dvh - 58px);
        display: flex;
        background:
            radial-gradient(ellipse 35% 38% at 8% 9%, rgba(83, 156, 255, .09), transparent 85%),
            radial-gradient(ellipse 42% 50% at 91% 88%, rgba(105, 154, 231, .10), transparent 86%),
            #f5f8fd;
        padding: 20px 26px 20px;
        color: var(--acc-ink);
        font-family: 'Roboto', Arial, sans-serif;
    }

    #acc-access-page *, #acc-access-page *::before, #acc-access-page *::after { box-sizing: border-box; }
    #acc-access-page .acc-access-container {
        max-width: 1500px; width: 100%; margin: 0 auto;
        display: flex; flex: 1; flex-direction: column;
    }
    #acc-access-page .acc-access-pagehead {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 14px; margin-bottom: 17px;
    }
    #acc-access-page .acc-access-breadcrumb {
        color: #7b90ae; font-size: 12px; font-weight: 700; margin-bottom: 9px;
    }
    #acc-access-page .acc-access-breadcrumb i { margin-right: 6px; color: #2a74d7; }
    #acc-access-page h1 {
        color: #142c53; font-weight: 800; font-size: 28px;
        margin: 0; line-height: 1.25; letter-spacing: -.5px;
    }
    #acc-access-page .acc-access-pagehead p {
        color: #788ba4; margin: 5px 0 0; font-size: 13px;
    }
    #acc-access-page .acc-access-role {
        display: inline-flex; align-items: center; gap: 8px;
        background: #e8f2ff; border: 1px solid #cde2fd; color: #275d9e;
        font-size: 12px; font-weight: 700; padding: 10px 15px; border-radius: 100px;
    }
    #acc-access-page .acc-access-card {
        position: relative; isolation: isolate; overflow: hidden;
        width: 100%; flex: 1; min-height: 370px;
        max-height: 750px; margin: 0 auto; text-align: left;
        display: grid; grid-template-columns: minmax(250px, .88fr) minmax(0, 1.12fr);
        align-items: center; column-gap: clamp(16px, 3vw, 62px);
        padding: 28px clamp(26px, 4.3vw, 76px);
        border: 1px solid #e1e9f4;
        border-radius: 22px; background: #fff;
        box-shadow: 0 22px 60px rgba(32, 72, 130, .09), 0 3px 9px rgba(32, 72, 130, .04);
    }
    #acc-access-page .acc-access-card-top {
        position: absolute; top: 0; left: 20%; right: 20%; height: 3px;
        background: linear-gradient(90deg, transparent, #3287fc, transparent);
    }
    #acc-access-page .acc-access-decoration {
        position: absolute; width: 285px; height: 285px; border-radius: 50%;
        background: linear-gradient(145deg, rgba(215, 232, 255, .67), rgba(243, 248, 255, .2));
        z-index: -1; pointer-events: none;
    }
    #acc-access-page .acc-access-decoration-left { left: -190px; bottom: -192px; }
    #acc-access-page .acc-access-decoration-right { right: -176px; top: -199px; }

    #acc-access-page .acc-access-visual {
        position: relative; display: grid; place-items: center;
        width: 100%; height: 100%; min-height: 250px; margin: 0;
        align-self: center;
    }
    #acc-access-page .acc-access-visual-orbit {
        display: grid; place-items: center; height: 159px; width: 159px;
        border: 2px dashed #d5e5fb; border-radius: 50%;
        background: radial-gradient(circle, #edf5ff 0 63%, transparent 64%);
    }
    #acc-access-page .acc-access-shield {
        display: grid; place-items: center; position: relative;
        height: 105px; width: 105px; border-radius: 27px;
        background: linear-gradient(145deg, #4099ff, #0b54c8);
        box-shadow: 0 12px 23px rgba(19, 101, 234, .21), inset 0 1px 0 rgba(255,255,255,.45);
        color: #fff; font-size: 63px;
    }
    #acc-access-page .acc-access-shield-lock {
        position: absolute; top: 47px; left: 50%; transform: translateX(-50%);
        font-size: 21px; color: #fff;
    }
    #acc-access-page .acc-access-floating {
        display: flex; gap: 12px; align-items: center; position: absolute;
        top: 50%; transform: translateY(-50%); background: #f4f8ff; color: #8fb7ed;
        border: 1px solid #e8f0fc; border-radius: 12px; padding: 15px 18px;
        box-shadow: 0 6px 16px rgba(36, 98, 182, .035);
    }
    #acc-access-page .acc-access-floating-left { left: -1%; }
    #acc-access-page .acc-access-floating-right { right: -1%; }
    #acc-access-page .acc-access-floating > i { font-size: 27px; }
    #acc-access-page .acc-access-floating span { display: flex; flex-direction: column; gap: 6px; }
    #acc-access-page .acc-access-floating b {
        display: block; background: #c9dbf4; width: 48px; height: 5px; border-radius: 5px;
    }
    #acc-access-page .acc-access-floating b:nth-child(2) { width: 35px; }
    #acc-access-page .acc-access-floating b:nth-child(3) { width: 42px; }

    #acc-access-page .acc-access-body {
        position: relative; z-index: 1; width: 100%; max-width: 700px;
        padding: 14px 0;
    }
    #acc-access-page .acc-access-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        color: #336cae; background: #edf5ff; border: 1px solid #dcecff;
        padding: 7px 12px; border-radius: 100px; font-size: 11px;
        letter-spacing: .25px; font-weight: 800;
    }
    #acc-access-page h2 {
        margin: 18px 0 13px; font-size: clamp(25px, 2.5vw, 34px);
        line-height: 1.25; letter-spacing: -.65px; font-weight: 800;
        color: #112b54; max-width: 700px;
    }
    #acc-access-page .acc-access-description {
        max-width: 620px; margin: 0 0 24px; font-size: 15px;
        color: #617797; line-height: 1.75;
    }
    #acc-access-page .acc-access-description strong { color: #2b5c98; }
    #acc-access-page .acc-access-action {
        display: inline-flex; align-items: center; justify-content: center; gap: 13px;
        min-height: 52px; background: linear-gradient(110deg, #1877fa, #085ad8);
        color: #fff; font-size: 14px; font-weight: 800;
        border: 1px solid #1b70e7; border-radius: 12px;
        padding: 12px 20px; text-decoration: none;
        box-shadow: 0 10px 20px rgba(19, 104, 240, .19);
        transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
    }
    #acc-access-page .acc-access-action:hover,
    #acc-access-page .acc-access-action:focus {
        background: linear-gradient(110deg, #106be9, #064bb5);
        color: #fff; text-decoration: none; transform: translateY(-2px);
        box-shadow: 0 14px 24px rgba(19, 104, 240, .26);
    }
    #acc-access-page .acc-access-action:focus-visible { outline: 3px solid #8fc1ff; outline-offset: 3px; }
    #acc-access-page .acc-access-action-icon {
        display: grid; place-items: center; background: rgba(255, 255, 255, .16);
        height: 29px; width: 29px; border-radius: 7px; font-size: 14px;
    }
    #acc-access-page .acc-access-action-arrow { font-size: 12px; opacity: .88; }

    #acc-access-page .acc-access-note {
        display: flex; justify-content: center; align-items: flex-start; gap: 9px;
        background: #f3f8ff; border: 1px solid #e1edfb; border-radius: 10px;
        color: #4f709c; max-width: 670px; margin: 25px 0 0;
        padding: 12px 16px; font-size: 12px; line-height: 1.6;
    }
    #acc-access-page .acc-access-note i { color: #2b7adf; margin-top: 2px; }
    #acc-access-page .acc-access-footnote {
        margin: 13px auto 0; text-align: center;
        color: #8a9bb2; font-size: 12px;
    }
    #acc-access-page .acc-access-footnote i { margin-right: 6px; }

    /* Cuando el ancho es reducido, volver a una composición centrada y apilada. */
    @media (max-width: 991px) {
        #acc-access-page .acc-access-card {
            display: flex; flex-direction: column; justify-content: center;
            max-width: 760px; min-height: 0; max-height: none;
            padding: 31px 28px; text-align: center;
        }
        #acc-access-page .acc-access-visual {
            width: min(100%, 520px); height: 158px; min-height: 158px;
            margin: 0 auto 12px;
        }
        #acc-access-page .acc-access-body { max-width: 650px; padding: 0; }
        #acc-access-page h2 { margin: 17px auto 12px; }
        #acc-access-page .acc-access-description { margin: 0 auto 23px; }
        #acc-access-page .acc-access-note { margin: 24px auto 0; text-align: center; }
        #acc-access-page .acc-access-floating-left { left: 8%; }
        #acc-access-page .acc-access-floating-right { right: 8%; }
    }
    @media (max-width: 767px) {
        #acc-access-page { padding: 20px 14px 27px; }
        #acc-access-page .acc-access-card { padding: 24px 17px; }
        #acc-access-page .acc-access-pagehead { margin-bottom: 21px; }
        #acc-access-page h1 { font-size: 25px; }
        #acc-access-page .acc-access-card { padding: 24px 17px 24px; border-radius: 17px; }
        #acc-access-page .acc-access-visual { height: 148px; min-height: 148px; }
        #acc-access-page .acc-access-visual-orbit { height: 137px; width: 137px; }
        #acc-access-page .acc-access-shield { width: 92px; height: 92px; font-size: 56px; border-radius: 24px; }
        #acc-access-page .acc-access-shield-lock { top: 41px; font-size: 19px; }
        #acc-access-page .acc-access-floating { display: none; }
        #acc-access-page h2 { margin-top: 14px; font-size: 23px; letter-spacing: -.3px; }
        #acc-access-page .acc-access-description { font-size: 13px; }
        #acc-access-page .acc-access-action { width: 100%; max-width: 365px; gap: 9px; padding: 11px 12px; font-size: 13px; }
        #acc-access-page .acc-access-note { text-align: left; }
    }
    @media (prefers-reduced-motion: reduce) {
        #acc-access-page .acc-access-action { transition: none; }
        #acc-access-page .acc-access-action:hover { transform: none; }
    }
</style>
@endpush
