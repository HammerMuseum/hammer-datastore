<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('video/id/{id}', 'VideoController@getById');
Route::get ('video/all', 'VideoController@getAllVideos');

Route::group(['middleware' => 'auth:api'], function() {
    Route::post('video/create', 'ApiController@create');
    Route::put('video/update/{id}', 'ApiController@update');
    Route::delete('video/delete/{id}', 'ApiController@delete');
});

Route::post('login', 'Auth\LoginController@login');
