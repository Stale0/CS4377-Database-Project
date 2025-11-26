<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookLoanController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book-search', [BookController::class, 'index'])->name('books.search');
Route::post('/checkout', [BookLoanController::class, 'checkout'])->name('books.checkout');
