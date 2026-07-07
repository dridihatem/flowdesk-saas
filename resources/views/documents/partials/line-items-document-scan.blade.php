@if ($flowdeskPlanGates['ai_credits'] ?? true)
    <div class="mt-6 border-t border-violet-200/60 pt-6 dark:border-violet-900/40">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('document_scan_title') }}</h4>
            <span class="text-xs font-medium text-violet-700 dark:text-violet-300">{{ __($scanCreditsKey) }}</span>
        </div>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('document_scan_intro') }}</p>
        <div class="mt-4">
            <input
                type="file"
                x-ref="scanFileInput"
                class="block w-full text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-violet-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-violet-500 dark:text-slate-300 dark:file:bg-violet-500 dark:hover:file:bg-violet-400"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                @change="scanFile = $event.target.files?.[0] ?? null; scanError = ''"
            />
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('document_scan_formats') }}</p>
        </div>
        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
            <input type="checkbox" x-model="scanReplace" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:border-slate-600" />
            {{ __('Replace existing lines') }}
        </label>
        <p class="mt-2 text-xs text-rose-600 dark:text-rose-400" x-show="scanError" x-text="scanError" x-cloak></p>
        <button
            type="button"
            class="mt-4 inline-flex items-center gap-2 rounded-lg border border-violet-300 bg-white px-4 py-2 text-sm font-semibold text-violet-700 shadow-sm hover:bg-violet-50 disabled:opacity-50 dark:border-violet-700 dark:bg-slate-900 dark:text-violet-300 dark:hover:bg-violet-950/40"
            :disabled="scanLoading || !scanFile"
            @click="scanDocumentLines()"
        >
            <i class="fa-solid fa-file-import text-xs" aria-hidden="true"></i>
            <span x-show="!scanLoading">{{ __('document_scan_button') }}</span>
            <span x-show="scanLoading" x-cloak>{{ __('document_scan_loading') }}</span>
        </button>
    </div>
@endif
