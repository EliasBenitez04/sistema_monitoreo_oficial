@push('page_css')
<style>
.trk-shell{
    --trk:#2563eb;
    --trk-soft:#eff6ff;
    --trk-dark:#1d4ed8;
    --trk-border:#dbeafe;
}
.trk-shell.theme-it{
    --trk:#6366f1;
    --trk-soft:#eef2ff;
    --trk-dark:#4338ca;
    --trk-border:#c7d2fe;
}
.trk-eyebrow{font-size:10px;font-weight:900;letter-spacing:.12em;color:#94a3b8;margin-bottom:3px}
.trk-title{font-size:29px;font-weight:850;color:#0f172a}
.trk-subtitle{font-size:12px;color:#64748b}
.trk-hero{
    background:#fff;border:1px solid #e6ebf1;border-radius:16px;
    padding:24px;box-shadow:0 8px 24px rgba(15,23,42,.05)
}
.trk-pill{
    display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;
    font-size:10px;font-weight:900;letter-spacing:.05em;background:var(--trk-soft);
    color:var(--trk-dark);border:1px solid var(--trk-border)
}
.trk-hero-value{font-size:42px;line-height:1;font-weight:900;color:#0f172a}
.trk-hero-label{font-size:12px;color:#64748b;margin-top:5px}
.trk-hero-detail{font-size:19px;color:#334155;font-weight:850}
.trk-progress{height:9px;background:#eef2f7;border-radius:999px;overflow:hidden}
.trk-progress>div{height:100%;background:var(--trk);border-radius:999px;transition:width .25s ease}
.trk-side{
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;
    padding:20px;text-align:center
}
.trk-side span{display:block;font-size:10px;font-weight:900;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em}
.trk-side strong{display:block;font-size:33px;line-height:1;color:#0f172a;margin:9px 0 5px}
.trk-side small{color:#64748b}
.trk-kpi{
    min-height:118px;background:#fff;border:1px solid #e6ebf1;border-radius:14px;
    padding:17px;display:flex;align-items:center;gap:14px;
    box-shadow:0 5px 18px rgba(15,23,42,.04);position:relative;overflow:hidden;
    transition:transform .18s ease, box-shadow .18s ease
}
.trk-kpi:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,23,42,.08)}
.trk-kpi:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--trk)}
.trk-kpi.is-success:before{background:#22c55e}
.trk-kpi.is-warning:before{background:#f59e0b}
.trk-kpi.is-volume:before{background:#8b5cf6}
.trk-kpi-icon{
    width:43px;height:43px;border-radius:11px;background:var(--trk-soft);
    display:flex;align-items:center;justify-content:center;color:var(--trk-dark);
    font-size:17px;flex:0 0 43px
}
.trk-kpi strong{display:block;font-size:25px;line-height:1;color:#0f172a}
.trk-kpi span{display:block;font-size:12px;font-weight:800;color:#334155;margin-top:5px}
.trk-kpi small{display:block;font-size:10px;color:#94a3b8;margin-top:2px}
.trk-card{border:1px solid #e6ebf1;border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(15,23,42,.04)}
.trk-card .card-header{background:#fff!important;border-bottom:1px solid #eef2f6!important}
.trk-table th{
    background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;
    letter-spacing:.04em;white-space:nowrap;vertical-align:middle!important
}
.trk-table td{font-size:12px;vertical-align:middle!important}
.trk-order{font-size:15px;font-weight:900;color:var(--trk-dark)}
.trk-state{
    display:inline-flex;align-items:center;border-radius:999px;padding:5px 8px;
    font-size:9px;font-weight:900;white-space:nowrap
}
.trk-state.is-complete{background:#ecfdf5;color:#047857}
.trk-state.is-progress{background:#fff7ed;color:#c2410c}
.trk-state.is-pending{background:#f1f5f9;color:#64748b}
.trk-detail-hero,.trk-time-card{
    background:#fff;border:1px solid #e6ebf1;border-radius:16px;
    padding:22px;box-shadow:0 7px 22px rgba(15,23,42,.05)
}
.trk-detail-value{font-size:42px;line-height:1;font-weight:900;color:#0f172a}
.trk-time{
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;
    padding:20px;text-align:center
}
.trk-time span{display:block;font-size:10px;text-transform:uppercase;font-weight:900;letter-spacing:.06em;color:#94a3b8}
.trk-time strong{display:block;font-size:34px;line-height:1;margin:8px 0 4px}
.trk-time small{color:#64748b}
.trk-mini{
    height:100%;min-height:110px;background:#fff;border:1px solid #e6ebf1;
    border-radius:14px;padding:17px;box-shadow:0 5px 18px rgba(15,23,42,.04);
    position:relative;overflow:hidden
}
.trk-mini:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--trk)}
.trk-mini.is-success:before{background:#22c55e}
.trk-mini.is-warning:before{background:#f59e0b}
.trk-mini strong{display:block;font-size:26px;color:#0f172a}
.trk-mini span{display:block;font-size:12px;font-weight:800;color:#334155}
.trk-mini small{color:#94a3b8;font-size:10px}
.trk-step{padding:12px}
.trk-step:not(:last-child){border-right:1px solid #eef2f6}
.trk-step span{display:block;font-size:10px;text-transform:uppercase;font-weight:900;color:#94a3b8;letter-spacing:.05em}
.trk-step strong{display:block;font-size:16px;color:#0f172a;margin-top:4px}
.trk-ot{font-size:14px;font-weight:900;color:var(--trk-dark)}
.trk-progress-xs{height:4px;margin:4px auto 0;max-width:100px;background:#e9ecef;border-radius:999px;overflow:hidden}
.trk-progress-xs div{height:100%;background:var(--trk);border-radius:999px}
@media(max-width:767.98px){
    .trk-title{font-size:23px}.trk-hero-value,.trk-detail-value{font-size:34px}
    .trk-step:not(:last-child){border-right:0;border-bottom:1px solid #eef2f6}
}
</style>
@endpush
