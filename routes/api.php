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

Route::group(['prefix' => 'image', 'middleware' => 'auth:api'],function (){
    Route::post('store-files', 'ApiImageController@storeFile')->name('api.store-files');
    Route::post('store-from-remote-source', 'ApiImageController@saveFileFromUrl')->name('api.store-from-remote-source');
    Route::post('store-from-base64', 'ApiImageController@saveFileFromBase64')->name('api.store-from-base64');
    Route::post('create-resize', 'ApiImageController@createResize')->name('api.create-resize');
    Route::get('resizes', 'ApiImageController@getImageResizes')->name('api.get-image-resizes');
    Route::delete('resize', 'ApiImageController@deleteImageResize')->name('api.delete-resize');
    Route::delete('all-resizes', 'ApiImageController@deleteAllImageResizes')->name('api.delete-all-resizes');
});