<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('settings_connectivity_title') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('settings_connectivity_intro') }}</p>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('google'))
                <div class="mb-6 rounded-xl border border-red-200/80 bg-red-50/90 px-4 py-3 text-sm text-red-900 dark:border-red-900/40 dark:bg-red-950/50 dark:text-red-100">
                    {{ $errors->first('google') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('settings_google_calendar_section') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('google_calendar_intro') }}</p>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ __('google_calendar_how_sync_works') }}</p>

                @if (! $canManage)
                    <p class="mt-4 text-sm text-amber-800 dark:text-amber-200/90">{{ __('Only company admins can connect or disconnect Google Calendar.') }}</p>
                @endif

                <div class="mt-8 space-y-3 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                    @if ($isConfigured)
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('google_calendar_status_connected', ['email' => $connectedEmail ?? '—']) }}</p>
                        @if ($connectedAt)
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('Connected: :date', ['date' => $connectedAt->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                            </p>
                        @endif
                    @else
                        <p class="text-sm text-slate-700 dark:text-slate-300">{{ __('google_calendar_status_disconnected') }}</p>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($canManage)
                        <a
                            href="{{ route('settings.google-calendar.redirect') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                        >
                            <i class="fa-brands fa-google" aria-hidden="true"></i>
                            {{ $isConfigured ? __('Reconnect Google Calendar') : __('Connect Google Calendar') }}
                        </a>
                        @if ($isConfigured)
                            <form method="POST" action="{{ route('settings.google-calendar.disconnect') }}">
                                @csrf
                                <x-danger-button type="submit">{{ __('Disconnect') }}</x-danger-button>
                            </form>
                        @endif
                    @endif
                </div>

                <p class="mt-8 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ __('google_calendar_oauth_env_hint') }}
                </p>
            </div>

            <div class="mt-8 rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('zoom_settings_title') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('zoom_settings_intro') }}</p>

                @if ($zoomConfigured ?? false)
                    <p class="mt-4 text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('zoom_settings_configured') }}</p>
                @endif

                @if ($canManage)
                    <form method="POST" action="{{ route('settings.google-calendar.zoom') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="zoom_account_id" :value="__('zoom_account_id')" />
                            <x-text-input id="zoom_account_id" name="zoom_account_id" type="text" class="mt-1 block w-full" :value="old('zoom_account_id', $zoomAccountId)" />
                        </div>
                        <div>
                            <x-input-label for="zoom_client_id" :value="__('zoom_client_id')" />
                            <x-text-input id="zoom_client_id" name="zoom_client_id" type="password" class="mt-1 block w-full" autocomplete="off" placeholder="{{ ($hasZoomClientId ?? false) ? __('zoom_key_stored_placeholder') : '' }}" />
                        </div>
                        <div>
                            <x-input-label for="zoom_client_secret" :value="__('zoom_client_secret')" />
                            <x-text-input id="zoom_client_secret" name="zoom_client_secret" type="password" class="mt-1 block w-full" autocomplete="off" placeholder="{{ ($hasZoomClientSecret ?? false) ? __('zoom_key_stored_placeholder') : '' }}" />
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                            @if ($zoomConfigured ?? false)
                                <button type="submit" name="clear_zoom" value="1" class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-500/40 dark:bg-rose-950/40 dark:text-rose-300">
                                    {{ __('zoom_settings_clear') }}
                                </button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
