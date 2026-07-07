@props([
    'bodyId' => 'body_html',
    'subjectId' => 'subject',
    'showSubjectTools' => true,
    'previewEmailUrl' => null,
])

@php
    $keys = ['name', 'email', 'first_name', 'last_name', 'company_name', 'audience_name', 'workspace_name', 'company_logo'];
    $tagStrings = array_map(fn (string $k) => '{{'.$k.'}}', $keys);
    $subjectTagStrings = array_map(
        fn (string $k) => '{{'.$k.'}}',
        ['name', 'first_name', 'email', 'company_name']
    );
@endphp

{{-- Merge / preview: Alpine scope comes from <x-email-marketing.body-html-field> (parent x-data). --}}
<div
    class="mt-3 space-y-3 rounded-lg border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-600/50 dark:bg-slate-800/30"
>
    <div>
        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('email_marketing_merge_tags_label') }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_merge_tags_help') }}</p>
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($tagStrings as $t)
                {{-- Single-quoted @click so @json "..." does not break the attribute --}}
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 font-mono text-[0.7rem] text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    @click='insertAtCursor(@json($t))'
                >
                    {{ $t }}
                </button>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_merge_logo_hint') }}</p>
    </div>
    @if ($showSubjectTools)
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('email_marketing_merge_tags_subject_hint') }}</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($subjectTagStrings as $t)
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-indigo-200/80 bg-indigo-50/80 px-2 py-1 font-mono text-[0.7rem] text-indigo-900 hover:bg-indigo-100/80 dark:border-indigo-500/30 dark:bg-indigo-950/30 dark:text-indigo-100 dark:hover:bg-indigo-900/40"
                    @click='insertInSubject(@json($t))'
                >
                    {{ $t }} ({{ __('Subject') }})
                </button>
            @endforeach
        </div>
    @endif
    <div>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            @click="openPreview"
        >
            <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i>
            {{ __('email_marketing_preview_html') }}
        </button>
    </div>
    <div
        x-show="previewOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closePreview"
        @keydown.escape.window="closePreview"
    >
        <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-600 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('email_marketing_preview_html') }}</p>
                <button
                    type="button"
                    class="rounded-lg px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-slate-800"
                    @click="closePreview"
                >
                    {{ __('email_marketing_preview_close') }}
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-2 dark:bg-slate-800">
                <iframe
                    class="h-[min(70vh,600px)] w-full rounded-md border border-slate-200 bg-white dark:border-slate-600"
                    title="HTML preview"
                    sandbox="allow-scripts allow-same-origin"
                    x-bind:srcdoc="srcdoc"
                ></iframe>
            </div>
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                <x-email-marketing.preview-email-send-panel :body-id="$bodyId" :preview-email-url="$previewEmailUrl" />
            </div>
        </div>
    </div>
</div>
