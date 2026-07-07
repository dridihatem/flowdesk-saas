<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Branding') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('These fields complement your theme and logo. They can be used in emails and customer-facing pages.') }}</p>

                <form method="POST" action="{{ route('settings.branding.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="tagline" :value="__('Tagline')" />
                        <x-text-input id="tagline" name="tagline" type="text" class="mt-1 block w-full" :value="old('tagline', $branding['tagline'] ?? '')" />
                        <x-input-error class="mt-2" :messages="$errors->get('tagline')" />
                    </div>
                    <div>
                        <x-input-label for="support_email" :value="__('Support email')" />
                        <x-text-input id="support_email" name="support_email" type="email" class="mt-1 block w-full" :value="old('support_email', $branding['support_email'] ?? '')" />
                        <x-input-error class="mt-2" :messages="$errors->get('support_email')" />
                    </div>
                    <div>
                        <x-input-label for="contact_phone" :value="__('Contact phone')" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" :value="old('contact_phone', $branding['contact_phone'] ?? '')" />
                        <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
                    </div>
                    <div>
                        <x-input-label for="website_url" :value="__('Website URL')" />
                        <x-text-input id="website_url" name="website_url" type="url" class="mt-1 block w-full" :value="old('website_url', $branding['website_url'] ?? '')" placeholder="https://" />
                        <x-input-error class="mt-2" :messages="$errors->get('website_url')" />
                    </div>

                    <x-primary-button>{{ __('Save branding') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
