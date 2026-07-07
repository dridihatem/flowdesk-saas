{{-- Tenant / platform theme: load after @vite. Uses !important so tokens beat compiled app.css + Tailwind. --}}
@php
    $primary = $flowdeskTheme['primary_color'] ?? '#4f46e5';
    $primaryHover = $flowdeskTheme['primary_hover'] ?? '#4338ca';
    $secondary = $flowdeskTheme['secondary_color'] ?? '#64748b';
    $fontStack = $flowdeskTheme['font_stack'] ?? "'Figtree', ui-sans-serif, system-ui, sans-serif";
@endphp
<style id="flowdesk-theme-tokens">
    :root {
        --flow-primary: {{ $primary }} !important;
        --flow-primary-hover: {{ $primaryHover }} !important;
        --flow-secondary: {{ $secondary }} !important;
        {{-- Raw: inside <style>, Blade {{ }} escapes quotes to &#039; which CSS does not treat as quotes --}}
        --flow-font-sans: {!! $fontStack !!} !important;
        --flow-font-display: var(--flow-font-sans) !important;
        --flow-surface: #ffffff !important;
        --flow-surface-muted: #f1f5f9 !important;
        --flow-border: #e2e8f0 !important;
        --flow-text: var(--flow-secondary) !important;
        --flow-font-family:{{ $fontStack }}  !important;
        --flow-text-muted: color-mix(in srgb, var(--flow-secondary) 68%, #64748b) !important;
    }

    html.dark {
        --flow-surface: #0f172a !important;
        --flow-surface-muted: #1e293b !important;
        --flow-border: #334155 !important;
        --flow-text: #f1f5f9 !important;
        --flow-text-muted: #cbd5e1 !important;
        --flow-font-family: var(--flow-font-family) !important;
    }

    /* Scope: whole authenticated app (html + body) follows saved font / colors / light-dark */
    html.flow-theme-app {
        font-family: var(--flow-font-sans), ui-sans-serif, system-ui, sans-serif !important;
    }

    body.flow-app-body {
        background-color: #eef2f6 !important;
        color: var(--flow-text) !important;
        font-family: var(--flow-font-family) !important;
        min-height: 100vh;
    }

    html.dark body.flow-app-body {
        background-color: #0f172a !important;
        color: var(--flow-text) !important;
    }

    @if (filled($flowdeskTheme['custom_css'] ?? null))
        {!! $flowdeskTheme['custom_css'] !!}
    @endif
</style>
