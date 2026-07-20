<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

// Public site (home now renders our page instead of the kit's Welcome —
// the kit's Welcome.vue file itself is left untouched on disk).
Route::inertia('/', 'Home')->name('home');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{project:slug}', [PortfolioController::class, 'show'])->name('portfolio.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::inertia('/privacy', 'Privacy')->name('privacy');
Route::inertia('/accessibility', 'Accessibility')->name('accessibility');

// Kit routes preserved as-is:
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php'; // kit file — exists. NOTE: no auth.php require (Fortify registers auth routes itself).
