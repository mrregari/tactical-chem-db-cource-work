<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EquipmentLoadoutController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReagentController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\ToolsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReagentController::class, 'index'])->name('home');
Route::get('/reagents', [ReagentController::class, 'index'])->name('reagents.index');
Route::get('/loadouts', [EquipmentLoadoutController::class, 'index'])->name('loadouts.index');
Route::get('/equipment-items', [EquipmentLoadoutController::class, 'catalog'])->name('equipment-items.index');
Route::get('/equipment-items/search', [EquipmentLoadoutController::class, 'searchItems'])->name('equipment-items.search');
Route::get('/reactions', [ReactionController::class, 'index'])->name('reactions.index');
Route::post('/source', [SourceController::class, 'update'])->name('source.update');
Route::get('/botany', [ToolsController::class, 'botany'])->name('tools.botany');
Route::get('/medbay', [ToolsController::class, 'medbay'])->name('tools.medbay');
Route::get('/craft-trees', [ToolsController::class, 'trees'])->name('tools.trees');
Route::get('/maps', [ToolsController::class, 'maps'])->name('tools.maps');
Route::get('/ordnance', [ToolsController::class, 'ordnance'])->name('tools.ordnance');
Route::get('/antag', [ToolsController::class, 'antag'])->name('tools.antag');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:user,admin'])->group(function () {
    Route::resource('reagents', ReagentController::class)->only(['create', 'store']);
    Route::resource('loadouts', EquipmentLoadoutController::class)->only(['create', 'store']);
    Route::get('/reactions/create', [ReactionController::class, 'create'])->name('reactions.create');
    Route::post('/reactions', [ReactionController::class, 'store'])->name('reactions.store');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{type}/{id}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
});

Route::get('/reagents/{reagent}', [ReagentController::class, 'show'])->name('reagents.show')->whereNumber('reagent');
Route::get('/loadouts/{loadout}', [EquipmentLoadoutController::class, 'show'])->name('loadouts.show')->whereNumber('loadout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/reagents/{reagent}/approve', [AdminController::class, 'approveReagent'])->name('reagents.approve');
    Route::post('/reagents/{reagent}/reject', [AdminController::class, 'rejectReagent'])->name('reagents.reject');
    Route::post('/loadouts/{loadout}/approve', [AdminController::class, 'approveLoadout'])->name('loadouts.approve');
    Route::post('/loadouts/{loadout}/reject', [AdminController::class, 'rejectLoadout'])->name('loadouts.reject');
    Route::post('/reactions/{reaction}/approve', [AdminController::class, 'approveReaction'])->name('reactions.approve');
    Route::post('/reactions/{reaction}/reject', [AdminController::class, 'rejectReaction'])->name('reactions.reject');
    Route::post('/users/{user}/role', [AdminController::class, 'changeRole'])->name('users.role');
});
