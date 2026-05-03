<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.partials.budget-head', ['pageTitle' => $pageTitle ?? null])
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ url('/') }}" class="text-lg font-bold text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 rounded-md">
                {{ config('app.name') }}
            </a>
            @if (Route::has('login'))
                <div class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-gray-700 hover:text-gray-900">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-gray-900">{{ __('Log in') }}</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg border border-gray-900 bg-gray-900 px-3 py-2 font-semibold text-white hover:bg-black">{{ __('Register') }}</a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </header>

    <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md flex-col justify-center px-4 py-10 sm:px-6">
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </div>
        <p class="mt-6 text-center text-xs text-gray-500">
            <a href="{{ url('/') }}" class="font-medium text-gray-600 hover:text-gray-900">{{ __('Back to home') }}</a>
        </p>
    </div>
    @stack('scripts')
</body>
</html>
