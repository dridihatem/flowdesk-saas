<!DOCTYPE html>
@php($isRtl = app()->getLocale() === 'ar')
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title>@yield('title', config('app.name'))</title>
        @hasSection('meta_description')
            <meta name="description" content="@yield('meta_description')">
        @endif
        @include('partials.favicon')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="marketing-site bg-white font-sans antialiased text-slate-900">
        <div class="relative min-h-screen bg-slate-50 text-slate-900">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-slate-300 to-transparent" aria-hidden="true"></div>

            @include('marketing.partials.nav')

            <main class="relative z-10">
                @yield('content')
            </main>

            @include('marketing.partials.footer')
        </div>
        @stack('scripts')
    </body>
</html>
