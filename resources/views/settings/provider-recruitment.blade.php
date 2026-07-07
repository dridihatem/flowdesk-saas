<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Provider recruitment') }}</h2>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    @endpush

    <div class="py-12">
        <div class="max-w-6xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-flow border border-flow-border bg-flow-surface p-6 shadow-sm">
                <p class="text-sm text-flow-text-muted">{{ __('Share the public link so independent providers can request to work with your workspace. Each one must sign the partnership; then a company admin signs to activate their account.') }}</p>

                <form id="provider-recruitment-form" method="POST" action="{{ route('settings.provider-recruitment.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-8 lg:grid-cols-3">
                        <div class="space-y-6 lg:col-span-2">
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="provider_recruitment_enabled" value="0" />
                                <input
                                    id="provider_recruitment_enabled"
                                    type="checkbox"
                                    name="provider_recruitment_enabled"
                                    value="1"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                                    @checked(old('provider_recruitment_enabled', $company->provider_recruitment_enabled))
                                />
                                <x-input-label for="provider_recruitment_enabled" :value="__('Open provider recruitment (public signup link)')" class="!mb-0" />
                            </div>

                            <div>
                                <x-input-label for="provider_recruitment_slug" :value="__('Link slug (appears after /partner/)')" />
                                <x-text-input
                                    id="provider_recruitment_slug"
                                    name="provider_recruitment_slug"
                                    type="text"
                                    class="mt-1 block w-full font-mono text-sm"
                                    :value="old('provider_recruitment_slug', $company->provider_recruitment_slug)"
                                    placeholder="acme-providers"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Lowercase letters, numbers, and hyphens only. Leave empty to auto-generate when you enable recruitment.') }}</p>
                                <x-input-error :messages="$errors->get('provider_recruitment_slug')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-2">
                                <input id="regenerate_slug" name="regenerate_slug" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800" />
                                <label for="regenerate_slug" class="text-sm text-slate-700 dark:text-slate-300">{{ __('Generate a new slug from workspace name') }}</label>
                            </div>

                            @if ($company->providerRecruitmentUrl())
                                <div class="rounded-lg border border-indigo-200/80 bg-indigo-50/50 p-4 dark:border-indigo-800/50 dark:bg-indigo-950/30">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">{{ __('Public signup URL') }}</p>
                                    <p class="mt-2 break-all font-mono text-sm text-indigo-950 dark:text-indigo-100">{{ $company->providerRecruitmentUrl() }}</p>
                                    <button type="button" class="mt-3 text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300" data-copy="{{ $company->providerRecruitmentUrl() }}">{{ __('Copy link') }}</button>
                                </div>
                            @endif

                            <div>
                                <x-input-label for="provider_partnership_terms" :value="__('Partnership contract body')" />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Rich editor for partnership agreement') }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Insert sample contract by language') }}:</span>
                                    <button type="button" data-sample-locale="fr" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Lang French short') }}</button>
                                    <button type="button" data-sample-locale="en" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Lang English short') }}</button>
                                    <button type="button" data-sample-locale="es" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Lang Spanish short') }}</button>
                                    <button type="button" data-sample-locale="ar" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Lang Arabic short') }}</button>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Sample contract replaces editor content') }}</p>
                                <textarea
                                    id="provider_partnership_terms"
                                    name="provider_partnership_terms"
                                    rows="10"
                                    class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                    placeholder="{{ __('If empty, a standard short agreement text is used.') }}"
                                >{{ old('provider_partnership_terms', $company->provider_partnership_terms) }}</textarea>
                                <x-input-error :messages="$errors->get('provider_partnership_terms')" class="mt-2" />
                            </div>

                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>

                        <aside class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-600/60 dark:bg-slate-900/40">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Template placeholders') }}</h3>
                            <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ __('Partnership template placeholders help') }}</p>
                            <ul class="mt-3 flex flex-col gap-2">
                                @foreach ($partnershipTemplateHints as $hint)
                                    @php
                                        $token = '{{'.$hint['key'].'}}';
                                    @endphp
                                    <li>
                                        <button
                                            type="button"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-left text-xs font-medium text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700/80"
                                            data-insert-token="{{ $token }}"
                                        >
                                            <span class="font-mono text-indigo-700 dark:text-indigo-300">{{ $token }}</span>
                                            <span class="mt-0.5 block font-normal text-slate-500 dark:text-slate-400">{{ $hint['label'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </aside>
                    </div>
                </form>
            </div>

            <div class="rounded-flow border border-flow-border bg-flow-surface p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Providers and contract status') }}</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Providers contract status help') }}</p>

                <div class="mt-4 overflow-x-auto">
                    @if ($partnershipProviders->isEmpty())
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('No providers yet.') }}</p>
                    @else
                        <table class="min-w-full divide-y divide-slate-200 text-start text-sm dark:divide-slate-700">
                            <thead class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="py-2 pr-4 text-start">{{ __('Full name') }}</th>
                                    <th class="py-2 pr-4 text-start">{{ __('Email') }}</th>
                                    <th class="py-2 pr-4 text-start">{{ __('Partnership status') }}</th>
                                    <th class="py-2 pr-4 text-start">{{ __('Provider signed') }}</th>
                                    <th class="py-2 pr-4 text-start">{{ __('Company signed') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                @foreach ($partnershipProviders as $p)
                                    <tr class="text-slate-800 dark:text-slate-200">
                                        <td class="py-3 pr-4 font-medium text-start">{{ $p->name }}</td>
                                        <td class="py-3 pr-4 font-mono text-xs text-start">{{ $p->email ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-start">
                                            @if ($p->isPartnershipActive())
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100">{{ __('Approved provider') }}</span>
                                            @else
                                                <span class="text-xs">{{ $p->partnership_status->label() }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-xs text-slate-600 dark:text-slate-400 text-start">
                                            @if ($p->partnership_provider_signed_at)
                                                {{ $p->partnership_provider_signed_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-xs text-slate-600 dark:text-slate-400 text-start">
                                            @if ($p->partnership_company_signed_at)
                                                {{ $p->partnership_company_signed_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $providerRecruitmentSampleTermsUrls = [
            'fr' => route('settings.provider-recruitment.sample-terms', ['locale' => 'fr']),
            'en' => route('settings.provider-recruitment.sample-terms', ['locale' => 'en']),
            'es' => route('settings.provider-recruitment.sample-terms', ['locale' => 'es']),
            'ar' => route('settings.provider-recruitment.sample-terms', ['locale' => 'ar']),
        ];
    @endphp

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
        <script>
            (function () {
                const sampleTermsUrls = @json($providerRecruitmentSampleTermsUrls);
                const confirmSampleMsg = {!! json_encode(__('Replace contract body with the sample text for this language?')) !!};

                document.querySelector('[data-copy]')?.addEventListener('click', function () {
                    const t = this.getAttribute('data-copy');
                    if (t && navigator.clipboard) navigator.clipboard.writeText(t);
                });

                const ta = document.getElementById('provider_partnership_terms');
                const form = document.getElementById('provider-recruitment-form');
                if (!ta) return;

                let $ta = null;
                if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
                    $ta = jQuery(ta);
                    $ta.summernote({
                        height: 320,
                        placeholder: {!! json_encode(__('If empty, a standard short agreement text is used.')) !!},
                        toolbar: [
                            ['style', ['bold', 'italic', 'underline', 'clear']],
                            ['para', ['ul', 'ol']],
                            ['insert', ['link']],
                        ],
                    });
                }

                function setEditorHtml(html) {
                    if ($ta && $ta.next('.note-editor').length) {
                        $ta.summernote('code', html);
                    } else {
                        const d = document.createElement('div');
                        d.innerHTML = html;
                        ta.value = d.textContent || d.innerText || '';
                    }
                }

                function insertAtCursor(text) {
                    if ($ta && $ta.next('.note-editor').length) {
                        $ta.summernote('focus');
                        $ta.summernote('pasteHTML', text);
                    } else {
                        const start = ta.selectionStart ?? ta.value.length;
                        const end = ta.selectionEnd ?? ta.value.length;
                        ta.value = ta.value.slice(0, start) + text + ta.value.slice(end);
                        ta.selectionStart = ta.selectionEnd = start + text.length;
                        ta.focus();
                    }
                }

                document.querySelectorAll('[data-insert-token]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const token = this.getAttribute('data-insert-token');
                        if (token) insertAtCursor(token);
                    });
                });

                document.querySelectorAll('[data-sample-locale]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const loc = this.getAttribute('data-sample-locale');
                        const url = loc ? sampleTermsUrls[loc] : null;
                        if (!url || !window.confirm(confirmSampleMsg)) return;
                        fetch(url, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        })
                            .then(function (r) {
                                if (!r.ok) throw new Error('sample-terms');
                                return r.json();
                            })
                            .then(function (data) {
                                if (data && data.html) setEditorHtml(data.html);
                            })
                            .catch(function () {
                                alert({!! json_encode(__('Could not load sample text. Try again.')) !!});
                            });
                    });
                });

                form?.addEventListener('submit', function () {
                    if ($ta && $ta.next('.note-editor').length) {
                        ta.value = $ta.summernote('code');
                    }
                });
            })();
        </script>
    @endpush
</x-app-layout>
