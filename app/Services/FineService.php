<?php

namespace App\Services;

use App\Models\BookLoan;
use App\Models\Fine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
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
     * Returns decimal(8,2) or null if not overdue.
     */
    public static function calculateFineAmount(BookLoan $loan): ?string
    {
        // Use date-only (startOfDay) values so fines increment by whole days
        $dueDate = Carbon::parse($loan->Due_date)->startOfDay();
        $currentDate = self::getCurrentDate()->startOfDay();

        // Always use current date for fine calculation; Date_in is only for record-keeping
        // Fines are calculated from today (simulated or real) back to due date
        $checkDate = $currentDate;

        // Calculate signed day difference (checkDate - dueDate). If <= 0, not overdue.
        $daysOverdue = $checkDate->diffInDays($dueDate, false);

        if ($daysOverdue <= 0) {
            return null;
        }

        // Calculate overdue days as whole calendar days
        $fineAmount = $daysOverdue * self::DAILY_RATE;

        // Return formatted 2-decimal string to keep comparisons consistent
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
    DB::beginTransaction();

    try {
        $currentDate = self::getCurrentDate()->format('Y-m-d');

        $overdueLoans = BookLoan::where(function ($q) use ($currentDate) {
            // Overdue & not returned
            $q->whereNull('Date_in')
              ->where('Due_date', '<', $currentDate)
            ->orWhere(function($ret) {
                // Returned but past due
                $ret->whereNotNull('Date_in')
                    ->whereColumn('Date_in', '>', 'Due_date');
            });
        })->get();

        // Log initial state
        try {
            Log::info('FineService.updateAllFines start', ['currentDate' => $currentDate, 'overdue_count' => count($overdueLoans)]);
        } catch (\Exception $e) {
            // Logging should not break execution
        }

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

                if ((float)$existingFine->Fine_amt != (float)$calculatedFine) {
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

        DB::commit();
        try {
            Log::info('FineService.updateAllFines complete', ['created' => $created, 'updated' => $updated]);
        } catch (\Exception $e) {
            // ignore logging errors
        }
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }

    return compact('created', 'updated');
}

    /**
     * Get all unpaid fines grouped by borrower (Card_id) with totals.
     * Returns array of borrowers with unpaid fine totals.
     */
    public static function getUnpaidFinesByBorrower()
    {
        return DB::table('fines')
            ->where('Paid', false)
            ->join('book_loans', 'fines.Loan_id', '=', 'book_loans.Loan_id')
            ->join('borrower', 'book_loans.Card_id', '=', 'borrower.Card_id')
            ->select(
                'borrower.Card_id',
                'borrower.Bname',
                DB::raw('SUM(CAST(fines.Fine_amt AS DECIMAL(10,2))) as total_fine')
            )
            ->groupBy('borrower.Card_id', 'borrower.Bname')
            ->orderBy('borrower.Bname')
            ->get();
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
     * Returns success bool and message.
     */
    public static function payAllBorrowerFines(string $cardId): array
    {
        DB::beginTransaction();
        try {
            $updated = Fine::where('Paid', false)
                ->whereHas('loan', function ($q) use ($cardId) {
                    $q->where('Card_id', $cardId)
                      ->where(function ($subQ) {
                          // Only pay if book is returned
                          $subQ->whereNotNull('Date_in')
                               ->where('Date_in', '!=', null);
                      });
                })
                ->update(['Paid' => true]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully paid {$updated} fine(s).",
                'count' => $updated
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error paying fines: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * Check if a borrower has any unpaid fines for books that are still checked out.
     * Used to prevent checkout if they have unpaid fines for unreturned books.
     */
    public static function hasPendingUnpaidFines(string $cardId): bool
    {
        return Fine::where('Paid', false)
            ->whereHas('loan', function ($q) use ($cardId) {
                $q->where('Card_id', $cardId)
                  ->where(function ($subQ) {
                      // Books still checked out
                      $subQ->whereNull('Date_in')->orWhere('Date_in', '0000-00-00');
                  });
            })
            ->exists();
    }
}
