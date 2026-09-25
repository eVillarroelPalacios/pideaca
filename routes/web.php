<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\TypeUserController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SubGroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\AdvertisingController;

Route::get('/', [AdvertisingController::class, 'index']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1')->name('password.forgot');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'showForm'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset.store');

Route::get('/registro/tipos', [RegisterController::class, 'types'])->name('registro.tipos');
Route::post('/registro', [RegisterController::class, 'store'])->name('registro.store');

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
Route::get('/type-users/pages-data', [TypeUserController::class, 'pagesData'])->name('type-users.pages-data');
Route::put('/type-users/{typeUser}/pages', [TypeUserController::class, 'updatePages'])->name('type-users.pages.update');
Route::post('/type-users/{typeUser}/pages/bulk', [TypeUserController::class, 'bulkPages'])->name('type-users.pages.bulk');
Route::post('/type-users/{typeUser}/pages/{page}', [TypeUserController::class, 'attachPage'])->name('type-users.pages.attach');
Route::delete('/type-users/{typeUser}/pages/{page}', [TypeUserController::class, 'detachPage'])->name('type-users.pages.detach');

Route::get('/admin/users', [UsuarioController::class, 'index'])->name('admin.users.index');
Route::get('/admin/users/{user}', [UsuarioController::class, 'show'])->name('admin.users.show');
Route::post('/admin/users', [UsuarioController::class, 'store'])->name('admin.users.store');
Route::put('/admin/users/{user}', [UsuarioController::class, 'update'])->name('admin.users.update');
Route::delete('/admin/users/{user}', [UsuarioController::class, 'destroy'])->name('admin.users.destroy');
Route::put('/admin/users/{user}/provider', [UsuarioController::class, 'updateProvider'])->name('admin.users.provider.update');
Route::post('/admin/users/{user}/addresses', [UsuarioController::class, 'storeAddress'])->name('admin.users.addresses.store');
Route::put('/admin/users/{user}/addresses/{address}', [UsuarioController::class, 'updateAddress'])->name('admin.users.addresses.update');
Route::delete('/admin/users/{user}/addresses/{address}', [UsuarioController::class, 'destroyAddress'])->name('admin.users.addresses.destroy');
Route::post('/admin/users/{user}/images', [UsuarioController::class, 'storeImage'])->name('admin.users.images.store');
Route::delete('/admin/users/{user}/images/{image}', [UsuarioController::class, 'destroyImage'])->name('admin.users.images.destroy');
    Route::post('/admin/users/{user}/pages/{page}', [UsuarioController::class, 'attachPage'])->name('admin.users.pages.attach');
    Route::delete('/admin/users/{user}/pages/{page}', [UsuarioController::class, 'detachPage'])->name('admin.users.pages.detach');
    Route::get('/admin/users/{user}/subgroups', [UsuarioController::class, 'subgroups'])->name('admin.users.subgroups.index');
    Route::post('/admin/users/{user}/subgroups/toggle', [UsuarioController::class, 'toggleSubgroup'])->name('admin.users.subgroups.toggle');

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
