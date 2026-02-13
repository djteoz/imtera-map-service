<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/demo.html');
});

Route::get('/demo.html', function () {
    return response()->file(public_path('demo.html'));
});

Route::get('/index.html', function () {
    return response()->file(public_path('index.html'));
});
