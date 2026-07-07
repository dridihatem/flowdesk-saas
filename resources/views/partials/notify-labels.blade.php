@php
    $flowdeskNotifyLabels = [
        'serviceUnavailable' => __('nova_voice_service_unavailable'),
        'requestFailed' => __('nova_voice_request_failed'),
        'creditsLimit' => __('nova_voice_credits_limit_short'),
        'sessionExpired' => __('Page expired, please refresh and try again.'),
    ];
@endphp
<script>
    window.flowdeskNotifyLabels = @json($flowdeskNotifyLabels);
</script>
