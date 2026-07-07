@php
    $t = $template ?? null;
    $aiAvailable = $aiAvailable ?? false;
    $aiTemplateCreditCost = (int) ($flowdeskAiTaskCredits['email_template'] ?? 120);
@endphp

<div class="space-y-4">
    @if ($aiAvailable)
        @php
            $templateAiUrl = route('email-marketing.templates.ai');
        @endphp
        <div
            class="rounded-xl border border-indigo-200/80 bg-indigo-50/50 p-4 dark:border-indigo-500/30 dark:bg-indigo-950/20"
            x-data='{
                brief: "",
                busy: false,
                err: null,
                errFallback: @json(__('ai_template_error')),
                async generate() {
                    this.err = null;
                    const t = (document.querySelector("meta[name=\"csrf-token\"]") || {}).content;
                    if (!t) { this.err = this.errFallback; return; }
                    const briefEl = document.getElementById("email_template_ai_brief");
                    const briefText = (briefEl && briefEl.value ? briefEl.value : this.brief || "").trim();
                    this.brief = briefText;
                    this.busy = true;
                    try {
                        const res = await fetch({{ json_encode($templateAiUrl) }}, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": t,
                            },
                            body: JSON.stringify({ brief: briefText }),
                        });
                        const data = await res.json().catch(function () { return {}; });
                        if (!res.ok) { this.err = data.message || this.errFallback; return; }
                        const n = document.getElementById("name");
                        const b = document.getElementById("body_html");
                        if (n) { if (data.name) n.value = data.name; }
                        if (b) {
                            if (data.body_html && typeof window.flowdeskSetEmailBodyHtml === "function") {
                                window.flowdeskSetEmailBodyHtml("body_html", data.body_html, { showPreview: true });
                            } else if (data.body_html) {
                                b.value = data.body_html;
                            }
                        }
                    } catch (e) {
                        if (e) { if (e.message) { this.err = e.message; } else { this.err = this.errFallback; } } else { this.err = this.errFallback; }
                    } finally { this.busy = false; }
                }
            }'
        >
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ai_template_heading') }}</p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('ai_template_subtitle') }}</p>
            <x-ai-voice-wrap target-id="email_template_ai_brief" class="mt-3">
                <textarea
                    id="email_template_ai_brief"
                    rows="3"
                    class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="{{ __('ai_template_brief_placeholder') }}"
                ></textarea>
            </x-ai-voice-wrap>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                    @click="generate"
                    :disabled="busy"
                >
                    <span x-show="!busy">{{ __('ai_template_generate') }} ({{ $aiTemplateCreditCost }} {{ __('credits') }})</span>
                    <span x-show="busy" x-cloak>{{ __('ai_template_generating') }}</span>
                </button>
            </div>
            <p x-show="err" x-text="err" x-cloak class="mt-2 text-sm text-rose-600 dark:text-rose-400"></p>
        </div>
    @endif
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $t->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" :value="__('Category')" />
        <x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category', $t->category ?? '')" />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <x-email-marketing.body-html-field
        :rows="18"
        :required="true"
        :value="old('body_html', $t->body_html ?? '')"
        hint-key="email_marketing_workspace_template_body_hint"
        :show-subject-tools="false"
        :preview-email-url="route('email-marketing.campaigns.preview-email')"
        :preview-email-to="auth()->user()?->email"
    >
        <x-slot:label>
            <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <x-input-label for="body_html" class="!mt-0 sm:mb-0" :value="__('email_marketing_workspace_template_body')" />
                <x-email-marketing.starter-template-modal body-field-id="body_html" />
            </div>
        </x-slot:label>
    </x-email-marketing.body-html-field>
</div>
