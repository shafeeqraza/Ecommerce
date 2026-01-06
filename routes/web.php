<?php

use App\Livewire\CartShow;
use App\Livewire\ProductList;
use Illuminate\Support\Facades\Route;

Route::get('/', ProductList::class)->name('home');

Route::get('/cart', CartShow::class)
    ->middleware(['auth'])
    ->name('cart');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
