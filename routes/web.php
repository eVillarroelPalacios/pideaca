<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\TypeUserController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SubGroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\AdvertisingController;

Route::get('/', [AdvertisingController::class, 'index']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');

Route::get('/user-statuses', [UserStatusController::class, 'index'])->name('user-statuses.index');
Route::post('/user-statuses', [UserStatusController::class, 'store'])->name('user-statuses.store');
Route::put('/user-statuses/{userStatus}', [UserStatusController::class, 'update'])->name('user-statuses.update');
Route::delete('/user-statuses/{userStatus}', [UserStatusController::class, 'destroy'])->name('user-statuses.destroy');

Route::get('/type-users', [TypeUserController::class, 'index'])->name('type-users.index');
Route::post('/type-users', [TypeUserController::class, 'store'])->name('type-users.store');
Route::put('/type-users/{typeUser}', [TypeUserController::class, 'update'])->name('type-users.update');
Route::delete('/type-users/{typeUser}', [TypeUserController::class, 'destroy'])->name('type-users.destroy');

Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

Route::get('/subgroups', [SubGroupController::class, 'index'])->name('subgroups.index');
Route::post('/subgroups', [SubGroupController::class, 'store'])->name('subgroups.store');
Route::put('/subgroups/{subgroup}', [SubGroupController::class, 'update'])->name('subgroups.update');
Route::delete('/subgroups/{subgroup}', [SubGroupController::class, 'destroy'])->name('subgroups.destroy');

Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::put('/profile/provider', [ProfileController::class, 'updateProvider'])->name('profile.provider.update');
Route::get('/profile/services', [ProviderServiceController::class, 'index'])->name('profile.services.index');
Route::post('/profile/services', [ProviderServiceController::class, 'store'])->name('profile.services.store');
Route::put('/profile/services/reorder', [ProviderServiceController::class, 'reorder'])->name('profile.services.reorder');
Route::put('/profile/services/{service}', [ProviderServiceController::class, 'update'])->name('profile.services.update');
Route::delete('/profile/services/{service}', [ProviderServiceController::class, 'destroy'])->name('profile.services.destroy');
Route::post('/profile/images', [ProfileController::class, 'storeImage'])->name('profile.images.store');
Route::delete('/profile/images/{image}', [ProfileController::class, 'destroyImage'])->name('profile.images.destroy');
Route::get('/profile/subgroups', [ProfileController::class, 'getProviderSubgroups'])->name('profile.subgroups.index');
Route::post('/profile/subgroups/toggle', [ProfileController::class, 'toggleProviderSubgroup'])->name('profile.subgroups.toggle');
Route::get('/profile/address', [ProfileController::class, 'getAddress'])->name('profile.address.index');
Route::put('/profile/address', [ProfileController::class, 'saveAddress'])->name('profile.address.update');
Route::get('/countries', [ProfileController::class, 'getCountries'])->name('countries.index');
Route::get('/regions', [ProfileController::class, 'getRegions'])->name('regions.index');
