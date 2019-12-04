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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::get('video/id/{id}', 'VideoController@getById');
Route::get ('video/all', 'VideoController@getAllVideos');

Route::post('video/create', 'ApiController@create');
Route::put('video/update/{id}', 'ApiController@update');
Route::delete('video/delete/{id}', 'ApiController@delete');