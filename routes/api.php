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

Route::get('videos/{id}', 'VideoController@getById');
Route::get('videos/{id}/transcript', 'VideoController@getTranscript');
Route::get('videos', 'VideoController@getAllVideos');
Route::get('search', 'SearchController@search');

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('videos', 'ApiController@create');
    Route::put('videos/{id}', 'ApiController@update');
    Route::delete('videos/{id}', 'ApiController@delete');
});

Route::post('login', ['as' => 'login', 'uses' => 'Auth\LoginController@login'])->name('login');
