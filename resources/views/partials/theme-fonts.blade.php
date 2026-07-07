@php
    $fontHref = $flowdeskTheme['font_url'] ?? 'https://fonts.bunny.net/css?family=figtree:400,500,600|plus-jakarta-sans:500,600,700&display=swap';
@endphp
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="{{ $fontHref }}" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

@if (!empty($flowdeskTheme['use_system_dark']))
    <script>
        (function () {
            const m = window.matchMedia('(prefers-color-scheme: dark)');
            function apply() {
                document.documentElement.classList.toggle('dark', m.matches);
            }
            apply();
            m.addEventListener('change', apply);
        })();
    </script>
@endif
