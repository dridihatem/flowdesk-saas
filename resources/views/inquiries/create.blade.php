<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('New inquiry') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/50 p-5 dark:border-indigo-900/50 dark:bg-indigo-950/25">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Capture leads from your website') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('inquiry_marketing_blurb') }}</p>
                <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700 dark:text-slate-300">
                    <li>
                        <a href="{{ route('marketing.hub') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Marketing hub') }}</a>
                        — {{ __('Traffic, SEO ideas, and sitewide tracker script.') }}
                    </li>
                    <li>
                        <a href="{{ route('forms.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Lead forms') }}</a>
                        — {{ __('inquiry_marketing_forms_line') }}
                    </li>
                    <li>
                        <a href="{{ route('settings.widget-embed') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Widget embed') }}</a>
                        — {{ __('API token and copy-paste snippets.') }}
                    </li>
                </ul>
            </div>
            <div class="flow-panel p-8">
                <form method="POST" action="{{ route('inquiries.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="subject" :value="__('Subject')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" value="{{ old('subject') }}" required />
                        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="message" :value="__('Message')" />
                        <textarea id="message" name="message" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="contact_name" :value="__('Contact name')" />
                            <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full" value="{{ old('contact_name') }}" />
                        </div>
                        <div>
                            <x-input-label for="contact_email" :value="__('Contact email')" />
                            <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full" value="{{ old('contact_email') }}" />
                            <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="contact_phone" :value="__('Contact phone')" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" value="{{ old('contact_phone') }}" />
                    </div>
                    <div>
                        <x-input-label for="source" :value="__('Channel / source (optional)')" />
                        <x-text-input id="source" name="source" type="text" class="mt-1 block w-full" value="{{ old('source') }}" placeholder="{{ __('e.g. phone, walk-in, partner name') }}" />
                    </div>
                    <div class="flex gap-3">
                        <x-primary-button type="submit">{{ __('Record inquiry') }}</x-primary-button>
                        <a href="{{ route('inquiries.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
