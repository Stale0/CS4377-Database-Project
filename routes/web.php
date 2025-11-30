<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookLoanController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\DateSimulatorController;

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

// Fines management
Route::get('/fines', [FineController::class, 'finesForm'])->name('fines.manage');
Route::post('/fines/pay', [FineController::class, 'payFines'])->name('fines.pay');

// Date Simulator
Route::get('/date/current', [DateSimulatorController::class, 'getCurrent'])->name('date.current');
Route::post('/date/set', [DateSimulatorController::class, 'setDate'])->name('date.set');
Route::post('/date/reset', [DateSimulatorController::class, 'resetDate'])->name('date.reset');

