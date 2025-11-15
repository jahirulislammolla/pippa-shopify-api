<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
// optional check upload product
Route::get('/shopify/product-list', [ProductController::class, 'index']);
// store product api
Route::post('/shopify/products', [ProductController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
