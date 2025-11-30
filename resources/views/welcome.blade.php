<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Welcome Page</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .date-simulator {
            background-color: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
        }
        .date-simulator h3 {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }
        .date-display {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .date-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .date-controls input {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .date-controls button {
            padding: 0.5rem 1rem;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .date-controls button:hover {
            background-color: #2563eb;
        }
        .date-controls button.reset {
            background-color: #6b7280;
        }
        .date-controls button.reset:hover {
            background-color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-2xl font-semibold">{{ config('app.name', 'Library Database') }}</h1>
        
        <!-- Date Simulator -->
        <div class="date-simulator">
            <h3>Simulate Current Date</h3>
            <div class="date-display" id="current-date">{{ now()->format('F j, Y') }}</div>
            <div class="date-controls">
                <input type="date" id="date-input" value="{{ now()->format('Y-m-d') }}">
                <button onclick="setSimulatedDate()">Set Date</button>
                <button class="reset" onclick="resetDate()">Reset to Today</button>
            </div>
        </div>
        <nav>
            <a href="{{ url('/book-search') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Book Search</a>
        </nav>
        <p class="mb-4 text-gray-600 dark:text-gray-300">Welcome to the library database. Use the search to find books by title, author, or ISBN.</p>
        <div class="flex gap-2">
            <a href="{{ route('books.search') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500">Search Books</a>
            <a href="{{ route('books.check-in') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500">Check-in Books</a>
            <a href="{{ route('borrowers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500">Create Borrower</a>
            <a href="{{ route('fines.manage') }}" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">Manage Fines</a>
        </div>
    </div>

    <script>
        function setSimulatedDate() {
            const dateInput = document.getElementById('date-input');
            const selectedDate = dateInput.value;
            
            if (!selectedDate) {
                alert('Please select a date');
                return;
            }

            fetch('{{ route("date.set") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ date: selectedDate })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDateDisplay(data.date);
                    alert('Simulated date updated successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating date');
            });
        }

        function resetDate() {
            fetch('{{ route("date.reset") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDateDisplay(data.date);
                    document.getElementById('date-input').value = data.date;
                    alert('Date reset to today');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error resetting date');
            });
        }

        function updateDateDisplay(dateStr) {
            const date = new Date(dateStr);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            const formatted = date.toLocaleDateString('en-US', options);
            document.getElementById('current-date').textContent = formatted;
        }

        // Load current simulated date on page load
        window.addEventListener('load', function() {
            fetch('{{ route("date.current") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.date) {
                        document.getElementById('date-input').value = data.date;
                        updateDateDisplay(data.date);
                    }
                })
                .catch(error => console.error('Error loading date:', error));
        });
    </script>
</body>
</html> 
    
