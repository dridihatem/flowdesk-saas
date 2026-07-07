{{-- Visual formatting toolbar (preview mode). Alpine scope: parent emailHtmlEditorTools. --}}
<div
    x-show="viewMode === 'preview' && !bodyDisabled"
    x-cloak
    class="flex flex-wrap items-center gap-1 rounded-lg border border-slate-200/90 bg-white p-2 shadow-sm dark:border-slate-600 dark:bg-slate-900/80"
>
    <div class="flex flex-wrap items-center gap-0.5 border-e border-slate-200 pe-2 dark:border-slate-600">
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="execPreviewCommand('bold')" title="{{ __('email_editor_bold') }}">
            <i class="fa-solid fa-bold text-xs" aria-hidden="true"></i>
        </button>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="execPreviewCommand('italic')" title="{{ __('email_editor_italic') }}">
            <i class="fa-solid fa-italic text-xs" aria-hidden="true"></i>
        </button>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="execPreviewCommand('underline')" title="{{ __('email_editor_underline') }}">
            <i class="fa-solid fa-underline text-xs" aria-hidden="true"></i>
        </button>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="execPreviewCommand('removeFormat')" title="{{ __('email_editor_clear_format') }}">
            <i class="fa-solid fa-eraser text-xs" aria-hidden="true"></i>
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-1.5 border-e border-slate-200 px-2 dark:border-slate-600">
        <label class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            <span>{{ __('email_editor_text_color') }}</span>
            <input type="color" x-model="toolTextColor" @input="execPreviewCommand('foreColor', toolTextColor)" class="h-7 w-8 cursor-pointer rounded border border-slate-200 bg-white p-0.5 dark:border-slate-600" />
        </label>
        <label class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            <span>{{ __('email_editor_bg_color') }}</span>
            <input type="color" x-model="toolBgColor" @input="execPreviewCommand('hiliteColor', toolBgColor)" class="h-7 w-8 cursor-pointer rounded border border-slate-200 bg-white p-0.5 dark:border-slate-600" />
        </label>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="insertPreviewLink()" title="{{ __('email_editor_insert_link') }}">
            <i class="fa-solid fa-link text-xs" aria-hidden="true"></i>
        </button>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="execPreviewCommand('unlink')" title="{{ __('email_editor_remove_link') }}">
            <i class="fa-solid fa-link-slash text-xs" aria-hidden="true"></i>
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-0.5 px-2">
        <span class="me-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('email_editor_logo') }}</span>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="insertLogoBlock('left')" title="{{ __('email_editor_logo_left') }}">
            <i class="fa-solid fa-align-left text-xs" aria-hidden="true"></i>
        </button>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="insertLogoBlock('center')" title="{{ __('email_editor_logo_center') }}">
            <i class="fa-solid fa-align-center text-xs" aria-hidden="true"></i>
        </button>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent text-slate-600 transition hover:border-slate-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" @click="insertLogoBlock('right')" title="{{ __('email_editor_logo_right') }}">
            <i class="fa-solid fa-align-right text-xs" aria-hidden="true"></i>
        </button>
    </div>
</div>
