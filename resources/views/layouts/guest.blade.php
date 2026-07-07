@php
    $flowdeskTheme = app(\App\Services\CompanyThemeService::class)->themeFor(null);
@endphp
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
    </head>
    <body class="font-sans antialiased text-slate-900 dark:text-slate-100">
        <div class="min-h-screen lg:flex">
            <div @class([
                'relative hidden lg:flex lg:w-[42%] xl:w-[46%] flex-col justify-between overflow-hidden px-10 py-12 xl:px-14 text-white',
                'flow-auth-hero' => ! in_array(($heroVariant ?? 'default'), ['register', 'login'], true),
                'flow-auth-hero-register' => ($heroVariant ?? 'default') === 'register',
                'flow-auth-hero-login' => ($heroVariant ?? 'default') === 'login',
                'flow-auth-hero-forgot' => ($heroVariant ?? 'default') === 'forgot',
            ])>
                <div class="pointer-events-none absolute inset-0 flow-grid-pattern opacity-40" aria-hidden="true"></div>
                @if (($heroVariant ?? 'default') === 'register')
                    <div
                        class="pointer-events-none absolute inset-0 bg-cover bg-center opacity-90"
                        style="background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80');"
                        aria-hidden="true"
                    ></div>
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-950/88 via-indigo-900/75 to-cyan-900/82" aria-hidden="true"></div>
                @elseif (($heroVariant ?? 'default') === 'login')
                    <div
                        class="pointer-events-none absolute inset-0 bg-cover bg-center opacity-90"
                        style="background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1600&q=80');"
                        aria-hidden="true"
                    ></div>
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-slate-950/90 via-indigo-950/82 to-indigo-900/78" aria-hidden="true"></div>
                @elseif (($heroVariant ?? 'default') === 'forgot')
                    <div
                        class="pointer-events-none absolute inset-0 bg-cover bg-center opacity-90"
                        style="background-image: url('https://images.unsplash.com/photo-1633265486064-086b219458ec?auto=format&fit=crop&w=1600&q=80');"
                        aria-hidden="true"
                    ></div>
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-slate-950/88 via-violet-950/78 to-indigo-900/80" aria-hidden="true"></div>
                @endif
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/4 h-56 w-56 rounded-full bg-cyan-400/25 blur-3xl" aria-hidden="true"></div>

                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl p-1 ring-white/15 transition hover:bg-white/5 hover:ring-white/25 focus:outline-none focus:ring-2 focus:ring-white/40">
                        <x-application-logo inverse :tagline="false" />
                    </a>
                </div>

                <div class="relative z-10 max-w-md space-y-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/75">{{ config('flowdesk.brand_name', 'Flowqil') }}</p>
                    <h1 class="text-3xl font-bold leading-[1.15] tracking-tight xl:text-[2.15rem]">
                        {{ __('brand_tagline') }}
                    </h1>
                    <p class="text-base leading-relaxed text-white/85">
                        {{ __('brand_pitch') }}
                    </p>
                </div>

                <p class="relative z-10 text-xs text-white/50">
                    © {{ date('Y') }} {{ config('app.name') }}
                </p>
            </div>

            <div class="relative flex flex-1 flex-col justify-center overflow-hidden px-4 py-12 sm:px-8 lg:px-14 lg:py-16">
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950/20" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-32 top-1/4 h-96 w-96 rounded-full bg-indigo-400/12 blur-3xl dark:bg-indigo-500/10" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -left-20 bottom-0 h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl dark:bg-cyan-500/5" aria-hidden="true"></div>

                <div class="relative z-10 mx-auto w-full max-w-md lg:max-w-lg">
                    <div class="mb-8 flex justify-center lg:hidden">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl p-1 ring-slate-900/5 transition hover:bg-slate-100/80 dark:ring-white/10 dark:hover:bg-slate-800/50">
                            <x-application-logo :tagline="false" />
                        </a>
                    </div>

                    <div class="flow-auth-shell">
                        <div class="flow-auth-shell-accent" aria-hidden="true"></div>
                        <div class="flow-auth-shell-body">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
