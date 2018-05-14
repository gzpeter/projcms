<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Message;
use App\MessageSend;

/**
 * 站内消息类
 */
class MessageController extends Controller 
{
	/**
	 * 获取站内消息列表
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
 				'lists' => [],
 			]
 		];
 		//检测需要的参数是否传递
 		$param = ['user_id','token','page'];
 		foreach ($param as $key => $value) {
 			if(!$request->has($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = $value.'参数错误！';
 				return $ret;
 			}
 		}
 		$user_id = $request->input('user_id');
 		$token = $request->input('token');
 		$page = $request->input('page',1);

 		//查询条件
 		/*$where = [
 			['message_send.user_id','=',$user_id]
 		];*/
 		//需要查询的列
 		$field = ['id','title','msender','created_at',];
 		$size = 10;
 		$offsize = ($page - 1) * $size;
 		$lists = Message::select($field)->offset($offsize)->limit($size)->orderBy('id','desc')->get();
 		$ret['data']['lists'] = $lists;
 		return $ret;
 	}

 	/**
 	 * 获取站内消息内容
 	 * @param  string $value [description]
 	 * @return [type]        [description]
 	 */
 	public function info(Request $request)
 	{
 		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '查询成功！',
 			'data' => ''
 		];
 		//检测需要的参数是否传递
 		$param = ['user_id','token','message_id'];
 		foreach ($param as $key => $value) {
 			if(!$request->has($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = $value.'参数错误！';
 				return $ret;
 			}
 		}
 		$user_id = $request->input('user_id');
 		$token = $request->input('token');
 		$message_id = $request->input('message_id');
 		//查询条件
 		$where =[
 			['id','=',$message_id],
 		];

 		//需要查询的列
 		$field = [
 			'title',
 			'content',
 			'link',
 			'sender',
 			'last_update_time',
 		];
 		$info = Message::where($where)->select($field)->first();
 		$ret['dara']['info'] = $info;
 		return $ret;
 	}

 	/**
 	 * 发送站内消息
 	 * @param  Request $request [description]
 	 * @return [type]           [description]
 	 */
 	public function send(Request $request)
 	{
 		//定义返回格式数组
 		$ret = [
 			'status' => 0,
 			'msg' => '添加成功！',
 			'data' => ''
 		];
 		//检测需要的参数是否传递
 		$param = ['user_id','token','title','content','link','receiver_ids','sender'];
 		foreach ($param as $key => $value) {
 			if(!$request->has($value)){
 				$ret['status'] = -1;
 				$ret['msg'] = '参数错误！';
 				return $ret;
 			}
 		}
 		$title = $request->input('title');
 		$content = $request->input('content');
 		$link = $request->input('link');
 		$receiver_ids = $request->input('receiver_ids');
 		$sender = $request->input('sender');
 		$time = time();	
 		$send_data = [];

 		$data = ['title'=>$title,'content'=>$content,'link'=>$link,'sender'=>$sender,'last_update_time'=>$time,'created_at'=>$time];

 		$ids = explode(",",$receiver_ids);
 		DB::beginTransaction();
 		try {
 			$message_id = DB::table('message')->insertGetId($data);
 			foreach ($ids as $id) {
	 			$send_data[] = ['user_id'=>$id,'message_id'=>$message_id,'send_status'=>1,'last_update_time'=>$time,'created_at'=>$time];
	 		}
	 		DB::table('message_send')->insert($send_data);
 			DB::commit();
 		} catch (Exception $e) {
 			DB::rollBack();
 			$ret['status'] = -1;
 			$ret['msg'] = '添加失败！' . $e->getMessage();
 		}
 		return $ret;
 	}
} 
?>
