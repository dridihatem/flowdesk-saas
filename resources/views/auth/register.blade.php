@php
    $initialStep = 1;
    if ($errors->has('name') || $errors->has('email') || $errors->has('password') || $errors->has('password_confirmation') || $errors->has('_captcha_answer')) {
        $initialStep = 3;
    } elseif ($errors->has('company_name') || $errors->has('country') || $errors->has('vat_percent') || $errors->has('phone') || $errors->has('phone_country_iso') || $errors->has('phone_national_number')) {
        $initialStep = 1;
    } elseif ($errors->isNotEmpty()) {
        $initialStep = 2;
    }
@endphp

<x-guest-layout hero-variant="register">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[1.65rem]">{{ __('Create workspace') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Register subtitle') }}</p>
    </div>

    <div
        class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30"
        x-data="{
            step: {{ $initialStep }},
            maxStep: 3,
            steps: [1, 2, 3],
            goNext() {
                if (this.step === 1) {
                    const el = document.getElementById('company_name');
                    if (! el.checkValidity()) {
                        el.reportValidity();
                        return;
                    }
                }
                if (this.step === 2) {
                    const email = document.getElementById('contact_email');
                    if (email && email.value.trim() !== '' && ! email.checkValidity()) {
                        email.reportValidity();
                        return;
                    }
                }
                if (this.step < this.maxStep) {
                    this.step++;
                }
            },
            goBack() {
                if (this.step > 1) {
                    this.step--;
                }
            },
        }"
    >
        <p class="mb-4 text-center text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Progress') }}</p>
        <div class="flex justify-center gap-2 sm:gap-3">
            <template x-for="i in steps" :key="i">
                <div class="flex flex-1 flex-col items-center gap-2 sm:max-w-[6.5rem]">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition-all duration-300 sm:h-10 sm:w-10 sm:text-sm"
                        :class="step >= i ? 'bg-gradient-to-br from-indigo-600 to-cyan-600 text-white shadow-md shadow-indigo-600/30' : 'border-2 border-slate-200 bg-white text-slate-400 dark:border-slate-600 dark:bg-slate-900'"
                        x-text="i"
                    ></div>
                    <span
                        class="text-center text-[10px] font-medium leading-tight text-slate-500 dark:text-slate-400 sm:text-xs"
                        x-text="i === 1 ? @js(__('Company')) : (i === 2 ? @js(__('Details')) : @js(__('Account')))"
                    ></span>
                </div>
            </template>
        </div>
        <div class="mt-4 flex gap-1.5">
            <template x-for="i in steps" :key="'bar-' + i">
                <div
                    class="h-1 flex-1 rounded-full transition-all duration-300"
                    :class="step >= i ? 'bg-gradient-to-r from-indigo-500 to-cyan-500' : 'bg-slate-200 dark:bg-slate-600'"
                ></div>
            </template>
        </div>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <div x-show="step === 1" x-transition class="space-y-5">
                <div class="rounded-xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50 to-white p-4 text-sm dark:border-indigo-500/25 dark:from-indigo-950/50 dark:to-slate-900/50">
                    <p class="font-semibold text-indigo-950 dark:text-indigo-100">{{ __('Your company workspace') }}</p>
                    <p class="mt-1.5 leading-relaxed text-indigo-900/85 dark:text-indigo-200/90">{{ __('This organization is the account that owns clients, projects, pricing, quotes (proposals), and invoices. You can invite your team after signing up.') }}</p>
                </div>

                <div>
                    <x-input-label for="company_name" :value="__('Company name')" class="!text-slate-600 dark:!text-slate-400" />
                    <x-text-input id="company_name" class="mt-2 block w-full" type="text" name="company_name" :value="old('company_name')" required autocomplete="organization" />
                    <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="country" :value="__('Country')" class="!text-slate-600 dark:!text-slate-400" />
                    @include('auth.partials.country-select', ['countries' => $countries, 'id' => 'country', 'name' => 'country', 'value' => old('country')])
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Country list hint') }}</p>
                    <x-input-error :messages="$errors->get('country')" class="mt-2" />
                </div>

                @include('auth.partials.vat-percent-field')

                <div class="rounded-xl border border-slate-200/90 bg-white/70 p-4 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-600/50 dark:bg-slate-900/40 dark:ring-white/[0.04]">
                    <p class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Phone (optional)') }}</p>
                    <p class="mb-4 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Phone optional hint') }}</p>
                    @include('auth.partials.phone-fields', ['countries' => $countries, 'dialCodes' => $dialCodes])
                </div>
            </div>

            <div x-show="step === 2" x-transition class="space-y-5" x-cloak>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Optional details — you can finish these later in settings.') }}</p>
                @include('auth.partials.company-profile-info')
            </div>

            <div x-show="step === 3" x-transition class="space-y-5" x-cloak>
                <div>
                    <x-input-label for="name" :value="__('Your name')" class="!text-slate-600 dark:!text-slate-400" />
                    <x-text-input id="name" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" class="!text-slate-600 dark:!text-slate-400" />
                    <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" class="!text-slate-600 dark:!text-slate-400" />
                    <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="!text-slate-600 dark:!text-slate-400" />
                    <x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                @include('auth.partials.math-captcha', ['captcha' => $captcha, 'inputId' => 'register_captcha', 'class' => 'mt-4'])
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/90 pt-6 dark:border-slate-600/50">
                <button
                    type="button"
                    class="text-sm font-semibold text-slate-600 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    x-show="step > 1"
                    x-on:click="goBack()"
                    x-cloak
                >
                    ← {{ __('Back') }}
                </button>
                <span class="invisible w-px" x-show="step === 1" aria-hidden="true"></span>

                <div class="ms-auto flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:from-indigo-500 hover:to-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                        x-show="step < maxStep"
                        x-on:click="goNext()"
                    >
                        {{ __('Continue') }} →
                    </button>

                    <span x-show="step === maxStep" x-cloak>
                        <x-primary-button
                            type="submit"
                            class="!rounded-xl !py-2.5 !px-6 !text-sm !font-semibold !normal-case !tracking-normal bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 !shadow-lg !shadow-indigo-600/25"
                        >{{ __('Register') }}</x-primary-button>
                    </span>
                </div>
            </div>

            <p class="pt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                {{ __('Already registered?') }}
                <a class="font-semibold text-indigo-600 underline decoration-indigo-400/40 underline-offset-2 transition hover:text-indigo-500 dark:text-indigo-400" href="{{ route('login') }}">{{ __('Log in') }}</a>
            </p>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        {{ __('Use social sign-in on the login page if you prefer Google, GitHub, or LinkedIn.') }}
    </p>

    @include('auth.partials.country-registration-data')
</x-guest-layout>
