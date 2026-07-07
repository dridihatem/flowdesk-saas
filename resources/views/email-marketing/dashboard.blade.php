<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Email marketing')"
                :description="__('email_marketing_hub_intro')"
            />

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
                <x-flow.stat-card :label="__('Campaigns')" variant="indigo">
                    {{ number_format($campaignsCount) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('email_marketing_stat_sent')" variant="cyan">
                    {{ number_format($emailsSentTotal) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('email_marketing_stat_opened')" variant="emerald">
                    {{ number_format($emailsOpenedTotal) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('email_marketing_stat_open_rate')" variant="amber">
                    @if ($emailOpenRate !== null)
                        {{ $emailOpenRate }}%
                    @else
                        —
                    @endif
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('Templates')" variant="emerald">
                    {{ number_format($templatesCount) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('Audiences')" variant="cyan">
                    {{ number_format($audiencesCount) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('Automations')" variant="amber">
                    {{ number_format($sequencesCount) }}
                </x-flow.stat-card>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2">
                <a href="{{ route('email-marketing.campaigns.index') }}" class="flow-panel flex flex-col p-6 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Campaigns') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_card_campaigns') }}</p>
                    <span class="mt-4 text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Open') }} →</span>
                </a>
                <a href="{{ route('email-marketing.templates.index') }}" class="flow-panel flex flex-col p-6 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Templates') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_card_templates') }}</p>
                    <span class="mt-4 text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Open') }} →</span>
                </a>
                <a href="{{ route('email-marketing.audiences.index') }}" class="flow-panel flex flex-col p-6 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Audiences') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_card_audiences') }}</p>
                    <span class="mt-4 text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Open') }} →</span>
                </a>
                <a href="{{ route('email-marketing.sequences.index') }}" class="flow-panel flex flex-col p-6 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Sequences') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_card_sequences') }}</p>
                    <span class="mt-4 text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Open') }} →</span>
                </a>
            </div>

            <p class="mt-10 text-sm text-slate-500 dark:text-slate-400">{{ __('email_marketing_smtp_note') }}</p>
        </div>
    </div>
</x-app-layout>
