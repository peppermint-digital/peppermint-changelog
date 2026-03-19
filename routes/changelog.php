<?php

use Illuminate\Support\Facades\Route;
use Peppermint\Changelog\Controllers\AdminChangelogController;
use Peppermint\Changelog\Controllers\ChangelogController;

// Public changelog routes
Route::middleware(config('changelog.routes.public.middleware', ['web', 'auth']))
    ->prefix(config('changelog.routes.public.prefix', 'changelogs'))
    ->name(config('changelog.routes.public.name_prefix', 'changelogs.'))
    ->group(function () {
        Route::get('/', [ChangelogController::class, 'index'])->name('index');
        Route::get('/{slug}', [ChangelogController::class, 'show'])->name('show');
        Route::post('/{slug}/read', [ChangelogController::class, 'markAsRead'])->name('read');
        Route::post('/{slug}/dismiss-modal', [ChangelogController::class, 'dismissModal'])->name('dismiss-modal');
    });

// Admin changelog routes
Route::middleware(config('changelog.routes.admin.middleware', ['web', 'auth']))
    ->prefix(config('changelog.routes.admin.prefix', 'admin/changelogs'))
    ->name(config('changelog.routes.admin.name_prefix', 'admin.changelogs.'))
    ->group(function () {
        Route::get('/', [AdminChangelogController::class, 'index'])->name('index');
        Route::get('/create', [AdminChangelogController::class, 'create'])->name('create');
        Route::post('/', [AdminChangelogController::class, 'store'])->name('store');
        Route::get('/{slug}', [AdminChangelogController::class, 'show'])->name('show');
        Route::get('/{slug}/edit', [AdminChangelogController::class, 'edit'])->name('edit');
        Route::put('/{slug}', [AdminChangelogController::class, 'update'])->name('update');
        Route::delete('/{slug}', [AdminChangelogController::class, 'destroy'])->name('destroy');
    });
