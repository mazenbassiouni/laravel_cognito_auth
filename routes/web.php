<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Auth\LoginController;

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

Route::middleware('guest')->group(function (){
    Route::get('/', function () {
        return view('welcome');
    })->name('login');
    
    
    // Facebook Provider
    Route::get('facebook/auth/redirect', function () {
        return Socialite::driver('facebook')->redirect();
    })->name('facebook-login');
    
    // Facebook Callback
    Route::get('/facebook/auth/callback', [LoginController::class, 'facebookLogin']);
    
    // Google Provider
    Route::get('google/auth/redirect', function () {
        return Socialite::driver('google')->redirect();
    })->name('google-login');
    
    // Google Callback
    Route::get('/google/auth/callback', [LoginController::class, 'googleLogin']);

    // Cognito Provider
    Route::get('cognito/auth/redirect', function () {
        return Socialite::driver('cognito')->redirect();
    })->name('cognito-login');
    
    // Cognito Callback
    Route::get('/cognito/auth/callback', [LoginController::class, 'cognitoLogin']);
});


Route::middleware('auth')->group(function (){
    Route::middleware('auth')->get('home', function (){
        return view('show-token');
    });
    
    Route::post('logout', function(){
        auth()->logout();
        return redirect('/');
    })->name('logout');
});

