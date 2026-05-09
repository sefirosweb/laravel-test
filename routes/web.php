<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => 'Login stub (host has no auth scaffolding)')->name('login');
