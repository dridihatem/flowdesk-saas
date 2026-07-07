<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('portal_quote_requests') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('portal_new_quote_request') }}</h2>
            </div>
            <a href="{{ route('portal.quote-requests.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to quote requests') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <div class="border-b border-indigo-200/80 bg-gradient-to-br from-indigo-50/80 to-white px-6 py-5 dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-slate-900/50">
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('portal_quote_request_form_intro') }}</p>
                </div>
                <form method="POST" action="{{ route('portal.quote-requests.store') }}" class="space-y-5 p-6 sm:p-8">
                    @csrf
                    <div class="rounded-lg border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm dark:border-slate-700/80 dark:bg-slate-800/40">
                        <p class="font-medium text-slate-900 dark:text-white">{{ $client->name }}</p>
                        @if ($client->email)
                            <p class="mt-0.5 text-slate-600 dark:text-slate-400">{{ $client->email }}</p>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="subject" :value="__('portal_quote_request_subject')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject')" required placeholder="{{ __('portal_quote_request_subject_placeholder') }}" />
                        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="message" :value="__('portal_quote_request_details')" />
                        <textarea id="message" name="message" rows="6" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" placeholder="{{ __('portal_quote_request_details_placeholder') }}">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="contact_phone" :value="__('Phone (optional)')" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" :value="old('contact_phone', $client->phone)" />
                        <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                    </div>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <x-primary-button type="submit" class="inline-flex items-center gap-2 !normal-case">
                            <i class="fa-solid fa-paper-plane text-sm" aria-hidden="true"></i>
                            {{ __('portal_send_quote_request') }}
                        </x-primary-button>
                        <a href="{{ route('portal.quote-requests.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
