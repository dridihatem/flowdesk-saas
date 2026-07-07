@props([
    'conversations' => collect(),
])

@if ($conversations->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-900/5']) }}>
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-600">{{ __('nova_history_title') }}</h3>
        <ul class="mt-3 max-h-48 space-y-2 overflow-y-auto text-sm">
            @foreach ($conversations as $conversation)
                <li>
                    <button
                        type="button"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-slate-900 transition hover:border-sky-300 hover:bg-sky-50"
                        x-on:click="loadConversation(@js($conversation->id))"
                    >
                        <p class="truncate font-medium text-slate-900">{{ $conversation->title ?: __('nova_untitled_chat') }}</p>
                        <p class="mt-0.5 text-[10px] text-slate-500">{{ $conversation->updated_at?->diffForHumans() }}</p>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
@endif
