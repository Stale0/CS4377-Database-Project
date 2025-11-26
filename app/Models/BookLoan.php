<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookLoan extends Model
{
    protected $table = 'book_loans';
    public $timestamps = false;
    protected $primaryKey = 'Loan_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'Loan_id',
        'Isbn',
        'Card_id',
        'Date_out',
        'Due_date',
        'Date_in'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'Isbn', 'Isbn');
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'Card_id', 'Card_id');
    }

    public function fine()
    {
        return $this->hasOne(Fine::class, 'Loan_id', 'Loan_id');
    }
}
