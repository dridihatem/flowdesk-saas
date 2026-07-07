@props([
    'captcha' => [],
    'inputId' => 'auth_captcha',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-600/60 dark:bg-slate-800/40']) }}>
    <x-input-label for="{{ $inputId }}" :value="__('Math CAPTCHA')" class="!text-slate-600 dark:!text-slate-400" />
    <p class="mt-1.5 text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-200">{{ $captcha['question'] ?? '' }}</p>
    <input type="hidden" name="_captcha_token" value="{{ $captcha['token'] ?? '' }}" />
    <x-text-input
        id="{{ $inputId }}"
        name="_captcha_answer"
        type="number"
        inputmode="numeric"
        class="mt-2 block w-full max-w-[8rem]"
        :value="old('_captcha_answer')"
        required
        autocomplete="off"
    />
    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Auth captcha hint') }}</p>
    <x-input-error :messages="$errors->get('_captcha_answer')" class="mt-2" />
</div>
