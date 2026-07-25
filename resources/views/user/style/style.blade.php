@php
    $wayfindingStyles = [
        '01-foundation-map.css',
        '02-route-controls.css',
        '03-ai-search-voice.css',
        '04-indoor-navigation.css',
        '05-route-popup-effects.css',
        '06-path-picker-events-profile.css',
        '07-campus-theme.css',
        '08-panel-positioning.css',
        '09-map-performance.css',
        '10-campus-brand-route.css',
        '11-gps-rotation.css',
        '12-futuristic-theme.css',
    ];
@endphp

@foreach ($wayfindingStyles as $wayfindingStyle)
    <link rel="stylesheet" href="{{ asset('css/wayfinding/' . $wayfindingStyle) }}?v={{ filemtime(public_path('css/wayfinding/' . $wayfindingStyle)) }}">
@endforeach
