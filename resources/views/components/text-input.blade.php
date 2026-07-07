@props(['disabled' => false])

@php
    $inputClass = 'flow-input';
    $name = strtolower((string) $attributes->get('name', ''));
    $id = strtolower((string) $attributes->get('id', ''));
    $type = strtolower((string) $attributes->get('type', ''));
    $autocomplete = strtolower((string) $attributes->get('autocomplete', ''));

    if (
        $type === 'tel'
        || str_contains($name, 'phone')
        || str_contains($id, 'phone')
        || str_contains($autocomplete, 'tel')
    ) {
        $inputClass .= ' flowdesk-ltr-num tabular-nums';
    }
@endphp

<input @disabled($disabled) {{ $attributes->merge(['class' => $inputClass]) }}>
