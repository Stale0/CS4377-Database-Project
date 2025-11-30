<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateSimulatorController extends Controller
{
    /**
     * Get the current simulated date.
     */
    public function getCurrent()
    {
        $date = session('simulated_date', now()->format('Y-m-d'));
        return response()->json(['date' => $date]);
    }

    /**
     * Set a simulated date for the application.
     */
    public function setDate(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->format('Y-m-d');
        session(['simulated_date' => $date]);

        return response()->json([
            'success' => true,
            'date' => $date,
            'message' => 'Date simulated successfully'
        ]);
    }

    /**
     * Reset the simulated date to today.
     */
    public function resetDate()
    {
        session()->forget('simulated_date');
        $date = now()->format('Y-m-d');

        return response()->json([
            'success' => true,
            'date' => $date,
            'message' => 'Date reset to today'
        ]);
    }
}
