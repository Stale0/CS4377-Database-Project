<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Manage Fines - {{ config('app.name', 'Library Database') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex items-center justify-center p-6">
        <div class="max-w-4xl w-full bg-white dark:bg-gray-800 rounded-lg shadow p-8">
            <header class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold">Manage Fines</h1>
                <a href="{{ url('/') }}" class="px-3 py-1 border rounded">Home</a>
            </header>

            @if(session('success'))
                <div class="mt-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            <!-- Filter toggle -->
            <div class="mt-4 mb-6">
                <a href="{{ route('fines.manage') }}?show_paid={{ $show_paid ? '0' : '1' }}" class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    @if($show_paid)
                        Hide Paid Fines
                    @else
                        Show Paid Fines
                    @endif
                </a>
            </div>

            @if(count($borrowers) > 0)
                <div class="space-y-6">
                    @foreach($borrowers as $borrower)
                        <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                            <!-- Borrower header with total -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ $borrower['Bname'] }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Card ID: {{ $borrower['Card_id'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Total Fine:</p>
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">${{ number_format($borrower['total'], 2) }}</p>
                                </div>
                            </div>

                            <!-- Individual fines table -->
                            <table class="w-full text-sm mb-4">
                                <thead class="border-b">
                                    <tr>
                                        <th class="text-left p-2">ISBN</th>
                                        <th class="text-left p-2">Due Date</th>
                                        <th class="text-left p-2">Returned</th>
                                        <th class="text-right p-2">Fine Amount</th>
                                        <th class="text-center p-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($borrower['fines'] as $fine)
                                        <tr class="border-b dark:border-gray-600">
                                            <td class="p-2">{{ $fine->Isbn }}</td>
                                            <td class="p-2">{{ $fine->Due_date }}</td>
                                            <td class="p-2">
                                                @if($fine->Date_in && $fine->Date_in !== '0000-00-00')
                                                    {{ $fine->Date_in }}
                                                @else
                                                    <span class="text-red-600 dark:text-red-400">Still Out</span>
                                                @endif
                                            </td>
                                            <td class="p-2 text-right font-mono">${{ number_format($fine->Fine_amt, 2) }}</td>
                                            <td class="p-2 text-center">
                                                @if($fine->Paid)
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 rounded text-xs">Paid</span>
                                                @else
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 rounded text-xs">Unpaid</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Pay button (only if has unpaid fines and all books returned) -->
                            @php
                                $hasUnpaid = collect($borrower['fines'])->where('Paid', false)->count() > 0;
                                $allReturned = collect($borrower['fines'])->every(fn($f) => $f->Date_in && $f->Date_in !== '0000-00-00');
                            @endphp
                            @if($hasUnpaid && $allReturned)
                                <form method="POST" action="{{ route('fines.pay') }}" class="mt-4">
                                    @csrf
                                    <input type="hidden" name="Card_id" value="{{ $borrower['Card_id'] }}" />
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500">
                                        Pay All Fines (${{ number_format($borrower['total'], 2) }})
                                    </button>
                                </form>
                            @elseif(!$allReturned && $hasUnpaid)
                                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-700 dark:text-yellow-300 rounded text-sm">
                                    Cannot pay fines: some books are still checked out. Return all books first.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300 rounded">
                    @if($show_paid)
                        No fines to display.
                    @else
                        No unpaid fines at this time!
                    @endif
                </div>
            @endif

            <div class="mt-8">
                <a href="{{ url('/') }}" class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">Back to Home</a>
            </div>
        </div>
    </body>
</html>
