<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookAuthor extends Model
{
    protected $table = 'book_authors';
    public $timestamps = false;

    protected $fillable = [
        'Isbn',
        'Author_id',
    ];
}
