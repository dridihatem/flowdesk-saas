@php
    $contractHeader = $contractHeader ?? '';
    $contractTerms = $contractTerms ?? '';
    $contractTermsIsHtml = $contractTermsIsHtml ?? false;
@endphp
<div class="space-y-4">
    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 text-sm text-slate-800 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Contract summary') }}</h3>
        <div class="mt-2 whitespace-pre-wrap">{{ $contractHeader }}</div>
    </div>
    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 text-sm text-slate-800 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('provider_contract_terms_heading') }}</h3>
        @if ($contractTermsIsHtml)
            <div class="flow-partnership-terms-html mt-2 text-sm leading-relaxed text-slate-800 dark:text-slate-200">{!! $contractTerms !!}</div>
        @else
            <div class="mt-2 whitespace-pre-wrap">{{ $contractTerms }}</div>
        @endif
    </div>
</div>
