<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Google2FA;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Google 2FA landing page
Route::get('/google2fa/index', [Google2FA::class, 'index'])
    ->name('google2fa.index')
    ->middleware(['auth']);

// Authenticate Google 2FA
Route::post('/google2fa/authenticate', [Google2FA::class, 'authenticate'])
    ->name('google2fa.authenticate')
    ->middleware(['auth']);