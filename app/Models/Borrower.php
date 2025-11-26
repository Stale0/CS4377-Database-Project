<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    protected $table = 'borrower';
    public $timestamps = false;
    protected $primaryKey = 'Card_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'Card_id',
        'Ssn',
        'Bname',
        'Address',
        'Phone'
    ];

    public function loans()
    {
        return $this->hasMany(BookLoan::class, 'Card_id', 'Card_id');
    }
}
