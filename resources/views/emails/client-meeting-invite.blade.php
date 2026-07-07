<x-mail::message>
# {{ __('client_meeting_invite_heading', ['company' => $company->name]) }}

{{ __('client_meeting_invite_greeting', ['name' => $client->name]) }}

**{{ $event->title }}**

{{ __('Date') }}: {{ $event->starts_on->format('Y-m-d') }}
@if ($event->start_time)
{{ __('Time') }}: {{ \Illuminate\Support\Str::substr((string) $event->start_time, 0, 5) }}
@endif

@if ($event->description)
{{ $event->description }}
@endif

@if ($meetingUrl)
<x-mail::button :url="$meetingUrl">
{{ __('client_meeting_join_button') }}
</x-mail::button>

{{ __('client_meeting_link_label') }}: [{{ $meetingUrl }}]({{ $meetingUrl }})
@endif

{{ __('client_meeting_invite_footer', ['staff' => $staffName]) }}

{{ __('Thanks') }},<br>
{{ $company->name }}
</x-mail::message>
