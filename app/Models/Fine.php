<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    protected $table = 'fines';
    public $timestamps = false;
    protected $primaryKey = 'Loan_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Loan_id',
        'Fine_amt',
        'Paid'
    ];

    protected $casts = [
        'Fine_amt' => 'float',
        'Paid' => 'boolean',
    ];

    public function loan()
    {
        return $this->belongsTo(BookLoan::class, 'Loan_id', 'Loan_id');
    }
}
