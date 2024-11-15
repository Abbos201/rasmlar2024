<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;

Route::get('/', [MessageController::class, 'index']);
Route::post('/image/upload', [MessageController::class, 'uploadImage'])->name('image.upload');
