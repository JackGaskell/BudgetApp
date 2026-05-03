<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center px-6 py-16">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ __('Student budgeting') }}</p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-900">{{ config('app.name') }}</h1>
        <p class="mt-4 text-lg text-gray-600">
            {{ __('Track income and expenses in GBP, see your month at a glance, and keep upcoming bills alongside what you have already spent.') }}
        </p>
        <div class="mt-10 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-900 bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                    {{ __('Go to dashboard') }}
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg border border-gray-900 bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                        {{ __('Log in') }}
                    </a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                        {{ __('Create an account') }}
                    </a>
                @endif
            @endauth
        </div>
        <p class="mt-12 text-sm text-gray-500">
            {{ __('Built with Laravel and Tailwind CSS.') }}
        </p>
    </div>
</body>
</html>
