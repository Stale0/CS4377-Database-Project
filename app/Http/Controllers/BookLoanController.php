<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\BookLoan;
use App\Models\Borrower;
use App\Models\Fine;
use Carbon\Carbon;

class BookLoanController extends Controller
{
    /**
     * Checkout a book by ISBN for a borrower (Card_id).
     * Accepts inputs `isbn` or `Isbn`, and `card_id` or `Card_id`.
     */
    public function checkout(Request $request)
    {
        $isbn = $request->input('Isbn') ?? $request->input('isbn');
        $cardId = $request->input('Card_id') ?? $request->input('card_id');

        $validator = Validator::make(compact('isbn','cardId'), [
            'isbn' => 'required',
            'cardId' => 'required'
        ], [
            'isbn.required' => 'ISBN is required.',
            'cardId.required' => 'Borrower Card ID is required.'
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        // Normalize
        $isbn = trim((string)$isbn);
        $cardId = trim((string)$cardId);

        try {
            $result = DB::transaction(function () use ($isbn, $cardId) {
                // 1) Borrower exists
                $borrower = Borrower::where('Card_id', $cardId)->first();
                if (!$borrower) {
                    return ['ok' => false, 'message' => "Borrower with Card ID {$cardId} not found."];
                }

                // 2) Borrower has unpaid fines?
                $hasUnpaidFine = Fine::where('Paid', 0)
                    ->whereHas('loan', function ($q) use ($cardId) {
                        $q->where('Card_id', $cardId);
                    })->exists();

                if ($hasUnpaidFine) {
                    return ['ok' => false, 'message' => 'Borrower has unpaid fines and cannot checkout new books.'];
                }

                // 3) Borrower active loans count (Date_in null / 0000-00-00)
                $activeLoans = BookLoan::where('Card_id', $cardId)
                    ->whereNull('Date_in')
                    ->count();

                if ($activeLoans >= 3) {
                    return ['ok' => false, 'message' => 'Borrower already has 3 active loans and cannot checkout more books.'];
                }

                // 4) Book availability: no active loan for this ISBN
                $bookUnavailable = BookLoan::where('Isbn', $isbn)
                    ->whereNull('Date_in')
                    ->exists();


                if ($bookUnavailable) {
                    return ['ok' => false, 'message' => 'This book (ISBN '.$isbn.') is currently not available (already checked out).'];
                }

                // 5) Create new loan row. Loan_id in the schema is not auto-increment; compute next id.
                $maxId = DB::table('book_loans')->max('Loan_id');
                $nextId = ($maxId !== null) ? ((int)$maxId + 1) : 1;

                $dateOut = Carbon::today()->format('Y-m-d');
                $dueDate = Carbon::today()->addDays(14)->format('Y-m-d');

                $loan = BookLoan::create([
                    'Loan_id' => $nextId,
                    'Isbn' => $isbn,
                    'Card_id' => $cardId,
                    'Date_out' => $dateOut,
                    'Due_date' => $dueDate,
                    'Date_in' => null
                ]);

                return ['ok' => true, 'loan' => $loan, 'message' => 'Checkout successful. Due date: '.$dueDate];
            });
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Server error: '.$e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Server error: '.$e->getMessage());
        }

        if (!$result['ok']) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $result['message']], 422);
            }
            return redirect()->back()->with('error', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => $result['message'], 'loan' => $result['loan']]);
        }

        return redirect()->back()->with('success', $result['message']);
    }
}
