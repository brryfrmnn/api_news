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
Route::group(['prefix'=>'post', 'middleware' => 'cors'], function($api){
	Route::get('/', 'PostController@index');
    Route::post('/', 'PostController@store');
    Route::get('/show', 'PostController@show');
    Route::patch('/', 'PostController@update');
    Route::patch('/status', 'PostController@updateStatus');
    Route::post('/featured', 'PostController@setFeatured');
    Route::post('/featured/unset', 'PostController@unsetFeatured');
    Route::get('/featured', 'PostController@getFeatured');
    Route::delete('/{id}', 'PostController@destroy');
});
Route::group(['prefix' => 'comment', 'middleware' => 'cors'], function () {
    Route::get('/', 'CommentController@index');
    Route::post('/', 'CommentController@store');
    Route::delete('/', 'CommentController@destroy');
    Route::get('/show', 'CommentController@show');
    Route::patch('/', 'CommentController@update');
});

