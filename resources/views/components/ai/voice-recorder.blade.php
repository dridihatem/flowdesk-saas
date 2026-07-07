@props(['locale' => null])

@php
    $speechLocale = flowdesk_speech_recognition_locale($locale ?? app()->getLocale());
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }} data-nova-voice-locale="{{ $speechLocale ?? '' }}">
    {{ $slot }}
</div>
