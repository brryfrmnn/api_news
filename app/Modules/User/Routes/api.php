<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['middleware' => 'cors'], function($api){
    Route::get('check', 'UserController@check');
    Route::post('/login', 'AuthController@postLogin');
	Route::post('/register', 'AuthController@postRegister');
});