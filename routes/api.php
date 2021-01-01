<?php

use App\Http\Controllers\SavingsController;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function (){
    Route::post('login', [ApiController::class, 'authenticate']);
    Route::post('register', [ApiController::class, 'register']);
});

Route::group(['middleware' => ['jwt.verify']], function() {
     Route::get('get_savings', [SavingsController::class, 'get_savings']);
     Route::get('savings_balance', [SavingsController::class, 'savings_balance']);
     Route::get('dashboard', [SavingsController::class, 'dashboard']);
     Route::post('all_transactions', [SavingsController::class, 'all_transactions']);
     Route::post('save_money', [SavingsController::class, 'save_money']);
     Route::post('savings_transactions', [SavingsController::class, 'savings_transactions']);
     Route::post('withdraw', [SavingsController::class, 'withdraw']);



     
    // Route::get('logout', [ApiController::class, 'logout']);
    // Route::get('get_user', [ApiController::class, 'get_user']);
    // Route::get('products', [ProductController::class, 'index']);
    // Route::get('products/{id}', [ProductController::class, 'show']);
    // Route::post('create', [ProductController::class, 'store']);
    // Route::put('update/{product}',  [ProductController::class, 'update']);
    // Route::delete('delete/{product}',  [ProductController::class, 'destroy']);
});