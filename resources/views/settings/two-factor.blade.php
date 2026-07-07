<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Two-factor authentication') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            @if (session('two_factor_recovery_codes_plain'))
                <div class="mb-6 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/50 dark:text-amber-100">
                    <p class="font-medium">{{ __('Save these recovery codes in a safe place. Each can be used once if you lose your device.') }}</p>
                    <ul class="mt-3 space-y-1 font-mono text-xs">
                        @foreach (session('two_factor_recovery_codes_plain') as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                @if ($enabled)
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Two-factor authentication is enabled for your account.') }}</p>

                    <form method="POST" action="{{ route('settings.two-factor.destroy') }}" class="mt-8 space-y-6">
                        @csrf
                        @method('DELETE')

                        <div>
                            <x-input-label for="password" :value="__('Current password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <x-primary-button class="bg-red-600 hover:bg-red-500 focus:ring-red-500">{{ __('Disable two-factor') }}</x-primary-button>
                    </form>
                @else
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Add a second step after your password using an authenticator app (TOTP). Available to company admins only.') }}</p>

                    @if (! $pendingEnrollment)
                        <form method="POST" action="{{ route('settings.two-factor.prepare') }}" class="mt-8">
                            @csrf
                            <x-primary-button>{{ __('Set up two-factor') }}</x-primary-button>
                        </form>
                    @else
                        @if ($qrSvg)
                            <div class="mt-8 flex justify-center rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-600 dark:bg-slate-800/50">
                                {!! $qrSvg !!}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('settings.two-factor.confirm') }}" class="mt-8 space-y-6">
                            @csrf

                            <div>
                                <x-input-label for="code" :value="__('6-digit code from your app')" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" inputmode="numeric" maxlength="6" required autocomplete="one-time-code" />
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>

                            <x-primary-button>{{ __('Confirm and enable') }}</x-primary-button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
