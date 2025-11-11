<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::post('/shopify/products', [ProductController::class, 'store']);
Route::get('/health', fn () => response()->json(['ok' => true]));

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
