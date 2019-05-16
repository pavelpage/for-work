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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'image'],function (){
    Route::post('store-files', 'ApiImageController@storeFile')->name('api.store-files');
    Route::get('store-from-remote-source', 'ApiImageController@saveFileFromUrl')->name('api.store-from-remote-source');
    Route::post('store-from-base64', 'ApiImageController@saveFileFromBase64')->name('api.store-from-base64');
});