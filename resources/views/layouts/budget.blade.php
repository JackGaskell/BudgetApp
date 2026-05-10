<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.partials.budget-head', ['pageTitle' => null])
<body
    class="min-h-screen bg-gray-50 text-gray-900 antialiased"
    {{-- Single-quoted value so JSON string literals (e.g. "expense") do not break the attribute. --}}
    x-data='budgetShell(@json(['addOpen' => $add_modal_open, 'addTab' => $add_modal_tab]))'
>
    @include('layouts.partials.budget-nav')
    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        @include('layouts.partials.flash-messages')
        @yield('content')
    </main>
    @include('layouts.partials.add-transaction-modal')
    @stack('scripts')
</body>
</html>
