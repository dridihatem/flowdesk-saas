<x-guest-layout hero-variant="register">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[1.65rem]">{{ __('Join as business provider') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
            {{ __('You are registering to collaborate with :company. After signup you will sign a partnership agreement; the company signs second to activate your account.', ['company' => $company->name]) }}
        </p>
    </div>

    <x-auth-session-status
        class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-200"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('partner.register.store', $slug) }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="name" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone or WhatsApp (optional)')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="phone" class="mt-2 block w-full" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="job_title" :value="__('Role / specialty (optional)')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="job_title" class="mt-2 block w-full" type="text" name="job_title" :value="old('job_title')" />
            <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" class="!text-slate-600 dark:!text-slate-400" />
            <x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button
            class="!mt-2 w-full !justify-center !rounded-xl !py-3 !text-sm !font-semibold !normal-case !tracking-normal !shadow-lg !shadow-indigo-600/25 !transition hover:!shadow-indigo-600/35 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 dark:!shadow-indigo-900/40"
        >
            {{ __('Create account and continue') }}
        </x-primary-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 underline-offset-2 hover:underline dark:text-indigo-400">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
