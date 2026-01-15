<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\ContractController;
use App\Http\Controllers\LicitacionController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\UserController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::resource('contracts', ContractController::class);
    Route::resource('licitaciones', LicitacionController::class)->parameters(['licitaciones' => 'licitacion']);
    Route::resource('cvs', CurriculumController::class);
    Route::resource('users', UserController::class); // Config maps to users here
    
    // Alias for config menu link
    Route::get('/config', [UserController::class, 'index'])->name('config');
});

require __DIR__.'/auth.php';
