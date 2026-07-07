<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Partnership contract') }} — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #0f172a; background: #f8fafc; }
        @media (prefers-color-scheme: dark) {
            body { color: #e2e8f0; background: #0f172a; }
            .doc { background: #1e293b; border-color: #334155; }
            .muted { color: #94a3b8; }
        }
        .wrap { max-width: 48rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        .doc { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.75rem; box-shadow: 0 1px 2px rgb(0 0 0 / 0.05); }
        h1 { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem; }
        .muted { color: #64748b; font-size: 0.875rem; }
        .contract-header { margin-top: 1rem; white-space: pre-wrap; font-size: 0.9375rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; }
        @media (prefers-color-scheme: dark) {
            .contract-header { border-color: #334155; background: #0f172a; }
        }
        .terms { margin-top: 1.25rem; white-space: pre-wrap; font-size: 0.9375rem; }
        .flow-partnership-terms-html { margin-top: 1rem; font-size: 0.9375rem; }
        .flow-partnership-terms-html p { margin: 0.5rem 0; }
        .flow-partnership-terms-html ul, .flow-partnership-terms-html ol { margin: 0.5rem 0; padding-left: 1.25rem; }
        .flow-partnership-terms-html ul { list-style: disc; }
        .flow-partnership-terms-html ol { list-style: decimal; }
        .flow-partnership-terms-html a { color: #4f46e5; }
        .sig-block { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
        @media (prefers-color-scheme: dark) {
            .sig-block { border-color: #334155; }
        }
        .sig-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.5rem; }
        canvas { display: block; border: 1px dashed #94a3b8; border-radius: 0.5rem; cursor: crosshair; touch-action: none; background: #fff; max-width: 100%; height: auto; }
        @media (prefers-color-scheme: dark) {
            canvas { background: #0f172a; border-color: #475569; }
        }
        .btn-row { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; align-items: center; }
        button, .btn-link { font: inherit; cursor: pointer; border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; }
        button[type="submit"] { background: #4f46e5; color: #fff; border: none; }
        button[type="submit"]:disabled { opacity: 0.5; cursor: not-allowed; }
        button[type="button"].secondary { background: transparent; border: 1px solid #cbd5e1; color: #334155; }
        @media (prefers-color-scheme: dark) {
            button[type="button"].secondary { border-color: #475569; color: #e2e8f0; }
        }
        .btn-link { background: transparent; border: none; color: #4f46e5; text-decoration: underline; padding: 0.25rem 0; }
        label.chk { display: flex; gap: 0.5rem; align-items: flex-start; font-size: 0.875rem; margin-top: 1rem; }
        .err { color: #b91c1c; font-size: 0.8125rem; margin-top: 0.35rem; }
        .sig-img { max-width: 100%; height: auto; margin-top: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; }
        @media print {
            body { background: #fff; }
            .doc { box-shadow: none; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
