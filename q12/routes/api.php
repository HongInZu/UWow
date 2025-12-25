<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::prefix('articles')->group(function () {
    // List articles with search, sort, and filter
    Route::get('/', [ArticleController::class, 'index']);

    // Create article
    Route::post('/', [ArticleController::class, 'store']);

    // Show specific article
    Route::get('/{article}', [ArticleController::class, 'show']);

    // Update article
    Route::put('/{article}', [ArticleController::class, 'update']);
    Route::post('/{article}', [ArticleController::class, 'update']); // For form-data with image

    // Delete article
    Route::delete('/{article}', [ArticleController::class, 'destroy']);

    // Set active/inactive
    Route::patch('/{article}/set-active', [ArticleController::class, 'setActive']);
    Route::patch('/{article}/set-inactive', [ArticleController::class, 'setInactive']);
    Route::patch('/{article}/toggle-status', [ArticleController::class, 'toggleStatus']);

    // Update ordering
    Route::post('/update-order', [ArticleController::class, 'updateOrder']);

    // Bulk operations
    Route::post('/bulk-delete', [ArticleController::class, 'bulkDelete']);
    Route::post('/bulk-update-status', [ArticleController::class, 'bulkUpdateStatus']);
});
