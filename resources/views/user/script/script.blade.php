@php
    $wayfindingScripts = [
        '01-map-core.js',
        '02-map-data-ui.js',
        '03-outdoor-routing.js',
        '04-indoor-routing.js',
        '05-map-rendering.js',
        '06-search-voice.js',
        '07-campus-events-data.js',
        '08-assistant-ui.js',
        '09-building-indoor-ui.js',
        '10-responsive-performance.js',
        '11-map-performance.js',
        '12-gps-tracking.js',
    ];
@endphp

@foreach ($wayfindingScripts as $wayfindingScript)
    <script src="{{ asset('js/wayfinding/' . $wayfindingScript) }}?v={{ filemtime(public_path('js/wayfinding/' . $wayfindingScript)) }}"></script>
@endforeach
