<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('email-marketing.campaigns.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    ← {{ __('Campaigns') }}
                </a>
            </div>
            <x-flow.page-header
                :title="__('email_marketing_campaign_create')"
                :description="__('email_marketing_campaign_create_intro')"
            />
            <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_campaign_smtp_hint') }}</p>
            <div class="mt-8 flow-panel p-6 sm:p-8">
                <form method="post" action="{{ route('email-marketing.campaigns.store') }}" class="space-y-6">
                    @csrf
                    @include('email-marketing.campaigns._form', ['campaign' => null, 'aiAvailable' => $aiAvailable ?? false])
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button type="submit">{{ __('Save draft') }}</x-primary-button>
                        <a href="{{ route('email-marketing.campaigns.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
