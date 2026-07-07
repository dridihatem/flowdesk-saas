@props(['targetId', 'submitButtonId' => null, 'autoStart' => null])

@php
    $appLocale = app()->getLocale();
    $autoStartVoice = $autoStart ?? request()->boolean('nova_ai');
    $voiceCfg = [
        'targetId' => $targetId,
        'submitButtonId' => $submitButtonId,
        'appLocale' => $appLocale,
        'locale' => flowdesk_speech_recognition_locale($appLocale),
        'autoStart' => $autoStartVoice,
        'labels' => [
            'unsupported' => __('ai_voice_unsupported'),
            'localeUnsupported' => __('ai_voice_locale_unsupported'),
            'permission' => __('ai_voice_permission'),
            'listening' => __('ai_voice_listening'),
            'start' => __('ai_voice_start'),
            'stop' => __('ai_voice_stop'),
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    {{ $slot }}

    <div x-data="aiVoiceField({{ \Illuminate\Support\Js::from($voiceCfg) }})" class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-sm transition disabled:opacity-50"
            :class="listening
                ? 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-500/50 dark:bg-rose-950/50 dark:text-rose-300'
                : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-200 hover:text-indigo-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-indigo-500/40 dark:hover:text-indigo-300'"
            x-on:click="toggle()"
            x-bind:disabled="!supported"
        >
            <i class="fa-solid text-[11px]" :class="listening ? 'fa-stop' : 'fa-microphone'" aria-hidden="true"></i>
            <span x-text="listening ? @js(__('ai_voice_stop')) : @js(__('ai_voice_start'))"></span>
        </button>
        <span x-show="listening" x-cloak class="text-xs text-indigo-600 dark:text-indigo-400">
            {{ __('ai_voice_listening') }}
            <span x-show="interim" x-text="' — ' + interim" class="italic text-slate-500 dark:text-slate-400"></span>
        </span>
        <span x-show="error" x-text="error" x-cloak class="text-xs text-rose-600 dark:text-rose-400"></span>
        <span x-show="!supported" x-cloak class="text-xs text-slate-500 dark:text-slate-400">{{ __('ai_voice_unsupported') }}</span>
    </div>
    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ai_voice_hint') }}</p>
    <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('ai_voice_dictation_hint') }}</p>
</div>
