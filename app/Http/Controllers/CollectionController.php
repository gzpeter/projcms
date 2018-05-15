<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Collection;
use App\Article;

/**
 * 收藏表逻辑处理
 */
class CollectionController extends Controller{

	/**
	 * 收藏列表
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function lists(Request $request)
	{
		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '查询成功！',
 			'data' =>[
 				'pages' => 0,
 				'lists' => [],
 			],
 		];
 		//检测需要的参数是否传递
 		$param = ['user_id','token','page'];
 		foreach ($param as $key => $value) {
 			if(!$request->input($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = $value.'参数错误！';
 				return $ret;
 			}
 		}
 		//定义变量
 		$user_id = $request->input('user_id');
 		$token = $request->input('token');
 		$page = $request->input('page',1);
 		$where = [
 			['user_id','=',$user_id],
 		];

 		$size = 10;
 		$offsize = ($page -1 ) * $size;
 		$colle_lists = Collection::where($where)->select(['article_id'])->offset($offsize)->limit($size)->orderBy('id','desc')->get();
 		$lists = [];
 		foreach ($colle_lists as $key => $colle) {
 			$info = Article::select(['id','title','author','read_num','collect_num','state_update_time'])->find($colle['article_id']);
 			$info['state_update_time'] = date('Y-m-d H:i:s',$info['state_update_time']);
 			$lists[] = $info;
 		}
 		$count = Collection::where($where)->count();
 		$pages = ceil($count/$size);
 		$ret['data']['pages'] = $pages;
 		$ret['data']['lists'] = $lists;
 		return $ret;
	}

	/**
	 * 添加收藏
	 * @param Request $request [description]
	 */
	public function add(Request $request)
	{
		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '操作成功！',
 			'data' =>'',
 		];

 		//检测需要的参数是否传递
 		$param = ['user_id','token','article_id'];
 		foreach ($param as $key => $value) {
 			if(!$request->input($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = $value.'参数错误！';
 				return $ret;
 			}
 		}
 		//定义变量
 		$user_id = $request->input('user_id');
 		$token = $request->input('token');
 		$article_id = $request->input('article_id');
		$where = [
			['user_id','=',$user_id],
			['article_id','=',$article_id],
		];
 		$info = Collection::where($where)->select(['id'])->first();
 		if($info){
 			$ret['status'] = -2;
 			$ret['msg'] = '已经收藏过该文章！';
 			return $ret;
 		}
 		$time = time();
		$data['user_id'] = $user_id;
		$data['article_id'] = $article_id;
		$data['last_update_time'] = $time;
		$data['created_at'] = $time;

		DB::beginTransaction();
		try {
			Article::where('id','=',$article_id)->increment('collect_num',1);
			DB::table('article_collection')->insertGetId($data);
			DB::commit();			
		} catch (\Exception $e) {
			DB::rollBack();
 			$ret['status'] = -10;
 			$ret['msg'] = '收藏失败！'.$e->getMessage();
		}
 		return $ret;
	}

	/**
	 * 删除收藏
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function delete(Request $request)
	{
		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '操作成功！',
 			'data' =>'',
 		];

 		//检测需要的参数是否传递
 		$param = ['user_id','token','article_id'];
 		foreach ($param as $key => $value) {
 			if(!$request->input($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = $value.'参数错误！';
 				return $ret;
 			}
 		}
 		//定义变量
 		$user_id = $request->input('user_id');
 		$token = $request->input('token');
 		$article_id = $request->input('article_id');
		$where = [
			['user_id','=',$user_id],
			['article_id','=',$article_id],
		];
 		$info = Collection::where($where)->select(['id'])->first();
 		if(!$info){
 			$ret['status'] = -2;
 			$ret['msg'] = '您未收藏过该文章！';
 			return $ret;
 		}
 		DB::beginTransaction();
		try {
			Article::where('id','=',$article_id)->decrement('collect_num',1);
			Collection::where($where)->delete();
			DB::commit();			
		} catch (\Exception $e) {
			DB::rollBack();
 			$ret['status'] = -10;
 			$ret['msg'] = '取消收藏失败！'.$e->getMessage();
		}
 		return $ret;
	}
}
