@php
    $brand = (string) config('flowdesk.ai_assistant_brand_name', 'Nova');
    $creditCost = $creditCost ?? (int) config('flowdesk.ai_task_credits.assistant.modes.nova_chat', 75);
    $voiceCreditCost = (int) config('flowdesk.ai_task_credits.assistant.modes.nova_voice', 5);
    $briefingCreditCost = (int) config('flowdesk.ai_task_credits.assistant.modes.nova_briefing', 15);
    $askExamples = [
        ['topic' => 'nova_help_topic_revenue', 'items' => ['nova_help_ex_revenue_month', 'nova_help_ex_revenue_compare']],
        ['topic' => 'nova_help_topic_clients', 'items' => ['nova_help_ex_top_clients', 'nova_help_ex_client_projects', 'nova_help_ex_analyze_clients', 'nova_help_ex_analyze_client_name']],
        ['topic' => 'nova_help_topic_projects', 'items' => ['nova_help_ex_active_projects', 'nova_help_ex_open_tasks']],
        ['topic' => 'nova_help_topic_invoices', 'items' => ['nova_help_ex_unpaid', 'nova_help_ex_recent_invoices']],
        ['topic' => 'nova_help_topic_calendar', 'items' => ['nova_help_ex_calendar']],
        ['topic' => 'nova_help_topic_overview', 'items' => ['nova_help_ex_summary', 'nova_help_ex_who_are_you']],
        ['topic' => 'nova_help_topic_general', 'items' => ['nova_help_ex_crm_tips', 'nova_help_ex_cash_collection', 'nova_help_ex_flowdesk_howto']],
    ];
@endphp

