<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('SMTP (outbound email)') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Used when sending invoices to clients. Leave blank to use the application default mailer.') }}</p>

                <form method="POST" action="{{ route('settings.smtp.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="host" :value="__('SMTP host')" />
                            <x-text-input id="host" name="host" type="text" class="mt-1 block w-full" :value="old('host', $smtp['host'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="port" :value="__('Port')" />
                            <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" :value="old('port', $smtp['port'] ?? '587')" />
                        </div>
                        <div>
                            <x-input-label for="encryption" :value="__('Encryption')" />
                            <select id="encryption" name="encryption" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="tls" @selected(old('encryption', $smtp['encryption'] ?? 'tls') === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('encryption', $smtp['encryption'] ?? '') === 'ssl')>SSL</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="username" :value="__('Username')" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $smtp['username'] ?? '')" autocomplete="username" />
                        </div>
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" value="" autocomplete="current-password" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('Leave empty to keep the current password.') }}</p>
                        </div>
                        <div>
                            <x-input-label for="from_address" :value="__('From address')" />
                            <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $smtp['from_address'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="from_name" :value="__('From name')" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $smtp['from_name'] ?? '')" />
                        </div>
                    </div>

                    <x-primary-button>{{ __('Save SMTP') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
