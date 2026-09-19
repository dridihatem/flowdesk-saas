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
        'chatUrl' => $nova['chat_url'] ?? route('assistant.chat'),
        'agentUrl' => $nova['agent_url'] ?? route('assistant.agent.run'),
        'legacyChatUrl' => $nova['legacy_chat_url'] ?? ($nova['chat_url'] ?? route('assistant.chat')),
        'useAgent' => array_key_exists('use_agent', $nova)
            ? (bool) $nova['use_agent']
            : (bool) config('flowdesk.nova_agent_ui_enabled', true),
        'currentPage' => $nova['current_page'] ?? ($flowdeskNovaPageContext['current_page'] ?? null),
        'currentEntity' => $nova['current_entity'] ?? ($flowdeskNovaPageContext['current_entity'] ?? null),
        'companyId' => $nova['company_id'] ?? (string) (auth()->user()?->company_id ?? ''),
        'speakUrl' => route('assistant.speak'),
        'creditCost' => $nova['credit_cost'] ?? 0,
        'csrf' => csrf_token(),
        'compact' => $compact,
        'enableWakeWord' => $enableWakeWord,
        'skipWakeWord' => ! empty($flowdeskNovaVoiceNav['enabled']),
        'userId' => (string) (auth()->id() ?? ''),
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
            'wakeReplyHello' => __('nova_voice_wake_reply_hello', ['name' => trim(explode(' ', (string) auth()->user()?->name)[0] ?? '') ?: __('nova_voice_guest')]),
            'wakeReplyListening' => __('nova_voice_wake_reply_listening'),
            'identityReply' => __('nova_voice_identity_reply', [
                'name' => $brand,
                'user' => trim(explode(' ', (string) auth()->user()?->name)[0] ?? '') ?: __('nova_voice_guest'),
                'company' => trim((string) (auth()->user()?->company?->name ?? '')) ?: config('app.name'),
            ]),
            'browserFallback' => __('nova_voice_browser_fallback'),
            'confirm' => __('nova_confirm_action'),
            'cancel' => __('nova_cancel_action'),
            'activityTitle' => __('nova_activity_title'),
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
