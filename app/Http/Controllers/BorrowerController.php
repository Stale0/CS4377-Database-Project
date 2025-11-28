<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Borrower;

class BorrowerController extends Controller
{
    /** Show create borrower form */
    public function create()
    {
        return view('borrowers.create');
    }

    /** Store a new borrower */
    public function store(Request $request)
    {
        // sanitize phone: remove all non-digits
        $rawPhone = $request->input('Phone');
        $digits = null;
        if ($rawPhone !== null) {
            $only = preg_replace('/\D+/', '', (string)$rawPhone);
            if ($only !== '') {
                // keep last 10 digits when longer than 10 (preserve area+number)
                if (strlen($only) > 10) {
                    $only = substr($only, -10);
                }
                $digits = $only;
            }
        }

        // sanitize SSN: remove non-digits
        $rawSsn = $request->input('Ssn');
        $ssnOnly = null;
        if ($rawSsn !== null) {
            $ssnOnly = preg_replace('/\D+/', '', (string)$rawSsn);
            if ($ssnOnly === '') {
                $ssnOnly = null;
            }
        }

        $data = [
            'Bname' => $request->input('Bname'),
            'Ssn' => $ssnOnly,
            'Address' => $request->input('Address'),
            'Phone' => $digits,
        ];

        $validator = Validator::make($data, [
            'Bname' => 'required|string|max:100',
            'Ssn' => 'required|digits:9',
            'Address' => 'required|string|max:255',
            'Phone' => 'nullable|digits_between:7,10',
        ], [
            'Bname.required' => 'Name is required.',
            'Ssn.required' => 'SSN is required.',
            'Ssn.digits' => 'SSN must be exactly 9 digits.',
            'Address.required' => 'Address is required.',
            'Phone.digits_between' => 'Phone must contain only digits (7-10 digits).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check SSN uniqueness
        if (Borrower::where('Ssn', (int)$data['Ssn'])->exists()) {
            return redirect()->back()->with('error', 'A borrower with that SSN already exists.')->withInput();
        }

        // Generate next Card_id compatible with existing format (zero-padded 6 digits)
        $row = DB::table('borrower')->selectRaw('MAX(CAST(Card_id AS UNSIGNED)) as max')->first();
        $max = $row->max ?? 0;
        $next = ((int)$max) + 1;
        $cardId = str_pad((string)$next, 6, '0', STR_PAD_LEFT);

        try {
            Borrower::create([
                'Card_id' => $cardId,
                'Ssn' => (int)$data['Ssn'],
                'Bname' => $data['Bname'],
                'Address' => $data['Address'],
                'Phone' => $data['Phone'] !== null ? (int)$data['Phone'] : null,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create borrower: '.$e->getMessage())->withInput();
        }

        return redirect()->route('borrowers.create')->with('success', 'Borrower created successfully. Card ID: '.$cardId);
    }
}
