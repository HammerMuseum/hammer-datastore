<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/* Wrap in the following middleware to
/ make the routes cachable through Varnish.
/ Route::group(['middleware' => 'cache.headers:public;max_age=3600'], function () {
/ });
*/


// cahe for 20 seconds.
Route::get('videos/{id}', 'VideoController@show')->middleware('cacheResponse:600');

// cache all these routes for 10 minutes.
Route::group(['middleware' => 'cacheResponse:3600'], function () {
    Route::get('videos', 'VideoController@index');
    Route::get('videos/{id}/transcript', 'VideoController@showTranscript');
    Route::get('videos/{id}/related', 'VideoController@showRelated');
    Route::get('playlists', 'PlaylistController@index');
    Route::get('playlists/{name}', 'PlaylistController@show');
    Route::get('search', 'SearchController@search');
    Route::get('search/term', 'SearchController@term');
    Route::get('search/aggregate/{term}', 'SearchController@aggregate');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('videos', 'ApiController@create');
    Route::put('videos/{id}', 'ApiController@update');
    Route::delete('videos/{id}', 'ApiController@delete');
});

Route::post('login', ['as' => 'login', 'uses' => 'Auth\LoginController@login'])->name('login');

Route::fallback(function () {
    return response()->json(['message' => 'Not found'], 404);
})->name('api.fallback.404');
