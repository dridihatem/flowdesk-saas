@php
    $projectWizardPanes = [
        ['id' => 0, 'label' => __('General information')],
        ['id' => 1, 'label' => __('Content')],
        ['id' => 2, 'label' => __('Budget & schedule')],
        ['id' => 3, 'label' => __('People & organization')],
    ];
    $projectWizardInitialStep = 0;
    if ($errors->isNotEmpty()) {
        if ($errors->has('title') || $errors->has('description') || $errors->has('client_id') || $errors->has('attachment') || $errors->has('attachment_category')) {
            $projectWizardInitialStep = 1;
        } elseif ($errors->has('final_price') || $errors->has('negotiated_price') || $errors->has('final_deadline')) {
            $projectWizardInitialStep = 2;
        } elseif ($errors->has('provider_id')) {
            $projectWizardInitialStep = 3;
        } else {
            foreach (array_keys($errors->getMessages()) as $errKey) {
                if (str_starts_with($errKey, 'team_user_ids')) {
                    $projectWizardInitialStep = 3;
                    break;
                }
            }
        }
    }
    $flowdeskProjectWizardI18n = [
        'titleRequired' => __('The title is required.'),
        'initialStep' => $projectWizardInitialStep,
    ];
@endphp
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/smartwizard@7.0.2/dist/css/smartwizard.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/smartwizard@7.0.2/dist/css/themes/pills.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet" />
    <style>
        [id="flowdesk-smart-wizard"] { --sw-radius: 12px; }

        /* Toolbar buttons: same language as “theme template” selection (ring + rounded-xl) */
        #flowdesk-smart-wizard .sw-toolbar {
            padding: 1rem 0.25rem 0.25rem;
            border-top: 1px solid color-mix(in srgb, var(--flow-border, #e2e8f0) 80%, transparent);
            margin-top: 1.25rem;
        }
        html.dark #flowdesk-smart-wizard .sw-toolbar {
            border-top-color: color-mix(in srgb, var(--flow-border, #334155) 80%, transparent);
        }
        #flowdesk-smart-wizard .sw-toolbar .btn-group,
        #flowdesk-smart-wizard .sw-toolbar .d-flex {
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        #flowdesk-smart-wizard .sw-btn-prev,
        #flowdesk-smart-wizard .sw-btn-next,
        #flowdesk-smart-wizard .sw-btn,
        #flowdesk-smart-wizard #flowdesk-wizard-final-submit {
            border-radius: 0.75rem !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
            padding: 0.625rem 1.15rem !important;
            min-height: 2.75rem !important;
            text-transform: none !important;
            letter-spacing: 0.01em !important;
            transition: box-shadow 0.15s, border-color 0.15s, background 0.15s, color 0.15s;
        }
        /* Secondary: unselected “card” feel */
        #flowdesk-smart-wizard .sw-btn-prev {
            background: #fff !important;
            color: #334155 !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
        }
        html.dark #flowdesk-smart-wizard .sw-btn-prev {
            background: rgb(15 23 42 / 0.85) !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }
        #flowdesk-smart-wizard .sw-btn-prev:hover {
            border-color: #cbd5e1 !important;
        }
        html.dark #flowdesk-smart-wizard .sw-btn-prev:hover {
            border-color: #64748b !important;
        }
        /* Primary: “selected” theme block — ring-2 on brand color + soft fill */
        #flowdesk-smart-wizard .sw-btn-next,
        #flowdesk-smart-wizard #flowdesk-wizard-final-submit {
            color: #312e81 !important;
            border: 1px solid color-mix(in srgb, var(--flow-primary, #4f46e5) 28%, #e0e7ff) !important;
            background: linear-gradient(
                to right,
                color-mix(in srgb, var(--flow-primary, #4f46e5) 9%, #fff),
                #fff
            ) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--flow-primary, #6366f1) 55%, transparent), 0 1px 2px 0 rgb(0 0 0 / 0.06) !important;
        }
        html.dark #flowdesk-smart-wizard .sw-btn-next,
        html.dark #flowdesk-smart-wizard #flowdesk-wizard-final-submit {
            color: #e0e7ff !important;
            border-color: color-mix(in srgb, var(--flow-primary, #6366f1) 40%, #312e81) !important;
            background: linear-gradient(
                to right,
                color-mix(in srgb, var(--flow-primary, #4f46e5) 18%, #0f172a),
                color-mix(in srgb, var(--flow-primary, #4f46e5) 8%, #1e293b)
            ) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--flow-primary, #818cf8) 45%, transparent), 0 1px 2px 0 rgb(0 0 0 / 0.2) !important;
        }
        #flowdesk-smart-wizard .sw-btn-next:hover,
        #flowdesk-smart-wizard #flowdesk-wizard-final-submit:hover {
            filter: brightness(0.99);
        }
        html.dark #flowdesk-smart-wizard .sw-btn-next:hover,
        html.dark #flowdesk-smart-wizard #flowdesk-wizard-final-submit:hover {
            filter: brightness(1.05);
        }
    </style>
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/smartwizard@7.0.2/dist/js/jquery.smartWizard.min.js" crossorigin="anonymous"></script>
    <script>
        (function initFlowdeskProjectWizard() {
            const cfg = @json($flowdeskProjectWizardI18n);
            const l10n = {
                next: @json(__('Next')),
                previous: @json(__('Previous step')),
                create: @json(__('Create project')),
            };

            function getPaneInputs(pane) {
                if (!pane) {
                    return [];
                }
                return Array.from(pane.querySelectorAll('input, select, textarea')).filter((el) => {
                    if (el.id === 'title' && (el.type === 'button' || el.type === 'submit')) {
                        return false;
                    }
                    return !el.closest('[data-wizard-ignore-validation]');
                });
            }

            function ensureSummernote() {
                if (!window.jQuery) {
                    return;
                }
                const $d = window.jQuery('#description');
                if ($d.length && !$d.data('summernote')) {
                    $d.summernote({
                        height: 280,
                        dialogsInBody: true,
                        placeholder: @json(__('Summary, needs, and internal context — cahier des charges can be attached as a file below.')),
                    });
                }
            }

            function syncSummernote() {
                if (!window.jQuery) {
                    return;
                }
                const $d = window.jQuery('#description');
                if ($d.length && $d.data('summernote') && $d[0].tagName === 'TEXTAREA') {
                    $d.val($d.summernote('code'));
                }
            }

            function validateCurrentStep(stepIndex) {
                const panes = document.querySelectorAll('#flowdesk-smart-wizard .tab-content .tab-pane');
                const pane = panes[stepIndex];
                if (stepIndex === 1) {
                    const title = document.getElementById('title');
                    if (title) {
                        const ok = String(title.value || '').trim() !== '';
                        if (!ok) {
                            title.setCustomValidity(cfg.titleRequired);
                            title.reportValidity();
                            title.setCustomValidity('');
                            if (title.scrollIntoView) {
                                title.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            }
                            return false;
                        }
                    }
                }
                const inputs = getPaneInputs(pane);
                for (const el of inputs) {
                    if (el.id === 'title' && stepIndex === 1) {
                        continue;
                    }
                    if (el.willValidate && !el.checkValidity()) {
                        el.reportValidity();
                        if (el.scrollIntoView) {
                            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }
                        return false;
                    }
                }
                return true;
            }

            function validateFormForSubmit() {
                syncSummernote();
                const title = document.getElementById('title');
                if (!title || String(title.value || '').trim() === '') {
                    if (window.jQuery) {
                        jQuery('#flowdesk-smart-wizard').smartWizard('goToStep', 1, true);
                    }
                    if (title) {
                        title.setCustomValidity(cfg.titleRequired);
                        title.reportValidity();
                        title.setCustomValidity('');
                    }
                    return false;
                }
                return true;
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery || !jQuery.fn.smartWizard) {
                    return;
                }
                const $w = jQuery('#flowdesk-smart-wizard');
                if ($w.length === 0) {
                    return;
                }
                // Match Flowdesk (html.dark), not OS prefers-color-scheme — "auto" only followed the system theme.
                const swDisplayMode = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                $w.smartWizard({
                    initialStep: cfg.initialStep,
                    theme: 'pills',
                    displayMode: swDisplayMode,
                    behavior: { autoHeight: true, useUrlHash: false, supportBrowserHistory: false },
                    transition: { effect: 'fade', speed: 220 },
                    toolbar: {
                        position: 'bottom',
                        extraElements: '<button type="button" class="btn sw-btn sw-btn-next" id="flowdesk-wizard-final-submit" style="display:none">' + l10n.create + '</button>',
                    },
                    localization: { buttons: { next: l10n.next, previous: l10n.previous } },
                });

                $w.on('leave.sw', function (e, args) {
                    if (args.stepDirection !== 'forward') {
                        return;
                    }
                    if (!validateCurrentStep(args.stepIndex)) {
                        e.preventDefault();
                    }
                });

                $w.on('shown.sw', function (e, args) {
                    const last = args.stepIndex === 3;
                    $w.find('.sw-btn-next').not('#flowdesk-wizard-final-submit').css('display', last ? 'none' : '');
                    $w.find('#flowdesk-wizard-final-submit').css('display', last ? 'inline-flex' : 'none');
                    if (args.stepIndex === 1) {
                        ensureSummernote();
                    }
                    setTimeout(function () {
                        $w.smartWizard('adjustHeight');
                    }, 0);
                });

                document.getElementById('flowdesk-wizard-final-submit')?.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    if (!validateFormForSubmit()) {
                        return;
                    }
                    const form = document.getElementById('flowdesk-project-create-form');
                    if (form) {
                        if (form.requestSubmit) {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    }
                });

                if (Number(cfg.initialStep) === 1) {
                    ensureSummernote();
                } else {
                    setTimeout(ensureSummernote, 0);
                }
            });
        })();
    </script>
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('New project') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl w-full sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/90 via-white to-cyan-50/50 p-5 shadow-sm dark:border-indigo-500/20 dark:from-indigo-950/40 dark:via-slate-900/40 dark:to-slate-900/30 sm:p-6">
                <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">{{ __('Project form wizard lead') }}</p>
                <p class="mt-1 text-sm leading-relaxed text-indigo-800/90 dark:text-indigo-200/85">{{ __('Project form wizard hint') }}</p>
            </div>

            <div class="mb-2 flex items-center justify-end">
                <a
                    href="{{ route('projects.index') }}"
                    class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-indigo-600 dark:text-slate-300 dark:decoration-slate-600"
                >{{ __('Cancel') }}</a>
            </div>

            <div class="flow-panel overflow-x-clip p-0 sm:overflow-visible sm:p-0">
                <form
                    id="flowdesk-project-create-form"
                    method="POST"
                    action="{{ route('projects.store') }}"
                    enctype="multipart/form-data"
                >
                    @csrf

                    <div id="flowdesk-smart-wizard" class="w-full" data-color="indigo">
                        <ul class="nav" role="tablist" aria-label="{{ __('Project form steps') }}">
                            @foreach ($projectWizardPanes as $p)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="sw-tab-{{ $p['id'] }}" href="#sw-pane-{{ $p['id'] }}" role="tab" aria-controls="sw-pane-{{ $p['id'] }}">
                                        <div class="badge" aria-hidden="true">{{ $p['id'] + 1 }}</div>
                                        <span class="whitespace-normal break-words text-start">{{ $p['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content px-0 sm:px-1" id="sw-tab-content">
                            <div
                                id="sw-pane-0"
                                class="tab-pane"
                                role="tabpanel"
                                aria-labelledby="sw-tab-0"
                            >
                                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('General information') }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Status and how this project was sourced.') }}</p>
                                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="status" :value="__('Status')" />
                                        <select id="status" name="status" class="flow-input-select mt-2 block w-full">
                                            @foreach (\App\Enums\ProjectStatus::cases() as $case)
                                                <option value="{{ $case->value }}" @selected(old('status', 'draft') === $case->value)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="source" :value="__('Source')" />
                                        <select id="source" name="source" class="flow-input-select mt-2 block w-full">
                                            @foreach (\App\Enums\ProjectSource::cases() as $case)
                                                <option value="{{ $case->value }}" @selected(old('source', 'internal') === $case->value)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Use “Manual / internal” unless this project came from a form, provider, or inquiry.') }}</p>
                                        <x-input-error :messages="$errors->get('source')" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div
                                id="sw-pane-1"
                                class="tab-pane"
                                role="tabpanel"
                                aria-labelledby="sw-tab-1"
                            >
                                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Title, client, description & spec') }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Start with the essentials: name the project, link a prospect, describe it, and attach a cahier des charges if you have one.') }}</p>
                                <div class="mt-4 space-y-5">
                                    <div>
                                        <x-input-label for="title" :value="__('Title')" />
                                        <x-text-input id="title" name="title" type="text" class="mt-2 block w-full" value="{{ old('title') }}" />
                                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-project-client-quick-add>
                                            <div>
                                                <x-input-label for="client_id" :value="__('Client (prospect)')" />
                                                <select id="client_id" name="client_id" class="flow-input-select mt-2 block w-full">
                                                    <option value="">{{ __('None') }}</option>
                                                    @foreach ($clients as $client)
                                                        <option value="{{ $client->id }}" @selected(old('client_id', request('client')) === $client->id)>{{ $client->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </x-project-client-quick-add>
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-end justify-between gap-2">
                                            <x-input-label for="description" :value="__('Description')" />
                                            <x-project-description-ai textarea-id="description" />
                                        </div>
                                        <textarea id="description" name="description" class="mt-2 block w-full min-h-[120px]">{{ old('description') }}</textarea>
                                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                    </div>
                                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                                        <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Cahier des charges (file)') }}</h4>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Upload a brief, SOW, signed quote, or full specification — optional.') }}</p>
                                        <div class="mt-3">
                                            <x-input-label for="attachment" :value="__('Attachment (optional)')" />
                                            <input
                                                id="attachment"
                                                type="file"
                                                name="attachment"
                                                class="mt-2 block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-200"
                                            />
                                            <div class="mt-3">
                                                <x-input-label for="attachment_category" :value="__('File category')" />
                                                <select id="attachment_category" name="attachment_category" class="flow-input-select mt-1 block w-full text-sm">
                                                    @foreach (\App\Enums\ProjectFileCategory::cases() as $cat)
                                                        <option value="{{ $cat->value }}" @selected(old('attachment_category', 'document') === $cat->value)>{{ $cat->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                {{ __('PDF, Office, images, or ZIP. Max :mb MB per file.', ['mb' => number_format(config('flowdesk.project_files.max_file_kb', 12288) / 1024, 0)]) }}
                                            </p>
                                            <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                                            <x-input-error :messages="$errors->get('attachment_category')" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                id="sw-pane-2"
                                class="tab-pane"
                                role="tabpanel"
                                aria-labelledby="sw-tab-2"
                            >
                                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Budget & schedule') }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Internal amounts and the target end date (optional).') }}</p>
                                <div class="mt-4 space-y-5">
                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div>
                                            <x-input-label for="final_price" :value="__('Final price (:cur)', ['cur' => auth()->user()->company?->default_currency ?? 'USD'])" />
                                            <x-text-input id="final_price" name="final_price" type="text" inputmode="decimal" class="mt-2 block w-full flowdesk-amount" value="{{ old('final_price') }}" />
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Company-only; amount in your workspace default currency.') }}</p>
                                            <x-input-error :messages="$errors->get('final_price')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="negotiated_price" :value="__('Negotiated price (:cur)', ['cur' => auth()->user()->company?->default_currency ?? 'USD'])" />
                                            <x-text-input id="negotiated_price" name="negotiated_price" type="text" inputmode="decimal" class="mt-2 block w-full flowdesk-amount" value="{{ old('negotiated_price') }}" />
                                            <x-input-error :messages="$errors->get('negotiated_price')" class="mt-2" />
                                        </div>
                                    </div>
                                    <div>
                                        <x-input-label for="final_deadline" :value="__('Final deadline')" />
                                        <input id="final_deadline" type="date" name="final_deadline" value="{{ old('final_deadline') }}" class="flow-input-select mt-2 block w-full" />
                                        <x-input-error :messages="$errors->get('final_deadline')" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div
                                id="sw-pane-3"
                                class="tab-pane"
                                role="tabpanel"
                                aria-labelledby="sw-tab-3"
                            >
                                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('People & organization') }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Link a provider for commissions, and assign the internal team.') }}</p>
                                <div class="mt-4 space-y-5">
                                    <div>
                                        <x-input-label for="provider_id" :value="__('Provider (optional)')" />
                                        <select id="provider_id" name="provider_id" class="flow-input-select mt-2 block w-full">
                                            <option value="">{{ __('None') }}</option>
                                            @foreach ($providers as $provider)
                                                <option value="{{ $provider->id }}" @selected(old('provider_id') === $provider->id)>{{ $provider->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Link a business provider when commissions or partner workflows apply.') }}</p>
                                    </div>
                                    <fieldset class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                                        <legend class="px-1 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Assign team') }}</legend>
                                        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Select workspace members responsible for this project.') }}</p>
                                        <div class="max-h-48 space-y-2 overflow-y-auto pr-1">
                                            @foreach ($teamUsers as $user)
                                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-white/80 dark:hover:bg-slate-800/60">
                                                    <input
                                                        type="checkbox"
                                                        name="team_user_ids[]"
                                                        value="{{ $user->id }}"
                                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                                                        @checked(in_array((int) $user->id, array_map('intval', (array) old('team_user_ids', [])), true))
                                                    />
                                                    <span class="text-sm text-slate-800 dark:text-slate-200">{{ $user->name }} <span class="text-slate-500">({{ $user->email }})</span></span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <x-input-error :messages="$errors->get('team_user_ids')" class="mt-2" />
                                        <x-input-error :messages="$errors->get('team_user_ids.*')" class="mt-2" />
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
