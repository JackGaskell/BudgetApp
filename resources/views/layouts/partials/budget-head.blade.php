@php
    $sectionTitle = trim($__env->yieldContent('title'));
    $resolvedTitle = ($pageTitle ?? null) ?: ($sectionTitle !== '' ? $sectionTitle : null) ?: __('Dashboard');
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $resolvedTitle }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
