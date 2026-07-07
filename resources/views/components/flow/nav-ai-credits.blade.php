@props(['compact' => false])
@php
    $c = $flowdeskNavAiCredits ?? ['show' => false];
    $user = auth()->user();
    $creditsHref = ($user && $user->hasRole('company_admin'))
        ? route('billing.index')
        : route('assistant.index');
    $creditsTitle = ($user && $user->hasRole('company_admin'))
        ? __('AI credits remaining this month — open Billing')
        : __('AI credits remaining this month — open AI assistant');
    if (! empty($c['show']) && ! empty($c['unlimited'])) {
        $creditsAria = __('Unlimited AI credits this month');
    } elseif (! empty($c['show'])) {
        $creditsAria = __('nav_ai_credits_aria_limited', [
            'remaining' => number_format((int) ($c['remaining'] ?? 0)),
            'limit' => number_format((int) ($c['limit'] ?? 0)),
        ]);
    } else {
        $creditsAria = '';
    }
@endphp
@if (! empty($c['show']))
    <a
        href="{{ $creditsHref }}"
        {{ $attributes->class([
            'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-violet-200/90 bg-violet-50/90 px-2.5 py-1.5 text-xs font-semibold text-violet-900 shadow-sm hover:bg-violet-100/90 dark:border-violet-800/60 dark:bg-violet-950/50 dark:text-violet-100 dark:hover:bg-violet-900/40',
            'shrink-0' => $compact,
        ]) }}
        title="{{ $creditsTitle }}"
        @if ($creditsAria !== '')
            aria-label="{{ $creditsAria }}"
        @endif
    >
        <i class="fa-solid fa-wand-magic-sparkles text-[0.7rem] opacity-80" aria-hidden="true"></i>
        @if (! empty($c['unlimited']))
            <span class="tabular-nums tracking-tight">{{ __('AI') }} ∞</span>
        @else
            <span class="tabular-nums tracking-tight">{{ number_format((int) ($c['remaining'] ?? 0)) }}</span>
            @unless ($compact)
                <span class="hidden font-normal text-violet-800/90 sm:inline dark:text-violet-200/90">{{ __('nav_ai_credits_left') }}</span>
                <span class="hidden font-normal text-violet-700/80 sm:inline dark:text-violet-300/80">/ {{ number_format((int) ($c['limit'] ?? 0)) }}</span>
            @endunless
        @endif
    </a>
@endif
