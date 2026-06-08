<?php

use Illuminate\Support\Facades\Route;

// Retorna apenas um estado básico em vez de uma página pesada
Route::get('/', function () {
    return response()->json([
        'name' => 'Bisca API',
        'status' => 'Running',
        'version' => '1.0.0'
    ]);
});
