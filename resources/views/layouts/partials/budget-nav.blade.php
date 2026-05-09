@php
    $navLink = 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2';
    $navActive = 'bg-gray-100 text-gray-900';
    $navIdle = 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
@endphp
<nav class="border-b border-gray-200 bg-white" x-data="{ mobileOpen: false }">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <a href="{{ route('dashboard') }}" class="truncate text-lg font-bold text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 rounded-md">
                {{ config('app.name') }}
            </a>
            <div class="hidden items-center gap-1 md:flex">
                <a href="{{ route('dashboard') }}" class="{{ $navLink }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">{{ __('Dashboard') }}</a>
                <a href="{{ route('records.index', \App\Support\ViewMonth::queryParams(now()->year, now()->month)) }}" class="{{ $navLink }} {{ request()->routeIs('records.index') ? $navActive : $navIdle }}">{{ __('Records') }}</a>
                <a href="{{ route('recurring.index') }}" class="{{ $navLink }} {{ request()->routeIs('recurring.index') ? $navActive : $navIdle }}">{{ __('Recurring') }}</a>
                <a href="{{ route('profile.edit') }}" class="{{ $navLink }} {{ request()->routeIs('profile.edit') ? $navActive : $navIdle }}">{{ __('Profile') }}</a>
            </div>
        </div>

        <div class="hidden items-center gap-3 md:flex">
            <span class="max-w-[10rem] truncate text-sm text-gray-500" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>

        <div class="flex items-center gap-2 md:hidden">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 p-2 text-gray-600 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                @click="mobileOpen = ! mobileOpen"
                :aria-expanded="mobileOpen.toString()"
                aria-controls="budget-mobile-nav"
            >
                <span class="sr-only">{{ __('Open menu') }}</span>
                <svg x-show="! mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div id="budget-mobile-nav" x-show="mobileOpen" x-cloak x-transition class="border-t border-gray-100 md:hidden">
        <div class="mx-auto max-w-6xl space-y-1 px-4 py-3 sm:px-6">
            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('Dashboard') }}</a>
            <a href="{{ route('records.index', \App\Support\ViewMonth::queryParams(now()->year, now()->month)) }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('records.index') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('Records') }}</a>
            <a href="{{ route('recurring.index') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('recurring.index') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('Recurring') }}</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('profile.edit') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('Profile') }}</a>
            <div class="border-t border-gray-100 pt-3 text-sm text-gray-500">{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}" class="pt-2">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-gray-900 bg-gray-900 py-2 text-sm font-semibold text-white hover:bg-black">{{ __('Log out') }}</button>
            </form>
        </div>
    </div>
</nav>
