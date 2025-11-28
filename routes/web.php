<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookLoanController;
use App\Http\Controllers\BorrowerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book-search', [BookController::class, 'index'])->name('books.search');
Route::post('/checkout', [BookLoanController::class, 'checkout'])->name('books.checkout');
// Book check-in routes
Route::get('/book-check-in', [BookLoanController::class, 'checkinForm'])->name('books.check-in.form');
Route::post('/book-check-in', [BookLoanController::class, 'processCheckin'])->name('books.check-in');

// Borrower creation
Route::get('/borrowers/create', [BorrowerController::class, 'create'])->name('borrowers.create');
Route::post('/borrowers', [BorrowerController::class, 'store'])->name('borrowers.store');
