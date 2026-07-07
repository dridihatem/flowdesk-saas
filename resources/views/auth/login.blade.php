<x-guest-layout hero-variant="login">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[1.65rem]">{{ __('Welcome back') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Sign in to your workspace.') }}</p>
    </div>

    <x-auth-session-status
        class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-200"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-600/60 dark:bg-slate-800/40 sm:flex-row sm:items-center sm:justify-between">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2.5">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-indigo-500"
                />
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a
                    class="text-sm font-semibold text-indigo-600 decoration-indigo-400/40 underline-offset-2 transition hover:text-indigo-500 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300"
                    href="{{ route('password.request') }}"
                >{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        @include('auth.partials.math-captcha', ['captcha' => $captcha, 'inputId' => 'login_captcha'])

        <x-primary-button
            class="!mt-2 w-full !justify-center !rounded-xl !py-3 !text-sm !font-semibold !normal-case !tracking-normal !shadow-lg !shadow-indigo-600/25 !transition hover:!shadow-indigo-600/35 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 dark:!shadow-indigo-900/40"
        >
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <div class="relative mt-10 border-t border-slate-200/90 pt-10 dark:border-slate-600/50">
        <div class="absolute inset-x-0 top-0 flex justify-center">
            <span class="-translate-y-1/2 bg-white px-3 text-xs font-semibold uppercase tracking-widest text-slate-400 dark:bg-slate-900 dark:text-slate-500">{{ __('Or continue with') }}</span>
        </div>
        <div class="mt-2 grid gap-3 sm:grid-cols-3">
            <a
                href="{{ route('oauth.redirect', ['provider' => 'github']) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:shadow dark:border-slate-600 dark:bg-slate-800/90 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-800"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                GitHub
            </a>
            <a
                href="{{ route('oauth.redirect', ['provider' => 'google']) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:shadow dark:border-slate-600 dark:bg-slate-800/90 dark:text-slate-100 dark:hover:border-slate-500"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Google
            </a>
            <a
                href="{{ route('oauth.redirect', ['provider' => 'linkedin']) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-[#0A66C2]/[0.08] px-4 py-3 text-sm font-semibold text-[#0A66C2] shadow-sm transition hover:border-[#0A66C2]/30 hover:bg-[#0A66C2]/12 dark:border-slate-600 dark:bg-slate-800/90 dark:text-[#70b5f9]"
            >
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
        </div>
        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
            {{ __('New here?') }}
            <a class="font-semibold text-indigo-600 underline decoration-indigo-400/40 underline-offset-2 transition hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300" href="{{ route('register') }}">{{ __('Create workspace') }}</a>
        </p>
    </div>
</x-guest-layout>
