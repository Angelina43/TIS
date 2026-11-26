<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::post('registration', [AuthController::class, 'registration']);
Route::post('authorization', [AuthController::class, 'authorization']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('files')->group(function () {
        Route::post('', [FileController::class, 'upload']);
        Route::get('/disk', [FileController::class, 'showFile']);
        Route::get('shared', [AuthorController::class, 'showCoAuthor']);
    });

    Route::prefix('files/{id}')->group(function () {
        Route::patch('', [FileController::class, 'update']);
        Route::delete('', [FileController::class, 'destroy']);
        Route::get('', [FileController::class, 'download']);
        Route::post('/accesses', [AuthorController::class, 'access']);
        Route::delete('/accesses', [AuthorController::class, 'remove']);
    });

    Route::get('logout', [AuthController::class, 'logout']);
});
