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
Route::group(['prefix' => 'category', 'middleware' => 'cors'], function () {
	Route::get('/', 'CategoryController@index');
	Route::post('/', 'CategoryController@store');
	Route::delete('/', 'CategoryController@destroy');
	Route::get('/show', 'CategoryController@show');
	Route::patch('/', 'CategoryController@update');
});
