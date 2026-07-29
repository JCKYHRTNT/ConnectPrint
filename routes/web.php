<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ArtworkReportController;
// Guest Home
Route::get('/', [HomeController::class, 'home'])->name('home');

// User Home
Route::get('/home', [HomeController::class, 'homeForUser'])
    ->middleware('auth.user')
    ->name('home.user');

// Guest artwork detail
Route::get('/artworks/{id}', [HomeController::class, 'artworkDetail'])
    ->whereNumber('id')
    ->name('artworks.show');

Route::get('/shared/artworks/{shareToken}', [ArtworkController::class, 'shared'])
    ->name('artworks.shared');

Route::get('/artworks/{artwork}/preview', [ArtworkController::class, 'preview'])
    ->whereNumber('artwork')
    ->name('artworks.preview');

Route::get('/creators/{user}', [CreatorController::class, 'show'])
    ->name('creators.show');

Route::middleware('auth.user')->group(function () {
    Route::get('/artworks/create', [ArtworkController::class, 'create'])->name('artworks.create');
    Route::post('/artworks', [ArtworkController::class, 'store'])->name('artworks.store');
    Route::post('/artworks/draft', [ArtworkController::class, 'saveDraft'])->name('artworks.draft.store');
    Route::get('/artworks/{artwork}/edit', [ArtworkController::class, 'edit'])->whereNumber('artwork')->name('artworks.edit');
    Route::put('/artworks/{artwork}', [ArtworkController::class, 'update'])->whereNumber('artwork')->name('artworks.update');
    Route::post('/artworks/{artwork}/draft', [ArtworkController::class, 'saveDraft'])->whereNumber('artwork')->name('artworks.draft.update');
    Route::patch('/artworks/{artwork}/visibility', [ArtworkController::class, 'updateVisibility'])->whereNumber('artwork')->name('artworks.visibility.update');
    Route::patch('/artworks/{artwork}/archive', [ArtworkController::class, 'archive'])->whereNumber('artwork')->name('artworks.archive');
    Route::patch('/artworks/{artwork}/restore', [ArtworkController::class, 'restore'])->whereNumber('artwork')->name('artworks.restore');
    Route::delete('/artworks/{artwork}', [ArtworkController::class, 'destroy'])->whereNumber('artwork')->name('artworks.destroy');
    Route::get('/artworks/{artwork}/print-file', [ArtworkController::class, 'printFile'])->whereNumber('artwork')->name('artworks.print-file');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->whereNumber('purchase')->name('purchases.show');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->whereNumber('notification')->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// User cart
Route::get('/cart', [CartController::class, 'index'])
    ->middleware('auth.user')
    ->name('cart');

// User Cart Add Update
Route::post('/cart/artworks/{artwork}', [CartController::class, 'add'])
    ->middleware('auth.user')
    ->name('cart.add');

Route::post('/cart/items/{item}/update', [CartController::class, 'update'])
    ->middleware('auth.user')
    ->name('cart.item.update');

Route::post('/cart/items/{item}/printbox', [CartController::class, 'updatePrintbox'])
    ->middleware('auth.user')
    ->name('cart.item.printbox.update');

// User Cart Checkout
Route::post('/cart/checkout', [CartController::class, 'checkout'])
    ->middleware('auth.user')
    ->name('cart.checkout');

Route::post('/artworks/{artwork}/reports', [ArtworkReportController::class, 'store'])
    ->name('artworks.reports.store');

// Account Page
// User account (view)
Route::get('/account', [AccountController::class, 'userAccount'])
    ->middleware('auth.user')
    ->name('account');

Route::get('/account/images-bought', [PurchaseController::class, 'purchasedArtworks'])
    ->middleware('auth.user')
    ->name('purchases.library');

// Admin account (view)
Route::get('/a/{username}/account', [AccountController::class, 'adminAccount'])
    ->middleware('admin')
    ->name('account.admin');

// Account update/delete (user)
Route::post('/account/update', [AccountController::class, 'update'])
    ->middleware('auth.user')
    ->name('account.update');

Route::post('/account/delete', [AccountController::class, 'destroy'])
    ->middleware('auth.user')
    ->name('account.delete');

// Account update/delete (admin)
Route::post('/a/{username}/account/update', [AccountController::class, 'update'])
    ->middleware('admin')
    ->name('account.admin.update');

Route::post('/a/{username}/account/delete', [AccountController::class, 'destroy'])
    ->middleware('admin')
    ->name('account.admin.delete');

// Admin
Route::middleware('admin')->group(function () {
    Route::get('/admin', function () {
        $admin = \App\Models\User::findOrFail(session('user_id'));

        return redirect()->route('admin.crud', ['username' => $admin->slug] + request()->query());
    })->name('admin.crud.short');

    // Admin Home
    Route::get('/a/{username}', [AdminController::class, 'indexForUser'])
        ->name('admin.user');

    // Admin CRUD
    Route::get('/a/{username}/admin', [AdminController::class, 'crud'])
        ->name('admin.crud');

    // Promote admin
    Route::post('/a/{username}/admin/promote', [AdminController::class, 'promoteAdmin'])
        ->name('admin.crud.promote');

    // Demote admin
    Route::post('/a/{username}/admin/demote', [AdminController::class, 'demoteAdmin'])
        ->name('admin.crud.demote');

    Route::post('/a/{username}/admin/fees', [AdminController::class, 'updateFees'])
        ->name('admin.fees.update');

    // ADMIN ARTWORK DETAIL
    Route::get('/a/{username}/artworks/{artwork}', [AdminController::class, 'artworkDetail'])
        ->whereNumber('artwork')
        ->name('admin.artworks.show');

    // ARTWORK CRUD
    Route::post('/a/{username}/artworks', [AdminController::class, 'storeArtwork'])
        ->name('admin.artworks.store');

    Route::get('/a/{username}/artworks/{artwork}/edit', [AdminController::class, 'editArtwork'])
        ->whereNumber('artwork')
        ->name('admin.artworks.edit');

    Route::put('/a/{username}/artworks/{artwork}', [AdminController::class, 'updateArtwork'])
        ->whereNumber('artwork')
        ->name('admin.artworks.update');

    Route::delete('/a/{username}/artworks/{artwork}', [AdminController::class, 'destroyArtwork'])
        ->whereNumber('artwork')
        ->name('admin.artworks.destroy');

    Route::patch('/a/{username}/admin/reports/{report}', [AdminController::class, 'resolveReport'])
        ->name('admin.reports.resolve');

    // CATEGORY CRUD
    Route::post('/a/{username}/categories', [AdminController::class, 'storeCategory'])
        ->name('admin.categories.store');

    Route::get('/a/{username}/categories/{category}/edit', [AdminController::class, 'editCategory'])
        ->name('admin.categories.edit');

    Route::put('/a/{username}/categories/{category}', [AdminController::class, 'updateCategory'])
        ->name('admin.categories.update');

    Route::delete('/a/{username}/categories/{category}', [AdminController::class, 'destroyCategory'])
        ->name('admin.categories.destroy');
});


// Authentication
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
// Redirect /login -> /login/en
Route::get('/login', function () {
    return redirect()->route('login.locale', ['locale' => 'en']);
})->name('login');

// Localized login pages
Route::get('/login/{locale}', [LoginController::class, 'show'])
    ->whereIn('locale', ['en', 'id'])
    ->name('login.locale');

Route::post('/login/{locale}', [LoginController::class, 'login'])
    ->whereIn('locale', ['en', 'id'])
    ->name('login.submit');


// Redirect /register -> /register/en
Route::get('/register', function () {
    return redirect()->route('register.locale', ['locale' => 'en']);
})->name('register');

// Localized register
Route::get('/register/{locale}', [LoginController::class, 'showRegister'])
    ->whereIn('locale', ['en', 'id'])
    ->name('register.locale');

Route::post('/register/{locale}', [LoginController::class, 'register'])
    ->whereIn('locale', ['en', 'id'])
    ->name('register.submit');

    
// Localization
Route::get('/language/{locale}', [LanguageController::class, 'switch'])
    ->name('language.switch');
