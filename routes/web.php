<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/login', [LoginController::class, 'loginsubmit'])->name('login');
Route::post('/admin-login', [LoginController::class, 'adminloginsubmit'])->name('admin.login');
Route::post('/superadmin-login', [LoginController::class, 'superadminloginsubmit'])->name('superadmin.login');
Route::post('/register', [RegisterController::class, 'registersubmit']);
Route::post('/resend-otp', [RegisterController::class, 'resendotp']);
Route::post('/verify-otp', [RegisterController::class, 'verifyotp']);
Route::post('/add-tier', [RegisterController::class, 'addTier']);
Route::get('/all-users', [RegisterController::class, 'getUsers']);
Route::get('/active-banks', [UserController::class, 'getAllBanks'])->middleware('auth:api');
Route::get('/active-bank', [UserController::class, 'getAllBanks']);
Route::get('/get-users', [UserController::class, 'getUserss']);
Route::group(['prefix' => 'user'], function () {
    Route::middleware('auth:user')->group(function(){
        Route::get('/dashboard', [UserController::class, 'dashboard']);
        Route::get('/get-user', [UserController::class, 'getUser']);
        Route::get('/active-banks', [UserController::class, 'getAllBanks']);
        Route::post('/account-check', [UserController::class, 'getAccName']);
        Route::post('/int-account-check', [UserController::class, 'getIntAccName']);
        
        //Deposit
        Route::post('/deposit', [UserController::class, 'initiateDeposit']);
        Route::post('/complete-deposit', [UserController::class, 'completeDeposit']);
    

        //Transfer
        Route::post('/internal-transfer', [UserController::class, 'submitIntTransfer']);
        Route::post('/complete-int-transfer', [UserController::class, 'completeIntTransfer']);
        Route::post('/external-transfer', [UserController::class, 'submitExTransfer']);
        Route::post('/complete-ex-transfer', [UserController::class, 'completeExTransfer']);
    
        //reset password
        Route::post('/send-reset-password', [UserController::class, 'sendResetPassword']);
        Route::post('/verify-token', [UserController::class, 'verifyToken']);
        Route::post('/reset-password', [UserController::class, 'resetPassword']);
    });
});

Route::group(['prefix' => 'admin'], function () {
    Route::middleware('auth:admin')->group(function(){
        Route::get('/all-users', [RegisterController::class, 'getUsers']);
    });
});

Route::group(['prefix' => 'superadmin'], function () {
    Route::middleware('auth:superadmin')->group(function(){
        Route::get('/all-users', [RegisterController::class, 'getUsers']);
    });
});