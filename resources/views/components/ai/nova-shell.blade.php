@props([
    'nova',
    'compact' => false,
    'enableWakeWord' => false,
])

@php
    $brand = (string) config('flowdesk.ai_assistant_brand_name', 'Nova');
    $appLocale = app()->getLocale();
    $speechLocale = flowdesk_speech_recognition_locale($appLocale);
    $novaAlpineConfig = [
        'assistantName' => $nova['assistant_name'],
        'wakeBrand' => $brand,
        'chatUrl' => $nova['chat_url'],
        'speakUrl' => route('assistant.speak'),
        'creditCost' => $nova['credit_cost'] ?? 0,
        'csrf' => csrf_token(),
        'compact' => $compact,
        'enableWakeWord' => $enableWakeWord,
        'skipWakeWord' => ! empty($flowdeskNovaVoiceNav['enabled']),
        'appLocale' => $appLocale,
        'locale' => $speechLocale,
        'labels' => [
            'idle' => __('nova_state_idle'),
            'listening' => __('nova_state_listening'),
            'thinking' => __('nova_state_thinking'),
            'responding' => __('nova_state_responding'),
            'speaking' => __('nova_state_speaking'),
            'wake' => __('nova_state_wake'),
            'wakeReply' => __('nova_voice_wake_reply', ['name' => trim(explode(' ', (string) auth()->user()?->name)[0] ?? '') ?: __('nova_voice_guest')]),
            'identityReply' => __('nova_voice_identity_reply', [
                'name' => $brand,
                'user' => trim(explode(' ', (string) auth()->user()?->name)[0] ?? '') ?: __('nova_voice_guest'),
                'company' => trim((string) (auth()->user()?->company?->name ?? '')) ?: config('app.name'),
            ]),
            'browserFallback' => __('nova_voice_browser_fallback'),
        ],
        'wakeHint' => __('nova_wake_hint', ['name' => $brand]),
        'permissionError' => __('ai_voice_permission'),
        'unsupportedError' => __('ai_voice_unsupported'),
        'localeUnsupportedError' => __('ai_voice_locale_unsupported'),
    ];
@endphp

<div x-data="novaAssistant(@js($novaAlpineConfig))" {{ $attributes }}>
    {{ $slot }}
</div>
