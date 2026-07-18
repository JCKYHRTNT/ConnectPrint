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

Route::get('/u/{username}', function () {
    return redirect()->route('home.user', request()->query());
})->middleware('auth.user')->name('home.user.legacy');

// Guest artwork detail
Route::get('/artworks/{id}', [HomeController::class, 'artworkDetail'])
    ->whereNumber('id')
    ->name('artworks.show');

Route::get('/shared/artworks/{shareToken}', [ArtworkController::class, 'shared'])
    ->name('artworks.shared');

Route::get('/creators/{user}', [CreatorController::class, 'show'])
    ->name('creators.show');

Route::view('/print-with-printbox', 'printbox')
    ->name('printbox');

Route::middleware('auth.user')->group(function () {
    Route::get('/artworks', [ArtworkController::class, 'index'])->name('artworks.index');
    Route::get('/artworks/create', [ArtworkController::class, 'create'])->name('artworks.create');
    Route::post('/artworks', [ArtworkController::class, 'store'])->name('artworks.store');
    Route::get('/artworks/{artwork}/edit', [ArtworkController::class, 'edit'])->whereNumber('artwork')->name('artworks.edit');
    Route::put('/artworks/{artwork}', [ArtworkController::class, 'update'])->whereNumber('artwork')->name('artworks.update');
    Route::patch('/artworks/{artwork}/archive', [ArtworkController::class, 'archive'])->whereNumber('artwork')->name('artworks.archive');
    Route::patch('/artworks/{artwork}/restore', [ArtworkController::class, 'restore'])->whereNumber('artwork')->name('artworks.restore');
    Route::delete('/artworks/{artwork}', [ArtworkController::class, 'destroy'])->whereNumber('artwork')->name('artworks.destroy');
    Route::get('/artworks/{artwork}/print-file', [ArtworkController::class, 'printFile'])->whereNumber('artwork')->name('artworks.print-file');
});

// User artwork detail
Route::get('/u/{username}/artworks/{id}', function (string $username, int $id) {
    return redirect()->route('artworks.show', ['id' => $id]);
})
    ->whereNumber('id')
    ->name('artworks.show.user.legacy');

// User cart
Route::get('/cart', [CartController::class, 'index'])
    ->middleware('auth.user')
    ->name('cart');

Route::get('/u/{username}/cart', function () {
    return redirect()->route('cart');
})->middleware('auth.user')->name('cart.legacy');

// User Cart Add Update
Route::post('/cart/artworks/{artwork}', [CartController::class, 'add'])
    ->middleware('auth.user')
    ->name('cart.add');

Route::post('/cart/items/{item}/update', [CartController::class, 'update'])
    ->middleware('auth.user')
    ->name('cart.item.update');

// User Cart Checkout
Route::post('/cart/checkout', [CartController::class, 'checkout'])
    ->middleware('auth.user')
    ->name('cart.checkout');

Route::middleware('auth.user')->group(function () {
    Route::get('/u/{username}/artworks', fn (string $username) => redirect()->route('artworks.index', request()->query()))->name('artworks.index.legacy');
    Route::get('/u/{username}/artworks/create', fn (string $username) => redirect()->route('artworks.create'))->name('artworks.create.legacy');
    Route::get('/u/{username}/artworks/{artwork}/edit', fn (string $username, int $artwork) => redirect()->route('artworks.edit', ['artwork' => $artwork]))->whereNumber('artwork')->name('artworks.edit.legacy');
    Route::get('/u/{username}/artworks/{artwork}/print-file', fn (string $username, int $artwork) => redirect()->route('artworks.print-file', ['artwork' => $artwork]))->whereNumber('artwork')->name('artworks.print-file.legacy');

    Route::get('/u/{username}/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/u/{username}/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/u/{username}/purchased-artworks', [PurchaseController::class, 'purchasedArtworks'])->name('purchases.library');
    Route::get('/u/{username}/sales', [PurchaseController::class, 'sales'])->name('sales');

    Route::get('/u/{username}/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/u/{username}/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/u/{username}/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

Route::post('/artworks/{artwork}/reports', [ArtworkReportController::class, 'store'])
    ->name('artworks.reports.store');

// Account Page
// User account (view)
Route::get('/account', [AccountController::class, 'userAccount'])
    ->middleware('auth.user')
    ->name('account');

Route::get('/u/{username}/account', function () {
    return redirect()->route('account', request()->query());
})->middleware('auth.user')->name('account.legacy');

// User redirect from admin/crud
Route::get('/u/{username}/admin', function ($username) {
    return redirect()->route('home.user');
});

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

// Admin redirect from cart
Route::get('/a/{username}/cart', function ($username) {
    return redirect()->route('admin.user', ['username' => $username]);
});

// Admin
Route::middleware('admin')->group(function () {

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

    Route::patch('/a/{username}/admin/artworks/{artwork}/moderate', [AdminController::class, 'moderateArtwork'])
        ->name('admin.artworks.moderate');

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
