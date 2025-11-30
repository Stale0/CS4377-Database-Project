<?php

namespace App\Services;

use App\Models\BookLoan;
use App\Models\Fine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class FineService
{
    const DAILY_RATE = 0.25;

    /**
     * Get the current date, using simulated date from session if available
     */
    private static function getCurrentDate(): Carbon
    {
        $simulatedDate = Session::get('simulated_date');
        if ($simulatedDate) {
            return Carbon::createFromFormat('Y-m-d', $simulatedDate);
        }
        return Carbon::today();
    }

    /**
     * Calculate the fine amount for a single loan based on overdue days.
     * Returns a formatted 2-decimal string (e.g. "2.25") or null if not overdue.
     */
    public static function calculateFineAmount(BookLoan $loan): string
{
    // Use date-only (startOfDay) values so fines increment by whole days
    $dueDate = Carbon::parse($loan->Due_date)->startOfDay();
    $currentDate = self::getCurrentDate()->startOfDay();

    // Two scenarios:
    // 1) Returned: if Date_in is present, use Date_in to compute days overdue relative to due date.
    // 2) Still out: use current (simulated) date to compute estimated overdue days.
    if (!empty($loan->Date_in)) {
    $dateIn = Carbon::parse($loan->Date_in)->startOfDay();
    $daysOverdue = $dateIn->greaterThan($dueDate)
        ? $dueDate->diffInDays($dateIn)
        : 0;
    } else {
        $daysOverdue = $currentDate->greaterThan($dueDate)
        ? $dueDate->diffInDays($currentDate)
        : 0;
    }
    if ($daysOverdue <= 0) {
        // Always return "0.00" instead of null
        return number_format(0, 2, '.', '');
    }

    $fineAmount = $daysOverdue * self::DAILY_RATE;

    // Return fixed 2-decimal string so comparisons are deterministic
    return number_format($fineAmount, 2, '.', '');
}

    /**
     * Update/refresh all fines in the system.
     * - Create new Fine entries for overdue loans without a fine
     * - Update existing unpaid Fine entries with new amounts
     * - Ignore paid fines
     */
    public static function updateAllFines(): array
    {
        $created = 0;
        $updated = 0;

        $currentDate = self::getCurrentDate()->format('Y-m-d');

        // Find loans that are overdue: either currently out and past due, or returned late
        $overdueLoans = BookLoan::where(function ($q) use ($currentDate) {
            $q->where(function ($q2) use ($currentDate) {
                // still out and due_date < today
                $q2->whereNull('Date_in')
                   ->where('Due_date', '<', $currentDate);
            })->orWhere(function ($q3) {
                // returned but returned after due date
                $q3->whereNotNull('Date_in')
                   ->whereColumn('Date_in', '>', 'Due_date');
            });
        })->get();

        Log::info('FineService.updateAllFines start', ['currentDate' => $currentDate, 'overdue_count' => count($overdueLoans)]);

        foreach ($overdueLoans as $loan) {
            $calculatedFine = self::calculateFineAmount($loan);

            if (!$calculatedFine || $calculatedFine <= 0) {
                Log::info('FineService: no fine for loan (not overdue after day-based calc)', [
                    'Loan_id' => $loan->Loan_id,
                    'Isbn' => $loan->Isbn ?? null,
                    'Card_id' => $loan->Card_id ?? null,
                    'Due_date' => $loan->Due_date ?? null,
                    'Date_in' => $loan->Date_in ?? null,
                    'calculatedFine' => $calculatedFine
                ]);
                continue;
            }

            $existingFine = Fine::where('Loan_id', $loan->Loan_id)->first();

            if ($existingFine) {
                if ($existingFine->Paid) {
                    Log::info('FineService: existing fine already paid, skipping update', ['Loan_id' => $loan->Loan_id]);
                    continue;
                }

                $existingAmt = number_format((float)$existingFine->Fine_amt, 2, '.', '');
                if ($existingAmt !== $calculatedFine) {
                    $old = $existingFine->Fine_amt;
                    $existingFine->Fine_amt = $calculatedFine;
                    try {
                        $existingFine->save();
                        $updated++;
                        Log::info('FineService: updated fine', ['Loan_id' => $loan->Loan_id, 'old' => $old, 'new' => $calculatedFine]);
                    } catch (\Exception $e) {
                        Log::error('FineService: failed to update fine', ['Loan_id' => $loan->Loan_id, 'error' => $e->getMessage()]);
                    }
                } else {
                    Log::debug('FineService: fine amount unchanged', ['Loan_id' => $loan->Loan_id, 'amount' => $calculatedFine]);
                }
            } else {
                try {
                    Fine::create([
                        'Loan_id' => $loan->Loan_id,
                        'Fine_amt' => $calculatedFine,
                        'Paid' => false,
                    ]);
                    $created++;
                    Log::info('FineService: created fine', ['Loan_id' => $loan->Loan_id, 'amount' => $calculatedFine]);
                } catch (\Exception $e) {
                    Log::error('FineService: failed to create fine', ['Loan_id' => $loan->Loan_id, 'amount' => $calculatedFine, 'error' => $e->getMessage()]);
                }
            }
        }

        Log::info('FineService.updateAllFines complete', ['created' => $created, 'updated' => $updated]);

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Get all fines (paid and unpaid) grouped by borrower.
     */
    public static function getAllFinesByBorrower($includeUnpaidOnly = true)
    {
        $query = DB::table('fines')
            ->join('book_loans', 'fines.Loan_id', '=', 'book_loans.Loan_id')
            ->join('borrower', 'book_loans.Card_id', '=', 'borrower.Card_id')
            ->select(
                'fines.Loan_id',
                'borrower.Card_id',
                'borrower.Bname',
                'book_loans.Isbn',
                'book_loans.Due_date',
                'book_loans.Date_in',
                'fines.Fine_amt',
                'fines.Paid'
            );

        if ($includeUnpaidOnly) {
            $query->where('fines.Paid', false);
        }

        return $query->orderBy('borrower.Card_id', 'asc')
            ->orderBy('fines.Loan_id', 'asc')
            ->get();
    }

    /**
     * Pay all unpaid fines for a borrower.
     * Returns success result array.
     */
    public static function payAllBorrowerFines(string $cardId): array
    {
        DB::beginTransaction();
        try {
            // ensure no active loans
            $hasActive = BookLoan::where('Card_id', $cardId)
                ->where(function ($q) {
                    $q->whereNull('Date_in');
                })->exists();

            if ($hasActive) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Borrower has unreturned books; cannot accept payment.', 'count' => 0];
            }

            $updated = Fine::where('Paid', false)
                ->whereHas('loan', function ($q) use ($cardId) {
                    $q->where('Card_id', $cardId);
                })->update(['Paid' => true]);

            DB::commit();

            return ['success' => true, 'message' => "Successfully paid {$updated} fine(s).", 'count' => $updated];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FineService: error paying borrower fines', ['cardId' => $cardId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error paying fines: ' . $e->getMessage(), 'count' => 0];
        }
    }

    /**
     * Check if a borrower has any unpaid fines for books that are still checked out.
     */
    public static function hasPendingUnpaidFines(string $cardId): bool
    {
        return Fine::where('Paid', false)
            ->whereHas('loan', function ($q) use ($cardId) {
                $q->where('Card_id', $cardId)
                  ->where(function ($subQ) {
                      $subQ->whereNull('Date_in');
                  });
            })->exists();
    }
}
