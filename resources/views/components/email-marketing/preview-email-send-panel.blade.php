@if ($previewEmailUrl ?? null)
    <div
        class="rounded-lg border border-indigo-200/80 bg-indigo-50/50 p-3 dark:border-indigo-500/30 dark:bg-indigo-950/20"
        x-show="previewEmailUrl && !bodyDisabled"
    >
        <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ __('email_marketing_preview_send_heading') }}</p>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('email_marketing_preview_send_help') }}</p>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label class="sr-only" for="preview_email_to_{{ $bodyId }}">{{ __('email_marketing_sample_to') }}</label>
                <input
                    id="preview_email_to_{{ $bodyId }}"
                    type="email"
                    x-model="previewEmailTo"
                    class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="you@example.com"
                    :disabled="previewEmailBusy"
                />
            </div>
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                @click="sendPreviewEmail()"
                :disabled="previewEmailBusy"
            >
                <i class="fa-solid fa-paper-plane text-xs" aria-hidden="true"></i>
                <span x-show="!previewEmailBusy">{{ __('email_marketing_preview_send_button') }}</span>
                <span x-show="previewEmailBusy" x-cloak>{{ __('email_marketing_preview_send_sending') }}</span>
            </button>
        </div>
        <p x-show="previewEmailOk" x-text="previewEmailOk" x-cloak class="mt-2 text-xs font-medium text-emerald-700 dark:text-emerald-400"></p>
        <p x-show="previewEmailErr" x-text="previewEmailErr" x-cloak class="mt-2 text-xs text-rose-600 dark:text-rose-400"></p>
    </div>
@endif
