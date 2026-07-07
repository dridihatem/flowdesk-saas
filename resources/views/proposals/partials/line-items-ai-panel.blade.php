@if ($flowdeskPlanGates['ai_credits'] ?? true)
    <section class="rounded-2xl border border-violet-200/80 bg-gradient-to-br from-violet-50/80 to-indigo-50/40 p-6 dark:border-violet-900/40 dark:from-violet-950/30 dark:to-indigo-950/20">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600/15 text-violet-700 dark:bg-violet-400/20 dark:text-violet-300">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('AI line items') }}</h3>
                    <span class="text-xs font-medium text-violet-700 dark:text-violet-300">{{ __('quote_ai_credits_cost') }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('quote_ai_panel_intro') }}</p>
                <x-ai-voice-wrap target-id="quote_ai_brief" submit-button-id="quote_ai_generate_btn" class="mt-4">
                    <textarea
                        id="quote_ai_brief"
                        x-model="aiBrief"
                        rows="3"
                        class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        placeholder="{{ __('quote_ai_brief_placeholder') }}"
                    ></textarea>
                </x-ai-voice-wrap>
                <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" x-model="aiReplace" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:border-slate-600" />
                    {{ __('Replace existing lines') }}
                </label>
                <p class="mt-2 text-xs text-rose-600 dark:text-rose-400" x-show="aiError" x-text="aiError" x-cloak></p>
                <button
                    type="button"
                    id="quote_ai_generate_btn"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-500 disabled:opacity-50 dark:bg-violet-500 dark:hover:bg-violet-400"
                    :disabled="aiLoading"
                    @click="suggestAiLines()"
                >
                    <i class="fa-solid fa-sparkles text-xs" aria-hidden="true"></i>
                    <span x-show="!aiLoading">{{ __('Generate lines with AI') }}</span>
                    <span x-show="aiLoading" x-cloak>{{ __('Generating…') }}</span>
                </button>
                @include('documents.partials.line-items-document-scan', ['scanCreditsKey' => 'quote_ai_scan_credits_cost'])
            </div>
        </div>
    </section>
@endif
