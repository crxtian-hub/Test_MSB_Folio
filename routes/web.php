<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\DemoPageController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\InfoPageController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;

if (filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOL)) {
    Route::get('/', fn () => redirect()->route('demo.home'))->name('home');
    Route::get('/info', fn () => redirect()->route('demo.info'))->name('info');
    Route::get('/projects/{project}', fn (string $project) => redirect()->route('demo.projects.show', ['slug' => $project]))->name('projects.show');
} else {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/info', [PageController::class, 'info'])->name('info');
    Route::get('/projects/{project:slug}', [PageController::class, 'show'])->name('projects.show');
}

Route::prefix('demo')
    ->name('demo.')
    ->group(function () {
        Route::get('/', [DemoPageController::class, 'home'])->name('home');
        Route::get('/info', [DemoPageController::class, 'info'])->name('info');
        Route::get('/projects/{slug}', [DemoPageController::class, 'show'])->name('projects.show');
    });

// CRUD protetto da auth (solo admin)

Route::prefix('admin')
->middleware(['auth'])
->name('admin.')
->group(function () {
    Route::get('/', fn () => redirect()->route('home'))->name('dashboard');
    Route::post('projects/reorder', [AdminProjectController::class, 'reorder'])->name('projects.reorder');
    Route::resource('projects', AdminProjectController::class)->except(['show', 'index']);
    Route::post('projects/{project}/photos', [PhotoController::class, 'store'])->name('photos.store');
    Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::get('info', [InfoPageController::class, 'edit'])->name('info.edit');
    Route::put('info', [InfoPageController::class, 'update'])->name('info.update');
});


//!da mettere in blade.php
// <form action="{{ route('admin.photos.store') }}" method="POST" enctype="multipart/form-data">
//     @csrf
//     <input type="file" name="image" required>
//     <button type="submit">Carica</button>
// </form>
