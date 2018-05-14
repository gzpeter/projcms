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

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::post('message/list','MessageController@lists');
Route::post('message/info','MessageController@info');
Route::post('message/send','MessageController@send');

//文章模块
Route::post('article/list','ArticleController@lists');
Route::post('article/info','ArticleController@info');
Route::post('article/add','ArticleController@add');
Route::post('article/edit','ArticleController@edit');
Route::post('article/down_or_online','ArticleController@updateStatus');





