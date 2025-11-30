<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Library Database</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <header class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold"> Team Titanium Library Database</h1>
            <a href="{{ url('/') }}" class="px-3 py-1 border rounded">Home</a>
        </header>
        <p class="text-gray-600 dark:text-gray-300 mb-6">Welcome to the library database. Manage books, loans, borrowers, and fines.</p>

        <!-- Navigation Links -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <a href="{{ url('/book-search') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 text-center">Book Search</a>
            <a href="{{ url('/book-check-in') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 text-center">Check-in Books</a>
            <a href="{{ route('borrowers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 text-center">Create Borrower</a>
            <a href="{{ route('fines.manage') }}" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-500 text-center">Manage Fines</a>
        </div>

        <!-- Date Simulator -->
        <div class="border border-gray-300 dark:border-gray-600 p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
            <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Simulate Current Date</h3>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="text-sm text-gray-600 dark:text-gray-300">Current: <span id="current-date" class="font-semibold">{{ session('simulated_date', now()->format('M d, Y')) }}</span></div>
                <input type="date" id="date-input" value="{{ session('simulated_date', now()->format('Y-m-d')) }}" class="rounded border px-2 py-1 text-sm">
                <button onclick="setSimulatedDate()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-500">Set</button>
                <button onclick="resetDate()" class="px-3 py-1 bg-gray-400 text-white rounded text-sm hover:bg-gray-500">Reset</button>
            </div>
        </div>
    </div>
    
    <script>
        function formatDateMDY(dateStr) {
            const [year, month, day] = dateStr.split("-");
            return `${month}/${day}/${year}`;
        }
        async function setSimulatedDate() {
            const dateInput = document.getElementById('date-input');
            const selectedDate = dateInput.value;
            if (!selectedDate) { alert('Please select a date'); return; }

            const res = await fetch('{{ route('date.set') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ date: selectedDate })
            });

            const data = await res.json();
            if (data.success) {
                document.getElementById('current-date').textContent = formatDateMDY(data.date);
                alert('Simulated date set to ' + data.date);
            } else {
                alert('Error setting date');
            }
        }

        async function resetDate() {
            const res = await fetch('{{ route('date.reset') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('current-date').textContent = formatDateMDY(data.date);
                document.getElementById('date-input').value = data.date;
                alert('Simulated date reset to today');
            } else {
                alert('Error resetting date');
            }
        }

        // Initialize display from server on load
        window.addEventListener('load', async () => {
            try {
                const res = await fetch('{{ route('date.current') }}');
                const data = await res.json();
                if (data && data.date) {
                    document.getElementById('date-input').value = data.date;
                    document.getElementById('current-date').textContent = formatDateMDY(data.date);
                }
            } catch (e) { /* ignore */ }
        });
    </script>
</body>
</html> 
    
