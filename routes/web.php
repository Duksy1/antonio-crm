<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/activities/export', [ActivityController::class, 'export'])->name('activities.export');
    Route::patch('/activities/{activity}/toggle', [ActivityController::class, 'toggle'])->name('activities.toggle');
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::get('/companies/export', [CompanyController::class, 'export'])->name('companies.export');
    Route::get('/companies/board', [CompanyController::class, 'board'])->name('companies.board');
    Route::patch('/companies/{company}/status', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::patch('/companies/{company}/restore', [CompanyController::class, 'restore'])->name('companies.restore')->withTrashed();
    Route::delete('/companies/{company}/force', [CompanyController::class, 'forceDestroy'])->name('companies.force-destroy')->withTrashed();
    Route::resource('companies', CompanyController::class);

    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::get('/contacts/board', [ContactController::class, 'board'])->name('contacts.board');
    Route::patch('/contacts/{contact}/status', [ContactController::class, 'updateStatus'])->name('contacts.status');
    Route::patch('/contacts/{contact}/restore', [ContactController::class, 'restore'])->name('contacts.restore')->withTrashed();
    Route::delete('/contacts/{contact}/force', [ContactController::class, 'forceDestroy'])->name('contacts.force-destroy')->withTrashed();
    Route::resource('contacts', ContactController::class);

    Route::get('/deals/export', [DealController::class, 'export'])->name('deals.export');
    Route::get('/deals/board', [DealController::class, 'board'])->name('deals.board');
    Route::patch('/deals/{deal}/stage', [DealController::class, 'updateStage'])->name('deals.stage');
    Route::patch('/deals/{deal}/restore', [DealController::class, 'restore'])->name('deals.restore')->withTrashed();
    Route::delete('/deals/{deal}/force', [DealController::class, 'forceDestroy'])->name('deals.force-destroy')->withTrashed();
    Route::resource('deals', DealController::class);

    Route::get('/quotes/export', [QuoteController::class, 'export'])->name('quotes.export');
    Route::get('/quotes/board', [QuoteController::class, 'board'])->name('quotes.board');
    Route::patch('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.status');
    Route::post('/quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
    Route::patch('/quotes/{quote}/restore', [QuoteController::class, 'restore'])->name('quotes.restore')->withTrashed();
    Route::delete('/quotes/{quote}/force', [QuoteController::class, 'forceDestroy'])->name('quotes.force-destroy')->withTrashed();
    Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::resource('quotes', QuoteController::class);
});
