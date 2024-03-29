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


// Cache for 10 minutes.
Route::get('videos/{id}', 'VideoController@show')->middleware('cacheResponse:600');

// Cache all these routes for 1 hour.
Route::group(['middleware' => 'cacheResponse:3600'], function () {
    Route::get('videos', 'VideoController@index');
    Route::get('videos/{id}/related', 'VideoController@showRelated');
    Route::get('playlists', 'PlaylistController@index');
    Route::get('playlists/{name}', 'PlaylistController@show');
    Route::get('search', 'SearchController@search');
    Route::get('search/term', 'SearchController@term');
    Route::get('search/aggregate/{term}', 'SearchController@aggregate');
    Route::get('videos/{id}/transcript', 'TranscriptController@show');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::delete('videos/{id}', 'ApiController@delete');
});

Route::post('login', ['as' => 'login', 'uses' => 'Auth\LoginController@login'])->name('login');

Route::fallback(function () {
    return response()->json(['message' => 'Not found'], 404);
})->name('api.fallback.404');
