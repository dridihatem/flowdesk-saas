<x-app-layout>
    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('email-marketing.audiences.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    ← {{ __('Audiences') }}
                </a>
            </div>
            <x-flow.page-header
                :title="__('email_marketing_audience_create')"
                :description="__('email_marketing_audience_create_intro')"
            />
            <div class="mt-8 flow-panel p-6 sm:p-8">
                <form method="post" action="{{ route('email-marketing.audiences.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                    <div x-data="flowdeskAudienceContacts({
                        emails: @js($clientEmails),
                        addedMessage: @js(__('email_marketing_audience_sync_clients_added')),
                        noneAddedMessage: @js(__('email_marketing_audience_sync_clients_none_added')),
                    })">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <x-input-label for="contacts_input" :value="__('email_marketing_audience_contacts')" />
                            <button
                                type="button"
                                @click="syncClients()"
                                @if (empty($clientEmails)) disabled @endif
                                class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
                            >
                                <i class="fa-solid fa-rotate text-[10px]" aria-hidden="true"></i>
                                {{ __('email_marketing_audience_sync_clients') }}
                            </button>
                        </div>
                        <textarea
                            id="contacts_input"
                            name="contacts_input"
                            rows="10"
                            class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                            placeholder="one@example.com&#10;two@example.com"
                        >{{ old('contacts_input') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_audience_contacts_help') }}</p>
                        @if (empty($clientEmails))
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('email_marketing_audience_sync_clients_none') }}</p>
                        @endif
                        <p x-cloak x-show="added !== null" class="mt-1 text-xs font-medium text-emerald-600 dark:text-emerald-400" x-text="addedMessage()"></p>
                        <x-input-error :messages="$errors->get('contacts_input')" class="mt-2" />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                        <a href="{{ route('email-marketing.audiences.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
