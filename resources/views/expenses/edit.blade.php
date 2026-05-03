<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit expense — {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="w-full bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <p class="text-lg font-bold text-gray-900">{{ config('app.name') }}</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('records.index') }}" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Records</a>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Dashboard</a>
                <p class="text-sm text-gray-600">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-sm font-semibold px-4 py-2 rounded-lg border border-gray-900">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="max-w-xl mx-auto px-6 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit expense</h1>
            <p class="text-sm text-gray-500 mt-1">Update the details below. Amounts are in pounds sterling (GBP).</p>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label for="expense_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input id="expense_name" name="name" type="text" value="{{ old('name', $expense->name) }}" required maxlength="255" class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expense_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (£)</label>
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden bg-white focus-within:ring-2 focus-within:ring-gray-900 focus-within:ring-offset-0 @error('amount') border-red-500 @enderror">
                        <span class="flex items-center px-3 bg-gray-50 text-gray-600 text-sm font-medium border-r border-gray-300 shrink-0" aria-hidden="true">£</span>
                        <input id="expense_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('amount', $expense->amount) }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                    </div>
                    @error('amount')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expense_category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="expense_category" name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white @error('category') border-red-500 @enderror">
                        @foreach ($expense_categories as $category)
                            <option value="{{ $category }}" @selected(old('category', $expense->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input id="expense_date" name="date" type="date" value="{{ old('date', $expense->date) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="bg-gray-900 hover:bg-black text-white font-semibold px-4 py-2 rounded-lg border border-gray-900">Save changes</button>
                    <a href="{{ route('records.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
