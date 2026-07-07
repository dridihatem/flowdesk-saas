<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="flow-theme-app {{ $flowdeskTheme['html_class'] ?? '' }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.favicon')
        @include('partials.theme-fonts', ['flowdeskTheme' => $flowdeskTheme])

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @include('partials.theme-variables', ['flowdeskTheme' => $flowdeskTheme])
        @stack('styles')
    </head>
    <body class="font-sans antialiased flow-app-body">
        @include($flowdeskLayoutView ?? 'themes.default.layouts.sidebar', ['flowdeskTheme' => $flowdeskTheme])
        @include('partials.notify-labels')
        @stack('scripts')
    </body>
</html>
