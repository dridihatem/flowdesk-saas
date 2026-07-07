@php
    $readonly = ($campaign?->status ?? null) === 'sent';
    $audienceContacts = $audienceContacts ?? collect();
    $selectedContactIds = $selectedContactIds ?? [];
    $oldScope = old('recipient_scope', $campaign?->recipient_scope ?? 'all');
    $aiAvailable = $aiAvailable ?? false;
    $aiCampaignCreditCost = (int) ($flowdeskAiTaskCredits['email_campaign_content'] ?? 150);
    $modelTemplates = $modelTemplates ?? [];
    $templatePickerData = [
        'models' => collect($modelTemplates)->map(fn ($m, $slug) => [
            'key' => 'model:'.$slug,
            'name' => $m['name'] ?? $slug,
            'body_html' => $m['body_html'] ?? '',
        ])->values()->all(),
        'workspace' => ($templates ?? collect())->map(fn ($t) => [
            'key' => 'workspace:'.$t->id,
            'id' => (string) $t->id,
            'name' => $t->name,
            'body_html' => $t->body_html ?? '',
        ])->values()->all(),
    ];
@endphp

<div
    x-data="{
        scope: @js($oldScope),
    }"
    class="space-y-4"
>
    @if ($aiAvailable && ! $readonly)
        @php
            $campaignContentAiUrl = route('email-marketing.campaigns.content-ai');
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
                    const briefEl = document.getElementById("email_campaign_ai_brief");
                    const briefText = (briefEl && briefEl.value ? briefEl.value : this.brief || "").trim();
                    this.brief = briefText;
                    this.busy = true;
                    try {
                        const res = await fetch({{ json_encode($campaignContentAiUrl) }}, {
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
                        const s = document.getElementById("subject");
                        const b = document.getElementById("body_html");
                        if (s) { if (data.subject) { s.value = data.subject; } }
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
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ai_campaign_heading') }}</p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('ai_campaign_subtitle') }}</p>
            <x-ai-voice-wrap target-id="email_campaign_ai_brief" class="mt-3">
                <textarea
                    id="email_campaign_ai_brief"
                    rows="3"
                    class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="{{ __('ai_campaign_brief_placeholder') }}"
                ></textarea>
            </x-ai-voice-wrap>
            <div class="mt-3">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                    @click="generate"
                    :disabled="busy"
                >
                    <span x-show="!busy">{{ __('ai_campaign_generate') }} ({{ $aiCampaignCreditCost }} {{ __('credits') }})</span>
                    <span x-show="busy" x-cloak>{{ __('ai_template_generating') }}</span>
                </button>
            </div>
            <p x-show="err" x-text="err" x-cloak class="mt-2 text-sm text-rose-600 dark:text-rose-400"></p>
        </div>
    @endif
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $campaign?->name ?? '')" :disabled="$readonly" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="audience_id" :value="__('email_marketing_campaign_audience')" />
        <select
            id="audience_id"
            name="audience_id"
            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
            @disabled($readonly)
            @if (! $readonly)
                onchange="(function (el) { const u = new URL(window.location.href); if (el.value) { u.searchParams.set('audience_id', el.value); } else { u.searchParams.delete('audience_id'); } window.location.href = u.toString(); })(this)"
            @endif
        >
            <option value="">{{ __('email_marketing_campaign_audience_placeholder') }}</option>
            @foreach ($audiences as $aud)
                <option value="{{ $aud->id }}" @selected((string) old('audience_id', $campaign?->audience_id ?? (request('audience_id') ?? '')) === (string) $aud->id)>
                    {{ $aud->name }} ({{ __('email_marketing_recipient_count', ['count' => $aud->contacts_count]) }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_campaign_audience_help') }}</p>
        <x-input-error :messages="$errors->get('audience_id')" class="mt-2" />
    </div>

    <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('email_marketing_recipient_targeting') }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_recipient_targeting_help') }}</p>
        <div class="mt-3 space-y-2 text-sm text-slate-800 dark:text-slate-200">
            <label class="flex items-start gap-2">
                <input type="radio" name="recipient_scope" value="all" x-model="scope" @disabled($readonly) class="mt-0.5 text-indigo-600" />
                <span>{{ __('email_marketing_all_in_audience') }}</span>
            </label>
            <label class="flex items-start gap-2">
                <input type="radio" name="recipient_scope" value="selected" x-model="scope" @disabled($readonly) class="mt-0.5 text-indigo-600" />
                <span>{{ __('email_marketing_select_contacts') }}</span>
            </label>
        </div>
        <div x-show="scope === 'selected'" x-cloak class="mt-3 max-h-64 space-y-2 overflow-y-auto rounded-lg border border-slate-200/80 bg-white p-3 dark:border-slate-600/50 dark:bg-slate-900/40 sm:max-h-80">
            @if ($audienceContacts->isEmpty())
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_select_contacts_empty') }}</p>
            @else
                @foreach ($audienceContacts as $c)
                    <label class="flex cursor-pointer items-center gap-2 rounded-md px-1 py-1 hover:bg-slate-100 dark:hover:bg-slate-800/80">
                        <input
                            type="checkbox"
                            name="contact_ids[]"
                            value="{{ $c->id }}"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                            @disabled($readonly)
                            @checked(in_array((string) $c->id, array_map('strval', $selectedContactIds), true))
                        />
                        <span class="text-sm text-slate-800 dark:text-slate-200">{{ $c->name ? $c->name.' — ' : '' }}{{ $c->email }}</span>
                    </label>
                @endforeach
            @endif
        </div>
        <x-input-error :messages="$errors->get('contact_ids')" class="mt-2" />
    </div>

    <div
        x-data="{
            picker: @js($templatePickerData),
            applyTemplateKey(key) {
                if (!key) { return; }
                let html = '';
                let workspaceId = '';
                const model = this.picker.models.find((t) => t.key === key);
                const workspace = this.picker.workspace.find((t) => t.key === key);
                if (model) {
                    html = model.body_html || '';
                } else if (workspace) {
                    html = workspace.body_html || '';
                    workspaceId = workspace.id || '';
                }
                const hidden = document.getElementById('workspace_template_id');
                if (hidden) { hidden.value = workspaceId; }
                if (html && typeof window.flowdeskSetEmailBodyHtml === 'function') {
                    window.flowdeskSetEmailBodyHtml('body_html', html, { showPreview: true });
                }
            }
        }"
    >
        <x-input-label for="campaign_template_picker" :value="__('email_marketing_campaign_apply_template')" />
        <select
            id="campaign_template_picker"
            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
            @disabled($readonly)
            @change="applyTemplateKey($event.target.value)"
        >
            <option value="">{{ __('email_marketing_campaign_apply_template_none') }}</option>
            @if (! empty($modelTemplates))
                <optgroup label="{{ __('email_marketing_template_models_heading') }}">
                    @foreach ($modelTemplates as $slug => $model)
                        <option value="model:{{ $slug }}">{{ $model['name'] }}</option>
                    @endforeach
                </optgroup>
            @endif
            @if (($templates ?? collect())->isNotEmpty())
                <optgroup label="{{ __('email_marketing_your_templates') }}">
                    @foreach ($templates as $tpl)
                        <option value="workspace:{{ $tpl->id }}" @selected(old('workspace_template_id') === $tpl->id)>{{ $tpl->name }}</option>
                    @endforeach
                </optgroup>
            @endif
        </select>
        <input type="hidden" id="workspace_template_id" name="workspace_template_id" value="{{ old('workspace_template_id', '') }}" />
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_campaign_apply_template_help') }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_campaign_template_preview_hint') }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_apply_template_merge_hint') }}</p>
        <x-input-error :messages="$errors->get('workspace_template_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="subject" :value="__('Subject')" />
        <x-text-input id="subject" name="subject" class="mt-1 block w-full" :value="old('subject', $campaign?->subject ?? '')" :disabled="$readonly" />
        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
    </div>

    @if (! $readonly)
        <x-email-marketing.body-html-field
            :rows="16"
            :required="true"
            :value="old('body_html', $campaign?->body_html ?? '')"
            :show-subject-tools="true"
            :preview-email-url="route('email-marketing.campaigns.preview-email')"
            :preview-campaign-id="$campaign?->id"
            :preview-email-to="auth()->user()?->email"
        >
            <x-slot:label>
                <x-input-label for="body_html" :value="__('email_marketing_campaign_body_html')" />
            </x-slot:label>
        </x-email-marketing.body-html-field>
    @else
        <div>
            <x-input-label for="body_html" :value="__('email_marketing_campaign_body_html')" />
            <textarea
                id="body_html"
                name="body_html"
                rows="16"
                class="mt-1 block w-full rounded-lg border border-slate-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                disabled
            >{{ old('body_html', $campaign?->body_html ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('body_html')" class="mt-2" />
        </div>
    @endif
</div>
