<?php

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
//用于测试
Route::get('ceshi/ceshi', 'CeshiController@ceshi');

//生成swagger
Route::get('swagger/doc', 'SwaggerController@doc');


//标签部分
Route::prefix('tags')->group(function(){
	//标签列表
	Route::post('list','TagsController@lists');
});

//前台用户访问路由
Route::group(['prefix'=>'index','namespace'=>'Index'],function(){
	//文章列表
	Route::post('article/list', 'ArticleController@lists');
	//文章详情
	Route::post('article/info', 'ArticleController@info');
	//
	Route::post('article/content_tag','ArticleController@content_tag');
	//搜索
	Route::post('search/list','SearchController@lists');
	//收藏列表
	Route::post('collection/list', 'CollectionController@lists');
	//添加收藏
	Route::post('collection/add', 'CollectionController@add');
	//删除收藏
	Route::post('collection/delete', 'CollectionController@delete');
	//消息列表
    Route::post('message/list','MessageController@lists');
    //消息详情
    Route::post('message/info', 'MessageController@info');
    //
    Route::post('article/isUnderstand','ArticleController@isUnderstand');
});

//后台用户访问路由
Route::group(['prefix'=>'admin','namespace'=>'Admin'],function(){
	//文章列表
	Route::post('article/list', 'ArticleController@lists');
	//文章详情
	Route::post('article/info', 'ArticleController@info');
	//添加文章
	Route::post('article/add', 'ArticleController@add');
	//检测标签是否有匹配
	Route::post('article/check', 'ArticleController@check');
	//编辑文章
	Route::post('article/edit', 'ArticleController@edit');
	//更改文章上下线状态
	Route::post('article/updateStatus', 'ArticleController@updateStatus');
	//消息列表
    Route::post('message/list','MessageController@lists');
    //消息详情
    Route::post('message/info', 'MessageController@info');
    //发送消息
    Route::post('message/send', 'MessageController@send');
});
