<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book Search - {{ config('app.name', 'Library Database') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white dark:bg-gray-800 rounded-lg shadow p-8">
            <header class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold">Book Search</h1>
                <a href="{{ url('/') }}" class="px-3 py-1 border rounded">Home</a>
            </header>

            <!-- Flash messages (success / error / validation) -->
            @if(session('success'))
                <div class="mt-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" action="{{ route('books.search') }}" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search query</label>
                    <input name="q" type="search" placeholder="Title, author, or ISBN" value="{{ old('q', $q ?? '') }}" class="mt-1 block w-full rounded border px-3 py-2" />
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Search</button>
                    <a href="{{ url('/') }}" class="px-4 py-2 border rounded">Cancel</a>
                </div>
            </form>

            @if(isset($results) && $results && count($results) > 0)
                <div class="mt-6">
                    <h2 class="font-medium mb-2">Results</h2>
                    <ul class="space-y-3">
                        @foreach($results as $book)
                            <li class="p-3 border rounded bg-gray-50 dark:bg-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold">{{ $book->Title }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300">Author: {{ $book->authors->pluck('Name')->join(', ') ?? '—' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300">ISBN: {{ $book->Isbn ?? '—' }}</div>

                                        @if($book->loans && $book->loans->count() > 0)
                                            <div class="mt-2">
                                                @foreach($book->loans as $loan)
                                                    @php
                                                        $dateIn = $loan->Date_in ?? null;
                                                        $checkedOut = $dateIn === null || $dateIn === '' || $dateIn === '0000-00-00';
                                                    @endphp
                                                    <div class="mt-2 p-3 rounded-lg text-sm bg-blue-50 dark:bg-blue-900/40 border border-blue-300 dark:border-blue-600">
                                                        <div>
                                                            <span class="font-medium">Status:</span>
                                                            @if($checkedOut)
                                                                <span class="text-yellow-600 dark:text-yellow-400">Checked Out</span>
                                                            @else
                                                                <span class="text-green-600 dark:text-green-400">Checked In</span>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <span class="font-medium">Card ID:</span> {{ $loan->Card_id }}
                                                        </div>
                                                        <div>
                                                            <span class="font-medium">Borrower:</span> {{ $loan->borrower->Bname ?? '—' }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        <!-- Inline checkout form: prefill ISBN, ask for Card ID -->
                                        <div class="mt-3">
                                            <form method="POST" action="{{ route('books.checkout') }}" class="flex gap-2 items-center">
                                                @csrf
                                                <input type="hidden" name="isbn" value="{{ $book->Isbn }}" />
                                                <input name="card_id" type="text" placeholder="Card ID" class="px-2 py-1 rounded border text-sm" aria-label="Card ID" />
                                                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-500">Checkout</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4">
                        {{ $results->appends(request()->query())->links() }}
                    </div>
                </div>
            @elseif(isset($q) && $q !== '')
                <div class="mt-6 text-sm text-gray-600 dark:text-gray-300">No results found for "{{ $q }}".</div>
            @else
                <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">Enter a search term to find books by title, author, or ISBN.</p>
            @endif
        </div>
    </body>
</html>
