<?php

use App\Http\Controllers\AssetExportController;
use App\Http\Controllers\DrukController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/srodki');

// -- Guest ------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Volt::route('login', 'auth.login')->name('login');
});

Route::post('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout')->middleware('auth');

// -- Authenticated ----------------------------------------------------------
Route::middleware('auth')->group(function () {
    Volt::route('srodki', 'assets.index')->name('assets.index');

    // Exports (thin controllers, defined before the {asset} routes).
    Route::middleware('can:view assets')->group(function () {
        Route::get('srodki/export/csv', [AssetExportController::class, 'csv'])->name('assets.export.csv');
        Route::get('srodki/export/xlsx', [AssetExportController::class, 'xlsx'])->name('assets.export.xlsx');
    });

    Volt::route('srodki/nowy', 'assets.form')->name('assets.create')->middleware('can:manage assets');
    Volt::route('srodki/{asset}/edytuj', 'assets.form')->name('assets.edit')->middleware('can:manage assets');
    Volt::route('srodki/{asset}/historia', 'assets.history')->name('assets.history');

    Volt::route('powiadomienia', 'transfers.index')->name('transfers.index');

    Route::middleware('can:decide transfers')->group(function () {
        Volt::route('druki-zmu', 'druki.zmu')->name('druki.zmu');
        Volt::route('druki-likwidacji', 'druki.likwidacja')->name('druki.likwidacja');

        Route::get('powiadomienia/{transferRequest}/zmu-pdf', [DrukController::class, 'zmu'])->name('druki.zmu.pdf');
        Route::get('powiadomienia/{transferRequest}/likwidacja-pdf', [DrukController::class, 'likwidacja'])->name('druki.likwidacja.pdf');
    });

    // Read-only view of the fields the current user belongs to.
    Volt::route('moje-pola-spisowe', 'inventory-fields.mine')->name('my-fields');

    // The full field list — admins and the inventory section (read-only for the latter).
    Route::middleware('can:view inventory fields')->group(function () {
        Volt::route('pola-spisowe', 'inventory-fields.index')->name('inventory-fields.index');
    });

    // Field administration (create/edit/delete) — admins only.
    Route::middleware('can:manage inventory fields')->group(function () {
        Volt::route('pola-spisowe/nowy', 'inventory-fields.form')->name('inventory-fields.create');
        Volt::route('pola-spisowe/{inventoryField}/edytuj', 'inventory-fields.form')->name('inventory-fields.edit');
    });

    // User list — admins and the inventory section (read-only for the latter).
    Route::middleware('can:view users')->group(function () {
        Volt::route('uzytkownicy', 'users.index')->name('users.index');
    });

    // User administration (create/edit + impersonation) — admins only.
    Route::middleware('can:manage users')->group(function () {
        Volt::route('uzytkownicy/nowy', 'users.form')->name('users.create');
        Volt::route('uzytkownicy/{user}/edytuj', 'users.form')->name('users.edit');
        Route::get('przejmij-sesje/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });

    // Ending impersonation must be reachable as the impersonated user (any role).
    Route::get('zakoncz-przejecie', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
});
