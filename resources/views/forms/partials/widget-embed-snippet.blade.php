@php
    $codeId = $codeId ?? 'flowdesk-embed-code';
    $fid = $formId ?? 'YOUR_FORM_ULID';
    $tok = $revealedToken ?? 'fd_live_YOUR_COMPANY_API_TOKEN';
@endphp
<div class="space-y-3">
    <pre
        id="{{ $codeId }}"
        class="overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs leading-relaxed text-cyan-100 shadow-inner ring-1 ring-white/10"
    ><code class="block whitespace-pre text-left">&lt;div
  data-flowdesk-widget
  data-base-url="{{ e($baseUrl) }}"
  data-form-id="{{ e($fid) }}"
  data-token="{{ e($tok) }}"
&gt;&lt;/div&gt;
&lt;script src="{{ e($baseUrl) }}/build/widget.js" defer&gt;&lt;/script&gt;</code></pre>
    <button
        type="button"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        onclick="navigator.clipboard.writeText(document.getElementById({{ json_encode($codeId) }}).innerText)"
    >
        {{ __('Copy embed code') }}
    </button>
</div>
