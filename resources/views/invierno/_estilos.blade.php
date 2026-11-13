@push('page_css')
<style>
    .invierno { padding: 24px 20px; color: #23354d; }
    .invierno .iw-hero { background: linear-gradient(115deg, #172a43, #264563); color: #fff; padding: 26px 30px; border-radius: 12px; margin-bottom: 22px; }
    .invierno .iw-hero h1 { font-size: 1.65rem; font-weight: 700; margin-bottom: 8px; }
    .invierno .iw-hero p { color: #d1dfec; margin: 0; max-width: 850px; }
    .invierno .iw-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: .12em; color: #b9d5eb; margin-bottom: 10px; }
    .invierno .card { border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 2px 6px #172a4308; }
    .invierno .card-header { padding: 17px 20px; background: #fff; border-radius: 10px 10px 0 0; }
    .invierno .card-title { font-weight: 700; font-size: 1rem; }
    .invierno .iw-metric { padding: 17px 20px; height: calc(100% - 16px); margin-bottom: 16px; }
    .invierno .iw-metric span { display: block; color: #5b6c80; font-size: .81rem; }
    .invierno .iw-metric strong { font-size: 1.75rem; line-height: 1.5; display: block; }
    .invierno .iw-metric small { color: #687b8f; font-size: .74rem; }
    .invierno .iw-metric.iw-primary { border-top: 3px solid #3c7aaf; }
    .invierno .iw-metric.iw-green { border-top: 3px solid #2b8a68; }
    .invierno .iw-metric.iw-orange { border-top: 3px solid #ce9a35; }
    .invierno .table { color: #23354d; margin-bottom: 0; font-size: .86rem; }
    .invierno .table th { background: #f3f6fa; color: #586b80; font-size: .71rem; text-transform: uppercase; letter-spacing: .04em; border-top: 0; padding: 13px 15px; vertical-align: middle; }
    .invierno .table td { padding: 14px 15px; vertical-align: middle; border-top: 1px solid #eaf0f5; }
    .invierno .table td.num, .invierno .table th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .invierno .iw-tabla { min-width: 0; max-width: 100%; container: invierno-tabla / inline-size; }
    .invierno .iw-tabla .table { width: 100%; font-size: .9375rem; line-height: 1.4; }
    .invierno .iw-tabla th { padding: 11px 14px; font-size: .875rem; font-weight: 700; letter-spacing: normal; text-transform: none; white-space: normal; }
    .invierno .iw-tabla td { padding: 12px 14px; overflow-wrap: anywhere; }
    .invierno .iw-tabla small { font-size: .8125rem; }
    .invierno .iw-tabla .iw-descripcion { display: block; }
    .invierno .iw-tabla .iw-grupo-articulo { display: block; margin-top: 2px; color: #586b80; }
    .invierno .iw-tabla .iw-accion { display: inline-block; margin-top: 6px; white-space: normal; text-align: left; font-weight: 600; }
    .invierno .iw-filtros { display: grid; grid-template-columns: minmax(0, 1.35fr) repeat(2, minmax(0, 1fr)); gap: 12px 14px; align-items: end; }
    .invierno .iw-filtros .form-group { min-width: 0; margin: 0; }
    .invierno .iw-filtros-acciones { display: flex; flex-wrap: wrap; gap: 6px; }
    .invierno .iw-tabla .iw-col-cantidades { width: 27%; }
    .invierno .iw-tabla .iw-col-importes { width: 21%; }
    .invierno .iw-tabla .iw-col-grupo { width: 25%; }
    .invierno .iw-tabla .iw-col-saldo { width: 17%; }
    .invierno .iw-tabla .iw-col-formulario { width: 30%; }
    .invierno .iw-tabla .iw-etapa-cantidad { margin-bottom: 3px; }
    .invierno .iw-code { font-family: monospace; font-size: .95rem; color: #294d74; font-weight: 700; }
    .invierno .iw-cantidades { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 3px 10px; margin: 0; font-variant-numeric: tabular-nums; }
    .invierno .iw-cantidades dt { color: #52647a; font-size: .875rem; font-weight: 500; }
    .invierno .iw-cantidades dd { margin: 0; text-align: right; font-weight: 700; }
    .invierno .iw-cantidades .iw-saldo { color: #176748; font-weight: 700; }
    .invierno .iw-pill { display: inline-block; border-radius: 6px; padding: 5px 8px; font-size: .73rem; font-weight: 700; white-space: normal; }
    .invierno .iw-muted { background: #edf1f6; color: #596a7e; }
    .invierno .iw-info { background: #e7f1fc; color: #2c628e; }
    .invierno .iw-success { background: #e4f4eb; color: #24714d; }
    .invierno .iw-warning { background: #fff2d6; color: #8f621b; }
    .invierno .iw-danger { background: #fbe8e6; color: #a0352c; }
    .invierno .iw-note { font-size: .83rem; color: #61758b; }
    .invierno .iw-importe { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .invierno .iw-valores-celda { margin: 0; font-size: .875rem; font-weight: 400; line-height: 1.45; }
    .invierno .iw-valores-celda > div + div { margin-top: 3px; }
    .invierno .iw-etiqueta-importe { display: block; color: #52647a; }
    .invierno .iw-valores-celda .iw-importe { font-size: .9375rem; font-weight: 700; }
    .invierno .iw-tabla .iw-valores-celda { text-align: left; }
    .invierno .iw-importe-pendiente { color: #815815; font-size: .8125rem; font-weight: 400; }
    .invierno .iw-tabla .iw-importe { white-space: normal; overflow-wrap: anywhere; }
    .invierno .iw-disponible-importe { background-color: #f1f8f4; color: #176748; font-weight: 700; }
    .invierno .iw-valor-titulo { font-size: 1rem; font-weight: 700; margin: 0 0 .5rem; }
    .invierno .iw-valor-destacado .iw-importe { font-size: 1.4rem; font-weight: 700; color: #23354d; white-space: normal; overflow-wrap: anywhere; }
    .invierno .iw-empty { text-align: center; padding: 48px 20px !important; color: #708095; }
    .invierno .iw-empty i { display: block; font-size: 1.8rem; margin-bottom: 12px; }
    .invierno .iw-empty strong { color: #324c68; display: block; margin-bottom: 6px; }
    .invierno .iw-split { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .invierno label { font-size: .82rem; }
    .invierno .btn { border-radius: 6px; }
    .invierno details > summary { cursor: pointer; font-weight: 600; }
    .invierno .pagination { margin-bottom: 0; }
    .invierno .iw-product { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
    .invierno .iw-product > div { min-width: 0; }
    .invierno .iw-photo { display: inline-flex; flex: 0 0 64px; width: 64px; height: 74px; border: 1px solid #dfe7f0; border-radius: 8px; background: #f8fafc; overflow: hidden; align-items: center; justify-content: center; }
    .invierno .iw-photo img { width: 100%; height: 100%; object-fit: contain; }
    .invierno .iw-photo:hover { border-color: #3c7aaf; }
    .invierno .iw-photo-empty { flex-direction: column; gap: 7px; color: #8394a9; }
    .invierno .iw-photo-empty i { font-size: 1.4rem; }
    .invierno .iw-photo-empty small { font-size: .68rem; }
    .invierno .iw-file-zone { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 28px 20px; border: 2px dashed #a6bfd7; background: #f5f9fd; border-radius: 10px; cursor: pointer; }
    .invierno .iw-file-zone i { font-size: 2.2rem; color: #3c7aaf; margin-bottom: 12px; }
    .invierno .iw-file-zone span { color: #61758b; font-size: .78rem; margin-top: 8px; }
    .invierno #iw-imagenes-zona:focus-within,
    .invierno #iw-imagenes-zona.iw-arrastrando { border-color: #3c7aaf; background: #e7f1fc; }
    .invierno .iw-file-example { background: #edf3f9; color: #294d74; padding: 12px 14px; border-radius: 7px; font: .8rem monospace; overflow-wrap: anywhere; margin: 10px 0; }
    .invierno .iw-import-summary { background: #edf3f9; color: #42617e; border-radius: 7px; padding: 13px 16px; font-size: .83rem; }
    .invierno .iw-import-stat { padding: 12px 16px; background: #f3f7fb; border-radius: 8px; margin-bottom: 8px; }
    .invierno .iw-import-stat strong { display: block; color: #294d74; font-size: 1.7rem; }
    .invierno .iw-import-stat span { color: #61758b; font-size: .82rem; }
    /* Mantener legibles las filas según el espacio que deja el menú lateral. */
    @container invierno-tabla (max-width: 860px) {
        .invierno .iw-tabla .table { display: block; }
        .invierno .iw-tabla .table > thead { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip-path: inset(50%); }
        .invierno .iw-tabla .table > tbody, .invierno .iw-tabla .table > tfoot { display: block; }
        .invierno .iw-tabla .table tr { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border-top: 1px solid #e2e8f0; padding: 6px 0; }
        .invierno .iw-tabla .table td { display: block; min-width: 0; border-top: 0; padding: 8px 12px; text-align: left; white-space: normal; }
        .invierno .iw-tabla .table td[data-etiqueta]::before { display: block; margin-bottom: 4px; color: #52647a; font-size: .8125rem; font-weight: 700; content: attr(data-etiqueta); }
        .invierno .iw-tabla .iw-ficha, .invierno .iw-tabla .iw-columna-completa, .invierno .iw-tabla td[colspan] { grid-column: 1 / -1; }
        .invierno .iw-tabla .iw-etapa-saldo { grid-column: 1 / -1; }
        .invierno .iw-tabla td.iw-celda-vacia { display: none; }
    }
    @container invierno-tabla (max-width: 340px) {
        .invierno .iw-tabla .table tr { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 767px) {
        .invierno { padding: 16px 8px; }
        .invierno .iw-hero { padding: 22px 18px; }
        .invierno .iw-hero h1 { font-size: 1.35rem; }
        .invierno .iw-filtros { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 480px) {
        .invierno .iw-filtros { grid-template-columns: minmax(0, 1fr); }
        .invierno .iw-hero .btn { white-space: normal; text-align: left; }
        .invierno .pagination { flex-wrap: wrap; }
    }
    @media (min-width: 1600px) {
        .invierno .iw-filtros { grid-template-columns: minmax(0, 1.35fr) repeat(4, minmax(0, 1fr)) auto; }
    }
</style>
@endpush
