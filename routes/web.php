<?php

use App\Http\Controllers\GeneralHelperTestController;
use Illuminate\Support\Facades\Route;

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

Route::get('/login', function () {
    return redirect('/');
})->name('login');


Route::get('/general_helper/view', [GeneralHelperTestController::class, 'view']);
Route::get('/general_helper/generate_data', [GeneralHelperTestController::class, 'generate_data']);
Route::get('/general_helper/download_csv', [GeneralHelperTestController::class, 'download_csv']);
