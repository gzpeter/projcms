<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Article;

/**
 * 文章表逻辑处理
 */
class ArticleController extends Controller
{

	/**
	 * 获取文章列表
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
		$type = $request->input('type',0);
		$time_period = $request->input('time_period','');
		$status = $request->input('status',0);
		$search_query = $request->input('search_query','');
		//$tags = $request->input('tags','');
		$size = 10;	//默认查询条数
		$offsize = ($page - 1) * $size;
		//查询条件
		$where = [];
		if($type){
			$where[] = ['type','=',$type];
		}
		if($time_period){
			$arr = explode(',',$time_period);
			if($arr[0] > $arr[1] || count($arr) < 2){
				$ret['status'] = -2;
				$ret['msg'] = '时间格式有误！';
				return $ret;
			}
			$where[] = ['state_update_time','>=',strtotime($arr[0])];
			$where[] = ['state_update_time','<=',strtotime($arr[1])];
		}
		if($status){
			$where[] = ['status','=',$status];
		}
		if($search_query){
			$where[] = ['type','=',trim($search_query)];
		}

		//需要查询的列
		$field = ['id','type','status','title','author','read_num','collect_num','last_update_time'];
		//获取符合条件数据
		$lists = Article::where($where)->select($field)->offset($offsize)->limit($size)->orderBy('id','desc')->get();
		//获取符合条件总条数
		$count = Article::where($where)->count();
		//获取总页数
		$pages = ceil($count/$size);
		//格式化数据
		foreach ($lists as $key => $list) {
			$lists[$key]['update_time'] = date("Y-m-d H:i:s",$list['last_update_time']);
			unset($lists[$key]['last_update_time']);
		}
		$ret['data']['lists'] = $lists;
		$ret['data']['pages'] = $pages;
		return $ret;
	}

	/**
	 * 获取文章内容
	 * @param Request $request [description]
	 */
	public function info(Request $request)
	{
		//定义返回格式数组
		$ret = [
 			'status' => 0,
 			'msg' => '查询成功！',
 			'data' =>[
 				'info' => [],
 				'lists' => [],
 			]
 		];
 		//检测需要的参数是否传递
 		$param = ['user_id','token','article_id','status'];
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
 		$status = $request->input('status',1);

 		$field = ['id','type','status','title','author','read_num','collect_num','last_update_time'];
 		$info = Article::select($field)->find($article_id);
 		$info['last_update_time'] = date("Y-m-d H:i:s",$info['last_update_time']);
 		/*
 		$lists = Segment::where('article_id','=',$article_id)->get();
 		$ret['data']['info'] = $info;
 		$ret['data']['lists'] = $lists;
 		*/
 		$lists = Article::find($article_id)->segmentList; 
 		$ret['data']['info'] = $info;
 		$ret['data']['lists'] = $lists;
 		if($status == 1){
 			Article::where('id','=',$article_id)->increment('read_num',1);
 		}
 		return $ret;
	}

	/**
	 * 添加文章
	 * @param Request $request [description]
	 */
	public function add(Request $request)
	{
		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '添加成功！',
 			'data' =>'',
 		];

 		//检测需要的参数是否传递
 		$param = ['user_id','token','type','title','author','summary','segment'];
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
 		$type = $request->input('type');
 		$title = $request->input('title');
 		$author = $request->input('author');
 		$summary = $request->input('summary');
 		$segment = $request->input('segment');
 		$status = $request->input('status',0);

 		$time = time();
 		//article表需要的数据
 		$data['title'] = $title;
 		$data['summary'] = $summary;
 		$data['author'] = $author;
 		$data['type'] = $type;
 		$data['status'] = $status;
 		$data['state_update_time'] = $time;
 		$data['last_update_time'] = $time;
		$data['created_at'] = $time;

		//segment表需要的数据
		$segments = json_decode($segment,true);	
		
