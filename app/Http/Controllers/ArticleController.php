<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Article;
use App\Segment;
use App\ArticleRead;
use Swagger\Annotations as SWG;


class ArticleController extends Controller{


	/**
     * @SWG\Post(
     *     path="/api/article/list",
     *     summary="获取文章列表",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         name="user_id",
     *         type="integer",
     *         description="用户id",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="token",
	 *         type="string",
     *         description="用户token",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
	 *         type="integer",
     *         description="页数",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Response (
     *          response="200",
     *          description="查询成功！",
     *          @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="status",
	 *                  type="number",
	 *                  description="状态码"
	 *              ),
	 *              @SWG\Property(
	 *                  property="msg",
	 *                  type="string",
	 *                  description="提示信息"
	 *              ),
	 *              @SWG\Property(
	 *                  property="data",
	 *                  type="object",
	 *                  @SWG\Property(
	 *                  	property="lists",
	 *                  	type="object",
	 *                  	@SWG\Property(
     *                  	    property="id",
     *                    	    type="number",
     *                    	    description="文章ID"
     *                      ),
     *                 	    @SWG\Property(
     *                 	    	property="type",
     *                       	type="number",
     *                       	description="文章类型"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="status",
     *                       	type="number",
     *                       	description="文章状态"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="title",
     *                       	type="string",
     *                       	description="文章标题"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="author",
     *                       	type="string",
     *                       	description="文章作者"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="read_num",
     *                       	type="number",
     *                       	description="文章阅读数"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="collect_num",
     *                       	type="number",
     *                       	description="文章收藏数"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="update_time",
     *                       	type="string",
     *                       	description="文章更新时间"
     *                 	    ),
	 *                  ),
	 *                  @SWG\Property(
	 *                  	property="pages",
	 *                  	type="number",
	 *                  	description="总页数"
	 *                  ),
	 *              ),
	 *          )
     *     ),
     * )
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
     * @SWG\Post(
     *     path="/api/article/info",
     *     summary="获取文章内容",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         description="用户id",
     *         in="query",
     *         name="user_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="用户token",
     *         in="query",
     *         name="token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="文章ID",
     *         in="query",
     *         name="article_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="文章状态",
     *         in="query",
     *         name="status",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response (
     *          response="200",
     *          description="查询成功！",
     *          @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="status",
	 *                  type="number",
	 *                  description="状态码"
	 *              ),
	 *              @SWG\Property(
	 *                  property="msg",
	 *                  type="string",
	 *                  description="提示信息"
	 *              ),
	 *              @SWG\Property(
	 *                  property="data",
	 *                  type="object",
	 *                  @SWG\Property(
	 *                  	property="info",
	 *                  	type="object",
	 *                  	@SWG\Property(
     *                  	    property="id",
     *                    	    type="number",
     *                    	    description="文章ID"
     *                      ),
     *                 	    @SWG\Property(
     *                 	    	property="type",
     *                       	type="number",
     *                       	description="文章类型"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="status",
     *                       	type="number",
     *                       	description="文章状态"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="title",
     *                       	type="string",
     *                       	description="文章标题"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="author",
     *                       	type="string",
     *                       	description="文章作者"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="read_num",
     *                       	type="number",
     *                       	description="文章阅读数"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="collect_num",
     *                       	type="number",
     *                       	description="文章收藏数"
     *                 	    ),
     *                 	    @SWG\Property(
     *                 	    	property="update_time",
     *                       	type="string",
     *                       	description="文章更新时间"
     *                 	    ),
	 *                  ),
	 *                  @SWG\Property(
	 *                  	property="lists",
	 *                  	type="object",
	 *                  	@SWG\Property(
	 *                  		property="id",
	 *                  		type="number",
	 *                  		description="segmentID"
	 *                  	),
	 *                  	@SWG\Property(
	 *                  		property="content",
	 *                  		type="string",
	 *                  		description="segment内容"
	 *                  	),
	 *                  	@SWG\Property(
	 *                  		property="type",
	 *                  		type="number",
	 *                  		description="segment类型"
	 *                  	),
	 *                  ),
	 *              ),
	 *          )
	 *     )
     * )
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
		$lists = Segment::where('article_id','=',$article_id)->select(['id','type','content'])->get();
		$ret['data']['info'] = $info;
		$ret['data']['lists'] = $lists;
		if($status == 1){
			DB::beginTransaction();
			try {
				Article::where('id','=',$article_id)->increment('read_num',1);
				$info = ArticleRead::where([['user_id','=',$user_id],['article_id','=',$article_id]])->select(['id'])->first();
				if($info){
					ArticleRead::where('id','=',$info['id'])->increment('read_num',1);
				}else{
					$time = time();
					$data['user_id'] = $user_id;
					$data['article_id'] = $article_id;
					$data['last_update_time'] = $time;
					$data['created_at'] = $time;
					DB::table('table')->insertGetId($data);
				}
			} catch (\Exception $e) {
				DB::rollBack();
				$ret['status'] = -2;
				$ret['msg'] = '更新阅读数失败！'.$e->getMessage();
			}
		}
		return $ret;
	}

	/**
     * @SWG\Post(
     *     path="/api/article/add",
     *     summary="添加文章内容",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         description="用户id",
     *         in="query",
     *         name="user_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="用户token",
     *         in="query",
     *         name="token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="文章类型",
     *         in="query",
     *         name="type",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="文章作者",
     *         in="query",
     *         name="author",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="文章导语",
     *         in="query",
     *         name="summary",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="文章内容",
     *         in="query",
     *         name="segment",
     *         required=true,
     *         type="string",
     *         description="一个包含type和content的json数组对象,例如[{'type':1,'content':'dasdasd'}]",
     *     ),
     *     @SWG\Response (
     *          response="200",
     *          description="添加成功！",
     *          @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="status",
	 *                  type="number",
	 *                  description="状态码"
	 *              ),
	 *              @SWG\Property(
	 *                  property="msg",
	 *                  type="string",
	 *                  description="提示信息"
	 *              ),
	 *              @SWG\Property(
	 *                  property="data",
	 *                  type="string",
	 *              ),
	 *          )
	 *     )
     * )
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
     * @SWG\Post(
     *     path="/api/article/edit",
     *     summary="编辑文章内容",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         description="用户id",
     *         in="query",
     *         name="user_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="用户token",
     *         in="query",
     *         name="token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="操作的文章ID",
     *         in="query",
     *         name="article_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="文章类型",
     *         in="query",
     *         name="type",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="文章作者",
     *         in="query",
     *         name="author",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="文章导语",
     *         in="query",
     *         name="summary",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="文章内容",
     *         in="query",
     *         name="segment",
     *         required=true,
     *         type="string",
     *         description="一个包含type和content的json数组对象,例如[{'type':1,'content':'dasdasd'}]",
     *     ),
     *     @SWG\Response (
     *          response="200",
     *          description="操作成功！",
     *          @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="status",
	 *                  type="number",
	 *                  description="状态码"
	 *              ),
	 *              @SWG\Property(
	 *                  property="msg",
	 *                  type="string",
	 *                  description="提示信息"
	 *              ),
	 *              @SWG\Property(
	 *                  property="data",
	 *                  type="string",
	 *              ),
	 *          )
	 *     )
     * )
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
			$up_data['user_id'] = $user_id;
			$up_data['article_id'] = $article_id;
			$up_data['last_update_time'] = $time;
			$up_data['created_at'] = $time;
			DB::table('update_istory')->insertGetId($up_data);
			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();
			$ret['status'] = -10;
			$ret['msg'] = '编辑失败！'.$e->getMessage();
		}
		return $ret;
	}

	/**
     * @SWG\Post(
     *     path="/api/article/down_or_online",
     *     summary="编辑文章状态（上/下线）",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         description="用户id",
     *         in="query",
     *         name="user_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="用户token",
     *         in="query",
     *         name="token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="操作的文章ID",
     *         in="query",
     *         name="article_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         description="想要操作的状态，1上线，2下线",
     *         in="query",
     *         name="status",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response (
     *          response="200",
     *          description="操作成功！",
     *          @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="status",
	 *                  type="number",
	 *                  description="状态码"
	 *              ),
	 *              @SWG\Property(
	 *                  property="msg",
	 *                  type="string",
	 *                  description="提示信息"
	 *              ),
	 *              @SWG\Property(
	 *                  property="data",
	 *                  type="string",
	 *              ),
	 *          )
	 *     )
     * )
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
