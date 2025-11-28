<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="container">
        <h1 class="text-2xl font-semibold">{{ config('app.name', 'Library Database') }}</h1>
        <nav>
            <a href="{{ url('/book-search') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Book Search</a>
        </nav>
        <p class="mb-4 text-gray-600 dark:text-gray-300">Welcome to the library database. Use the search to find books by title, author, or ISBN.</p>
        <div class="flex gap-2">
            <a href="{{ url('/book-search') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500">Search Books</a>
            <a href="{{ url('/book-check-in') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500">Check-in Books</a>
            <a href="{{ route('borrowers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500">Create Borrower</a>
            <a href="{{ url('/') }}" class="px-4 py-2 border rounded">Home</a>
        </div>
    </div>
</body>
</html> 
    
