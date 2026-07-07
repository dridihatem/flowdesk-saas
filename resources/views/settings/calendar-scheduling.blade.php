<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Calendar & scheduling') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Calendly') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('calendly_settings_intro') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.calendar-scheduling.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="booking_url" :value="__('Calendly booking URL')" />
                        <x-text-input
                            id="booking_url"
                            name="booking_url"
                            type="url"
                            class="mt-1 block w-full"
                            :value="old('booking_url', $form['booking_url'])"
                            placeholder="https://calendly.com/your-workspace/30min"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('booking_url')" />
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('calendly_url_hint') }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input
                            type="checkbox"
                            name="embed_enabled"
                            value="1"
                            class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900"
                            @checked(old('embed_enabled', $form['embed_enabled']))
                        />
                        {{ __('Show Calendly embed on calendar pages') }}
                    </label>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <i class="fa-brands fa-google" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('settings_connectivity_title') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('settings_connectivity_hub_summary') }}</p>

                        <div class="mt-4 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 dark:border-slate-600/50 dark:bg-slate-800/30">
                            @if ($googleConfigured)
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('google_calendar_status_connected', ['email' => $googleConnectedEmail ?? '—']) }}</p>
                                @if ($googleConnectedAt)
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('Connected: :date', ['date' => $googleConnectedAt->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-slate-700 dark:text-slate-300">{{ __('google_calendar_status_disconnected') }}</p>
                            @endif
                        </div>

                        @if (($flowdeskPlanGates['projects'] ?? true))
                            <a
                                href="{{ route('settings.google-calendar') }}"
                                class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                {{ __('settings_manage_connectivity') }} →
                            </a>
                        @else
                            <p class="mt-4 text-sm text-amber-800 dark:text-amber-200/90">{{ __('calendar_google_plan_required') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
