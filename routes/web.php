<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;

Route::get('/', function () {
    return response()->view('welcome')->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
});

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
