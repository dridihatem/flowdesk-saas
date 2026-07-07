@props([
    'bodyId' => 'body_html',
    'subjectId' => 'subject',
    'showSubjectTools' => true,
    'rows' => 16,
    'required' => false,
    'disabled' => false,
    'value' => '',
    'hintKey' => null,
    'previewEmailUrl' => null,
    'previewCampaignId' => null,
    'previewEmailTo' => null,
])

@php
    $editorCompany = auth()->user()?->company;
    $editorTheme = $editorCompany
        ? app(\App\Services\CompanyThemeService::class)->themeFor($editorCompany)
        : [];
@endphp

<div class="space-y-1">
    {{ $label }}

    <div
        x-data="emailHtmlEditorTools({{ \Illuminate\Support\Js::from([
            'bodyId' => $bodyId,
            'subjectId' => $subjectId,
            'bodyDisabled' => $disabled,
            'previewEmailUrl' => $previewEmailUrl,
            'previewCampaignId' => $previewCampaignId,
            'previewEmailTo' => $previewEmailTo ?? auth()->user()?->email ?? '',
            'companyLogoUrl' => $editorTheme['logo_url'] ?? null,
            'companyName' => $editorCompany?->name ?? '',
            'brandPrimary' => $editorTheme['primary_color'] ?? '#4f46e5',
            'linkPromptLabel' => __('email_editor_link_prompt'),
        ]) }})"
        class="space-y-2"
    >
        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100/90 p-0.5 dark:border-slate-600 dark:bg-slate-800/80">
            <button
                type="button"
                @click="setViewMode('code')"
                :class="viewMode === 'code'
                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200'"
                class="rounded-md px-3 py-1.5 text-sm font-semibold transition"
            >
                <span class="inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-pen-to-square text-[0.75rem] opacity-80" aria-hidden="true"></i>
                    {{ __('email_marketing_body_view_code') }}
                </span>
            </button>
            <button
                type="button"
                @click="setViewMode('preview')"
                :class="viewMode === 'preview'
                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200'"
                class="rounded-md px-3 py-1.5 text-sm font-semibold transition"
            >
                <span class="inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-envelope-open text-[0.75rem] opacity-90" aria-hidden="true"></i>
                    {{ __('email_marketing_body_view_preview') }}
                </span>
            </button>
        </div>

        <textarea
            id="{{ $bodyId }}"
            name="{{ $bodyId }}"
            rows="{{ $rows }}"
            @if ($required) required @endif
            @disabled($disabled)
            @input="syncPreviewFromTextarea()"
            x-show="viewMode === 'code'"
            class="mt-0 block w-full rounded-lg border border-slate-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
        >{{ $value }}</textarea>

        <div
            x-show="viewMode === 'preview'"
            x-cloak
            class="space-y-2"
        >
            <p
                class="text-xs text-slate-600 dark:text-slate-400"
                x-show="!bodyDisabled"
            >
                {{ __('email_marketing_body_preview_visual_hint') }}
            </p>
            <x-email-marketing.visual-editor-toolbar />
            <div
                class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800/50"
            >
                <iframe
                    x-ref="previewFrame"
                    @load="onPreviewFrameLoad($el)"
                    class="h-[min(70vh,560px)] w-full bg-white dark:bg-slate-900"
                    title="{{ __('email_marketing_body_preview_frame_title') }}"
                    sandbox="allow-scripts allow-same-origin"
                    x-bind:srcdoc="srcdoc"
                ></iframe>
            </div>
            <x-email-marketing.preview-email-send-panel :body-id="$bodyId" :preview-email-url="$previewEmailUrl" />
        </div>

        @if ($hintKey)
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __($hintKey) }}</p>
        @endif

        <x-input-error :messages="$errors->get($bodyId)" class="mt-1" />

        <x-email-marketing.html-editor-tools
            :body-id="$bodyId"
            :subject-id="$subjectId"
            :show-subject-tools="$showSubjectTools"
            :preview-email-url="$previewEmailUrl"
        />
    </div>
</div>
