<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [LoginController::class, 'loginsubmit'])->name('login');
Route::post('/register', [RegisterController::class, 'registersubmit']);
Route::post('/resend-otp', [RegisterController::class, 'resendotp']);
Route::post('/verify-otp', [RegisterController::class, 'verifyotp']);
Route::post('/add-tier', [RegisterController::class, 'addTier']);
Route::get('/all-users', [RegisterController::class, 'getUsers']);
Route::get('/active-banks', [UserController::class, 'getAllBanks'])->middleware('auth:api');
Route::get('/active-bank', [UserController::class, 'getAllBanks']);
Route::get('/get-users', [UserController::class, 'getUserss']);
Route::group(['prefix' => 'user'], function () {
    Route::middleware('auth:api')->group(function(){
        Route::get('/dashboard', [UserController::class, 'dashboard']);
        Route::get('/get-user', [UserController::class, 'getUser']);
        Route::get('/active-banks', [UserController::class, 'getAllBanks']);
        Route::post('/account-check', [UserController::class, 'getAccName']);
        Route::post('/groove-account-check', [UserController::class, 'getGrooveAccName']);
        
        //Transfer
        Route::post('/internal-transfer', [UserController::class, 'submitIntTransfer']);
        Route::post('/complete-int-transfer', [UserController::class, 'completeIntTransfer']);
        Route::post('/external-transfer', [UserController::class, 'submitExTransfer']);
        Route::post('/complete-ex-transfer', [UserController::class, 'completeExTransfer']);
        
        //Beneficiary
        Route::post('/add-beneficiary', [UserController::class, 'addBeneficiary']);
        Route::get('/get-beneficiaries', [UserController::class, 'getBeneficiaries']);

        //reset password
        Route::post('/send-reset-password', [UserController::class, 'sendResetPassword']);
        Route::post('/verify-token', [UserController::class, 'verifyToken']);
        Route::post('/reset-password', [UserController::class, 'resetPassword']);
    });
});

