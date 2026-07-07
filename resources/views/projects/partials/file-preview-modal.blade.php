<div
    x-data="{ open: false, src: '', title: '' }"
    @flowdesk-file-preview.window="open = true; src = $event.detail.src; title = $event.detail.title || ''"
    @keydown.escape.window="open = false"
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-8"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="open = false"></div>
        <div class="relative z-10 max-h-[92vh] max-w-[min(96vw,1200px)] overflow-auto rounded-2xl border border-white/10 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <p class="mb-3 truncate text-center text-sm font-medium text-slate-800 dark:text-slate-100" x-text="title"></p>
            <img :src="src" :alt="title" class="mx-auto block max-h-[78vh] max-w-full object-contain" />
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <x-secondary-button type="button" class="!text-xs !normal-case" @click="open = false">{{ __('Close preview') }}</x-secondary-button>
                <a
                    :href="src"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                >{{ __('Open full size') }}</a>
            </div>
        </div>
    </div>
</div>
