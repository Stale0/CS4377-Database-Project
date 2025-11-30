<?php

namespace App\Http\Controllers;

use App\Services\FineService;
use Illuminate\Http\Request;

class FineController extends Controller
{
    /**
     * Show fines page with filtering options
     */
    public function finesForm(Request $request)
    {
        $showPaid = $request->query('show_paid', '0') === '1';
        
        // Trigger fine updates before displaying
        try {
            FineService::updateAllFines();
        } catch (\Exception $e) {
            // Log but don't fail
            Log::error("Error updating fines: " . $e->getMessage());
        }

        // Get fines grouped by borrower
        $fines = FineService::getAllFinesByBorrower(!$showPaid);
        
        // Group by Card_id for display
        $borrowers = [];
        foreach ($fines as $fine) {
            if (!isset($borrowers[$fine->Card_id])) {
                $borrowers[$fine->Card_id] = [
                    'Card_id' => $fine->Card_id,
                    'Bname' => $fine->Bname,
                    'fines' => [],
                    'total' => 0,
                ];
            }
            $borrowers[$fine->Card_id]['fines'][] = $fine;
            $borrowers[$fine->Card_id]['total'] += (float)$fine->Fine_amt;
        }

        return view('fines.manage', [
            'borrowers' => $borrowers,
            'show_paid' => $showPaid,
        ]);
    }

    /**
     * Pay all fines for a borrower
     */
    public function payFines(Request $request)
    {
        $cardId = $request->input('Card_id');
        
        if (!$cardId) {
            return redirect()->back()->with('error', 'No borrower selected.');
        }

        // Check if borrower has unreturned books with fines
        if (FineService::hasPendingUnpaidFines($cardId)) {
            return redirect()->back()->with('error', 'Cannot pay fines for books that are still checked out. Please return all books first.');
        }

        $result = FineService::payAllBorrowerFines($cardId);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }
}
