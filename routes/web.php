<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TagController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/tags', function () {
    return Inertia::render('tags/index');
});
Route::get('/contacts', function () {
    return Inertia::render('contacts/index');
});
Route::get('/device', function () {
    return Inertia::render('device/index');
});
Route::get('/campaigns', function () {
    return Inertia::render('campaigns/index');
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
