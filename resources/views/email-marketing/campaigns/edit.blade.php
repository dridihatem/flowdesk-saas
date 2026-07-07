<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <a href="{{ route('email-marketing.campaigns.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    ← {{ __('Campaigns') }}
                </a>
                <a href="{{ route('email-marketing.campaigns.show', $campaign) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-500 dark:text-slate-400 dark:hover:text-slate-300">
                    {{ __('email_marketing_campaign_results') }} →
                </a>
            </div>
            <x-flow.page-header
                :title="__('email_marketing_campaign_edit')"
                :description="__('email_marketing_campaign_edit_intro')"
            />

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
                    {{ session('status') }}
                </div>
            @endif

            @if ($campaign->status === 'sent')
                <div class="mt-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ __('email_marketing_campaign_sent_banner', ['time' => $campaign->sent_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—']) }}
                </div>
            @endif

            <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_campaign_smtp_hint') }}</p>

            <div class="mt-8 flow-panel p-6 sm:p-8">
                <form method="post" action="{{ route('email-marketing.campaigns.update', $campaign) }}" class="space-y-6">
                    @csrf
                    @method('put')
                    @include('email-marketing.campaigns._form', ['campaign' => $campaign, 'aiAvailable' => $aiAvailable ?? false])
                    @if ($campaign->status !== 'sent')
                        <div class="flex flex-wrap gap-3">
                            <x-primary-button type="submit">{{ __('Save draft') }}</x-primary-button>
                            <a href="{{ route('email-marketing.campaigns.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    @endif
                </form>

                <div class="mt-10 border-t border-slate-200 pt-8 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('email_marketing_sample_heading') }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_sample_help') }}</p>
                    <form method="post" action="{{ route('email-marketing.campaigns.sample', $campaign) }}" class="mt-4 flex max-w-2xl flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="min-w-0 flex-1">
                            <x-input-label for="sample_to" :value="__('email_marketing_sample_to')" />
                            <x-text-input id="sample_to" name="sample_to" type="email" class="mt-1 block w-full" :value="old('sample_to')" required placeholder="client@example.com" />
                        </div>
                        <x-secondary-button type="submit">{{ __('email_marketing_sample_send') }}</x-secondary-button>
                    </form>
                    <x-input-error :messages="$errors->get('sample_to')" class="mt-2" />
                </div>

                @if ($campaign->status === 'draft')
                    <div class="mt-10 border-t border-slate-200 pt-8 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('email_marketing_campaign_send_section') }}</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_campaign_send_help') }}</p>
                        <form method="post" action="{{ route('email-marketing.campaigns.send', $campaign) }}" class="mt-4" onsubmit="return confirm(@json(__('email_marketing_campaign_send_confirm')))">
                            @csrf
                            <x-primary-button type="submit" class="bg-emerald-600 hover:bg-emerald-500">{{ __('email_marketing_campaign_send_now') }}</x-primary-button>
                        </form>
                    </div>
                @endif

                @if ($campaign->status === 'draft')
                    <div class="mt-8 border-t border-slate-200 pt-8 dark:border-slate-700">
                        <form method="post" action="{{ route('email-marketing.campaigns.destroy', $campaign) }}" onsubmit="return confirm(@json(__('email_marketing_campaign_delete_confirm')))">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">{{ __('Delete draft') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
