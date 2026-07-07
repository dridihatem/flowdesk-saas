<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
    {{ __('Global tiered commission (by workspace client count) is configured in') }}
    <a href="{{ route('settings.provider-commissions') }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Provider commission tiers') }}</a>.
    {{ __('This percentage is this provider’s fixed rate when no tier applies or as a fallback.') }}
</p>
