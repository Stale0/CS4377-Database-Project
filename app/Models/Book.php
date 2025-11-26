<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'book';
    public $timestamps = false;

    protected $fillable = [
        'Title',
        'Isbn'
    ];

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'Book_authors', 'Isbn', 'Author_id', 'Isbn', 'Author_id');
    }

    public function loans()
    {
        return $this->hasMany(BookLoan::class, 'Isbn', 'Isbn');
    }
}
