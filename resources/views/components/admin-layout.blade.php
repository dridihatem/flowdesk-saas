@props(['title' => null])
@php
    $flowdeskTheme = app(\App\Services\CompanyThemeService::class)->themeFor(null, auth()->user());
    $preset = (array) (config('flowdesk.theme_presets.admin') ?? []);
    $flowdeskTheme = array_merge($flowdeskTheme, [
        'primary_color' => $preset['primary_color'] ?? ($flowdeskTheme['primary_color'] ?? '#0f766e'),
        'secondary_color' => $preset['secondary_color'] ?? ($flowdeskTheme['secondary_color'] ?? '#475569'),
        'dark_mode' => 'light',
        'html_class' => '',
        'use_system_dark' => false,
    ]);
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class=""
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' — ' : '' }}{{ __('Platform admin') }} — {{ config('app.name') }}</title>
        @include('partials.favicon')
        @include('partials.theme-fonts', ['flowdeskTheme' => $flowdeskTheme])
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-variables', ['flowdeskTheme' => $flowdeskTheme])
    </head>
    <body class="font-sans antialiased min-h-screen bg-slate-100 text-slate-900" style="font-family: var(--flow-font-sans, ui-sans-serif, system-ui, sans-serif);">
        @include('layouts.admin-sidebar', ['flowdeskTheme' => $flowdeskTheme, 'slot' => $slot])
        <x-admin-company-contact-widget />
        @include('partials.notify-labels')
        @stack('scripts')
    </body>
</html>