 		DB::beginTransaction();
 		try {
 			//获取插入的文章的ID
 			$article_id = DB::table('article')->insertGetId($data);
 			//如果有
 			if($segments){
 				$s_data = [];
 				if($segments){
					foreach ($segments as $key => $segment) {
						$s_data[] = ['article_id'=>$article_id,'content'=>$segment['content'],'type'=>$segment['type'],'last_update_time'=>$time,'created_at'=>$time];
					}
				}
 				DB::table('segment')->insert($s_data);
 			}
 			DB::commit();
 		} catch (\Exception $e) {
 			DB::rollBack();
 			$ret['status'] = -10;
 			$ret['msg'] = '添加失败！'.$e->getMessage();
 		}
 		return $ret;
	}

	/**
	 * 文章编辑
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function edit(Request $request)
	{
		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '编辑成功！',
 			'data' =>'',
 		];
 		//检测需要的参数是否传递
 		$param = ['user_id','token','type','title','author','summary','segment','article_id'];
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
 		$type = $request->input('type');
 		$title = $request->input('title');
 		$author = $request->input('author');
 		$summary = $request->input('summary');
 		$segment = $request->input('segment');
 		$status = $request->input('status',0);
 		$article_id = $request->input('article_id');

 		$time = time();
 		//article表需要的数据
 		$data['title'] = $title;
 		$data['summary'] = $summary;
 		$data['author'] = $author;
 		$data['type'] = $type;
 		$data['status'] = $status;
 		$data['state_update_time'] = $time;
 		$data['last_update_time'] = $time;

		//segment表需要的数据
		$segments = json_decode($segment,true);	
		
 		DB::beginTransaction();
 		try {
 			DB::table('article')->update($data);
 			//如果有
 			if($segments){
 				DB::table('segment')->where('article_id',$article_id)->delete();
 				$s_data = [];
 				if($segments){
					foreach ($segments as $key => $segment) {
						$s_data[] = ['article_id'=>$article_id,'content'=>$segment['content'],'type'=>$segment['type'],'last_update_time'=>$time,'created_at'=>$time];
					}
				}
 				DB::table('segment')->insert($s_data);
 			}else{
 				DB::table('segment')->where('article_id',$article_id)->delete();
 			}
 			DB::commit();
 		} catch (\Exception $e) {
 			DB::rollBack();
 			$ret['status'] = -10;
 			$ret['msg'] = '编辑失败！'.$e->getMessage();
 		}
 		return $ret;
	}

	/**
	 * 文章列表 对文章的上/下线操作
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function updateStatus(Request $request)
	{
		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '操作成功！',
 			'data' =>'',
 		];

 		//检测需要的参数是否传递
 		$param = ['user_id','token','article_id','status'];
 		foreach ($param as $key => $value) {
 			if(!$request->input($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = '参数错误！';
 				return $ret;
 			}
 		}
 		//定义变量
 		$user_id = $request->input('user_id');
 		$token = $request->input('token');
 		$article_id = $request->input('article_id');
 		$status = $request->input('status');
 		//获取当前文章状态
 		$info = Article::select('status')->find($article_id);
 		if(!in_array($status,[1,2])){
 			$ret['status'] = -3;
			$ret['msg'] = '状态取值有误！';
			return $ret;
 		}
 		if($status == 1){	//上线
 			if($info['status'] != 1){
 				$new_status = 1;
 			}else{
 				$ret['status'] = -2;
 				$ret['msg'] = '该文章已上线！';
 				return $ret;
 			}
 		}else{	//下线
 			if($info['status'] == 1){
 				$new_status = 2;
 			}else{
 				$ret['status'] = -2;
 				$ret['msg'] = '该文章未上线！';
 				return $ret;
 			}
 		}

 		$data = [
 			'status' => $new_status,
 			'state_update_time' => time(),
 		];
 		$res = Article::where('id',$article_id)->update($data);
 		if(!$res){
 			$ret['status'] = -2;
			$ret['msg'] = '操作失败！';
 		}
 		return $ret;
	}
}
?>