<details {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/60']) }}>
    <summary class="cursor-pointer list-none text-sm font-semibold text-slate-800 dark:text-slate-100 [&::-webkit-details-marker]:hidden">
        <span class="inline-flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-sky-500" aria-hidden="true"></i>
            {{ __('nova_help_title') }}
        </span>
    </summary>

    <div class="mt-4 space-y-6 text-sm text-slate-600 dark:text-slate-300">
        <p class="leading-relaxed">{{ __('nova_help_intro', ['name' => $brand]) }}</p>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_getting_started_title') }}</h4>
            <ol class="mt-2 list-decimal space-y-1.5 ps-5">
                <li>{{ __('nova_help_step_open') }}</li>
                <li>{{ __('nova_help_step_type') }}</li>
                <li>{{ __('nova_help_step_voice', ['name' => $brand]) }}</li>
                <li>{{ __('nova_help_step_credits', ['credits' => $creditCost]) }}</li>
            </ol>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_voice_nav_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_nav_intro', ['name' => $brand]) }}</p>
            <ol class="mt-2 list-decimal space-y-1.5 ps-5">
                <li>{{ __('nova_help_voice_nav_step_topbar', ['name' => $brand]) }}</li>
                <li>{{ __('nova_help_voice_nav_step_wake', ['name' => $brand]) }}</li>
                <li>{{ __('nova_help_voice_nav_step_say') }}</li>
                <li>{{ __('nova_help_voice_nav_step_direct') }}</li>
            </ol>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_nav_credits', ['voice' => $voiceCreditCost, 'chat' => $creditCost]) }}</p>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_listen_pause_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_listen_pause_intro', ['name' => $brand]) }}</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                <p class="rounded-xl border border-rose-200/80 bg-rose-50/80 px-3 py-2 text-xs text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-100">
                    {{ __('nova_help_listen_pause_stop_phrases') }}
                </p>
                <p class="rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-3 py-2 text-xs text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100">
                    {{ __('nova_help_listen_pause_resume_phrases', ['name' => $brand]) }}
                </p>
            </div>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_free_features_title') }}</h4>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-xs">
                <li>{{ __('nova_help_free_features_nav') }}</li>
                <li>{{ __('nova_help_free_features_identity', ['name' => $brand]) }}</li>
                <li>{{ __('nova_help_free_features_client_analysis') }}</li>
            </ul>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('nova_help_free_features_paid_note', ['voice' => $voiceCreditCost, 'chat' => $creditCost, 'briefing' => $briefingCreditCost]) }}</p>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_voice_briefing_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_briefing_intro', ['name' => $brand, 'credits' => $briefingCreditCost]) }}</p>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-xs">
                <li>« {{ __('nova_help_voice_briefing_ex') }} »</li>
            </ul>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_briefing_covers') }}</p>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_workflows_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_workflows_intro', ['name' => $brand]) }}</p>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-xs">
                <li>{{ __('nova_help_workflow_create_client') }}</li>
                <li>{{ __('nova_help_workflow_create_hr') }}</li>
                <li>{{ __('nova_help_workflow_create_provider') }}</li>
                <li>{{ __('nova_help_workflow_change_vat') }}</li>
                <li>{{ __('nova_help_workflow_change_locale') }}</li>
            </ul>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_client_analysis_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_client_analysis_intro', ['name' => $brand]) }}</p>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-xs">
                <li>{{ __('nova_help_client_analysis_overview') }}</li>
                <li>{{ __('nova_help_client_analysis_by_name') }}</li>
                <li>{{ __('nova_help_client_analysis_includes') }}</li>
            </ul>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_voice_say_title') }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_say_intro') }}</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">{{ __('nova_help_voice_say_general') }}</p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li>{{ __('nova_help_voice_ex_dashboard') }}</li>
                        <li>{{ __('nova_help_voice_ex_calendar') }}</li>
                        <li>{{ __('nova_help_voice_ex_settings') }}</li>
                        <li>{{ __('nova_help_voice_ex_clients') }}</li>
                        <li>{{ __('nova_help_voice_ex_projects') }}</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">{{ __('nova_help_voice_say_sales') }}</p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li>{{ __('nova_help_voice_ex_invoices') }}</li>
                        <li>{{ __('nova_help_voice_ex_invoice_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_proposals') }}</li>
                        <li>{{ __('nova_help_voice_ex_proposal_create') }}</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">{{ __('nova_help_voice_say_insights') }}</p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li>{{ __('nova_help_voice_ex_analytics') }}</li>
                        <li>{{ __('nova_help_voice_ex_reports') }}</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">{{ __('nova_help_voice_say_marketing') }}</p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li>{{ __('nova_help_voice_ex_marketing') }}</li>
                        <li>{{ __('nova_help_voice_ex_forms') }}</li>
                        <li>{{ __('nova_help_voice_ex_audiences') }}</li>
                        <li>{{ __('nova_help_voice_ex_campaigns') }}</li>
                        <li>{{ __('nova_help_voice_ex_email_overview') }}</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40 sm:col-span-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">{{ __('nova_help_voice_say_actions') }}</p>
                    <ul class="mt-2 grid gap-1 text-xs sm:grid-cols-2">
                        <li>{{ __('nova_help_voice_ex_client_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_client_account') }}</li>
                        <li>{{ __('nova_help_voice_ex_hr_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_provider_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_analyze_clients') }}</li>
                        <li>{{ __('nova_help_voice_ex_analyze_client') }}</li>
                        <li>{{ __('nova_help_voice_ex_signup_requests') }}</li>
                        <li>{{ __('nova_help_voice_ex_inquiry_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_form_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_campaign_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_audience_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_ticket_create') }}</li>
                        <li>{{ __('nova_help_voice_ex_logout') }}</li>
                    </ul>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_say_note') }}</p>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_voice_ai_forms_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_voice_ai_forms_intro') }}</p>
            <ol class="mt-2 list-decimal space-y-1.5 ps-5 text-xs">
                <li>{{ __('nova_help_voice_ai_forms_step_say') }}</li>
                <li>{{ __('nova_help_voice_ai_forms_step_dictate') }}</li>
                <li>{{ __('nova_help_voice_ai_forms_step_fields') }}</li>
                <li>{{ __('nova_help_voice_ai_forms_step_generate') }}</li>
            </ol>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_voice_title') }}</h4>
            <ul class="mt-2 list-disc space-y-1 ps-5">
                <li>{{ __('nova_help_voice_listen') }}</li>
                <li>{{ __('nova_help_voice_speaking') }}</li>
                <li>{{ __('nova_help_voice_auto_speak') }}</li>
                <li>{{ __('nova_help_voice_wake', ['name' => $brand]) }}</li>
                <li>{{ __('nova_help_listen_pause_bullet', ['name' => $brand]) }}</li>
                <li>{{ __('nova_help_voice_browser') }}</li>
                <li>{{ __('nova_help_voice_languages') }}</li>
            </ul>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_identity_title', ['name' => $brand]) }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_identity_intro', ['name' => $brand]) }}</p>
            <p class="mt-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-200">
                « {{ __('nova_help_ex_who_are_you') }} »
            </p>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_question_types_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_question_types_intro') }}</p>
            <ul class="mt-2 list-disc space-y-1.5 ps-5 text-xs">
                <li>{{ __('nova_help_question_types_workspace') }}</li>
                <li>{{ __('nova_help_question_types_general') }}</li>
            </ul>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_stop_title') }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('nova_help_stop_intro') }}</p>
            <p class="mt-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-200">
                {{ __('nova_help_stop_phrases') }}
            </p>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_ask_title') }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('nova_help_ask_intro') }}</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($askExamples as $group)
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-xs font-bold uppercase tracking-wide text-sky-600 dark:text-sky-400">{{ __($group['topic']) }}</p>
                        <ul class="mt-2 space-y-1.5 text-xs">
                            @foreach ($group['items'] as $key)
                                @php($text = __($key))
                                <li>
                                    <button
                                        type="button"
                                        class="w-full rounded-lg border border-transparent px-2 py-1.5 text-start text-slate-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-800 dark:text-slate-200 dark:hover:border-sky-500/30 dark:hover:bg-sky-950/40 dark:hover:text-sky-200"
                                        onclick="window.dispatchEvent(new CustomEvent('nova-ask-example', { detail: @js($text) }))"
                                    >
                                        “{{ $text }}”
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('nova_help_limits_title') }}</h4>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-xs">
                <li>{{ __('nova_help_limits_data') }}</li>
                <li>{{ __('nova_help_limits_actions') }}</li>
                <li>{{ __('nova_help_limits_review') }}</li>
            </ul>
        </section>

        <p class="text-xs text-slate-500 dark:text-slate-400">
            <i class="fa-solid fa-book me-1" aria-hidden="true"></i>
            {{ __('nova_help_admin_doc') }}
            <code class="rounded bg-slate-100 px-1 py-0.5 text-[10px] dark:bg-slate-800">NOVA.md</code>
        </p>
    </div>
</details>
