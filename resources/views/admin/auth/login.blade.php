<x-guest-layout hero-variant="login">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[1.65rem]">{{ __('Admin sign in') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Sign in to manage the platform.') }}</p>
    </div>

    <x-auth-session-status
        class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-200"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
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

        <x-primary-button
            class="!mt-2 w-full !justify-center !rounded-xl !py-3 !text-sm !font-semibold !normal-case !tracking-normal !shadow-lg !shadow-indigo-600/25 !transition hover:!shadow-indigo-600/35 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 dark:!shadow-indigo-900/40"
        >
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <div class="mt-10 text-center text-sm text-slate-600 dark:text-slate-400">
        <a class="font-semibold text-indigo-600 underline decoration-indigo-400/40 underline-offset-2 transition hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300" href="{{ route('login') }}">
            {{ __('Back to workspace login') }}
        </a>
    </div>
</x-guest-layout>

