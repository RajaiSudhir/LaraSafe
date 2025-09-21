<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});
Route::get('/dashboard', function () {
    return Inertia::render('Home');
});

Route::get('/login', function() {
    return Inertia::render('Auth/Login');
});
Route::get('/manage-projects', function(){
    return Inertia::render('Projects/Projects');
});