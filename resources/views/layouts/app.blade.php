<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.partials.budget-head', ['pageTitle' => $pageTitle ?? null])
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    @include('layouts.partials.budget-nav')
    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:max-w-7xl">
        @include('layouts.partials.flash-messages')
        @isset($header)
            <header class="mb-8 border-b border-gray-100 pb-4">
                {{ $header }}
            </header>
        @endisset
        {{ $slot }}
    </main>
    @stack('scripts')
</body>
</html>
