<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'authors';
    public $timestamps = false;

    protected $fillable = [
        'Author_id',
        'Name',
    ];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'Book_authors', 'Author_id', 'Isbn', 'Author_id', 'Isbn');
    }
}
