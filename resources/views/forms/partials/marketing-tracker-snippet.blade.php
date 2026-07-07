@php
    $codeId = $codeId ?? 'flowdesk-marketing-tracker-code';
    $tok = $revealedToken ?? 'fd_live_YOUR_COMPANY_API_TOKEN';
@endphp
<div class="space-y-3">
    <p class="text-sm text-slate-600 dark:text-slate-400">
        {{ __('marketing_tracker_intro') }}
    </p>
    <pre
        id="{{ $codeId }}"
        class="overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs leading-relaxed text-cyan-100 shadow-inner ring-1 ring-white/10"
    ><code class="block whitespace-pre text-left">&lt;script
  src="{{ e($baseUrl) }}/build/marketing-tracker.js"
  defer
  data-flowdesk-tracker
  data-base-url="{{ e($baseUrl) }}"
  data-token="{{ e($tok) }}"
&gt;&lt;/script&gt;</code></pre>
    <button
        type="button"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        onclick="navigator.clipboard.writeText(document.getElementById({{ json_encode($codeId) }}).innerText)"
    >
        {{ __('Copy embed code') }}
    </button>
    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('marketing_tracker_privacy_note') }}</p>
</div>
