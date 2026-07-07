<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Security') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Optional TOTP two-factor for your login:') }}
                    <a href="{{ route('settings.two-factor') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Two-factor authentication') }}</a>
                </p>

                <p class="mt-6 text-sm text-slate-600 dark:text-slate-400">{{ __('When non-empty, only listed IP addresses can access this workspace on its tenant domain. Leave blank to allow all. Your current IP is shown for convenience.') }}</p>
                <p class="mt-2 text-xs font-mono text-slate-500">{{ request()->ip() }}</p>

                <form method="POST" action="{{ route('settings.security.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="allowed_ips" :value="__('Allowed IP addresses (one per line)')" />
                        <textarea id="allowed_ips" name="allowed_ips" rows="8" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" placeholder="203.0.113.10">{{ old('allowed_ips', $allowedIpsText) }}</textarea>
                    </div>

                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
