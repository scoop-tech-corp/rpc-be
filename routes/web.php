<?php

use App\Events\MessageCreated;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyUserandPasswordController;

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

Route::get('/', function () {
    return view('welcome');
});


// Route::get('/userRegistertration', function () {
//     return view('userRegistertration');
// });


// Route::post('/userRegistertration', function () {

//     $message = request()->message;
//     $type = request()->type;
//     event(new MessageCreated($message,$type));
// });


// Auth::routes([
// 'verify' => true

// ]);

Route::resource('/posts/{id}', \App\Http\Controllers\VerifyUserandPasswordController::class);
Route::post('/posts', '\App\Http\Controllers\VerifyUserandPasswordController@store')->name('reset.password.store');
// Route::post('/holidays', '\App\Http\Controllers\StaffController@getAllHolidaysDate');
