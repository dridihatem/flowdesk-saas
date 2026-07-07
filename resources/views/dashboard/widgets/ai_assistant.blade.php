@props(['nova' => null])

@if ($nova)
    @php($summary = array_merge($nova['summary'], ['assistant_url' => $nova['assistant_url']]))
    <x-ai.summary-widget :summary="$summary" :compact="true" />
@endif
