<?php

use App\Http\Controllers\Dashboard\OrderDashboardController;

use App\Http\Controllers\api\Ketawebhook;
use App\Http\Controllers\KetaController;
use App\Http\Controllers\NinjaImportMenuController;
use App\Http\Controllers\ToYouPSKMenuController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ToYouLCPMenuController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/toyou-choice', function () {
    return view('toyou_choice'); // Ensure this matches your Blade file name
})->name('toyou.choice');

Route::get('/ninja-choice', function () {
    return view('Ninja_choice');
})->name('ninja.choice');

Route::get('/keta-choice', function () {
    return view('keta_choice');
})->name('keta.choice');

Route::get('/toyou-pos-mapping', function () {
    return view('toyou_POS_Mapping'); // Ensure this matches your Blade file name
})->name('toyou.pos.mapping');

Route::get('/Ninja-add-branch', function () {
    return view('ninja_add_branch'); // Ensure this matches your Blade file name
})->name('Ninja.add.branch');


Route::get('/toyou-menu-sync', function () {
    return view('toyou_Menu_Sync'); // Ensure this matches your Blade file name
})->name('toyou.Menu.Sync');

Route::get('/ninja-menu-sync', function () {
    return view('Ninja_Menu_Sync'); // Ensure this matches your Blade file name
})->name('Ninja.Menu.Sync');

Route::get('/keta-menu-sync', function () {
    return view('keta_Menu_Sync'); // Ensure this matches your Blade file name
})->name('keta.Menu.Sync');

Route::post('/import-menu-toyou-lcp', [ToYouLCPMenuController::class , 'importMenu'])->name('importMenu-toyou-lcp');
Route::post('/import-menu-toyou-psk', [ToYouPSKMenuController::class , 'importMenu'])->name('importMenu-toyou-psk');
Route::post('/mapPOSLCP', [ToYouLCPMenuController::class , 'mapPOSLCP'])->name('mapPOSLCP');
Route::post('/mapPOSPSK', [ToYouLCPMenuController::class , 'mapPOSPSK'])->name('mapPOSPSK');
Route::post('/importMenu-toyou-cnd', [ToYouLCPMenuController::class , 'importMenuCND'])->name('importMenu-toyou-cnd');

Route::get('/fetchPOSLocations', [ToYouLCPMenuController::class , 'fetchPOSLocations']);

Route::post('/import-menu-toyou-okashi', [ToYouLCPMenuController::class , 'importMenuOkashi'])->name('importMenu-toyou-okashi');
// Route for mapping CND POS
Route::post('/mapPOSCND', [ToYouLCPMenuController::class , 'mapPOSCND'])->name('mapPOSCND');

// Route for mapping OKS POS
Route::post('/mapPOSOKS', [ToYouLCPMenuController::class , 'mapPOSOKS'])->name('mapPOSOKS');



Route::post('/sync-menu', [NinjaImportMenuController::class , 'syncMenu'])->name('syncMenuAction');
Route::post('/sync-menu-ninja-branch', [NinjaImportMenuController::class , 'syncMenuBranch'])->name('sync.menu-branch-ninja');
Route::post('/sync-menu-ninja-sara', [NinjaImportMenuController::class , 'syncMenuSara'])->name('sync.menu-sara-ninja');
Route::post('/ninja-branches', [NinjaImportMenuController::class , 'store'])->name('ninja-branches.store');


Route::post('/sync-menu-keta', [KetaController::class , 'syncMenu'])->name('sync.menu-keta');
Route::post('/sync-menu-keta-branch', [KetaController::class , 'syncMenuBranch'])->name('sync.menu-branch-keta');
Route::post('/sync-menu-keta-sara', [KetaController::class , 'syncSaraBranches'])->name('sync.menu-sara-keta');


Route::get('/keeta-add-branch', function () {
    return view('keeta_add_branch'); // Ensure this matches your Blade file name
})->name('keeta.add.branch');
Route::post('/keeta-branches', [KetaController::class , 'store'])->name('keeta-branches.store');



// Public login routes (no auth middleware)
Route::get('/dashboard/login',  [OrderDashboardController::class, 'showLogin'])->name('dashboard.login');
Route::post('/dashboard/login', [OrderDashboardController::class, 'postLogin'])->name('dashboard.login.post');
Route::get('/dashboard/logout', [OrderDashboardController::class, 'logout'])->name('dashboard.logout');

Route::middleware('dashboard.auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/',               fn() => redirect()->route('dashboard.orders.index'));
    Route::get('/orders',         [OrderDashboardController::class, 'index'])->name('orders.index');
    Route::get('/orders/poll',    [OrderDashboardController::class, 'poll'])->name('orders.poll');
    Route::get('/orders/export',  [OrderDashboardController::class, 'export'])->name('orders.export');
    Route::get('/orders/{id}',    [OrderDashboardController::class, 'show'])->name('orders.show');
});