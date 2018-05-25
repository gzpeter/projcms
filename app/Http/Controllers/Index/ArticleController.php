<?php
namespace App\Http\Controllers\Index;

use App\Article;
use App\ArticleRead;
use App\Http\Controllers\Controller;
use App\Segment;
use App\TagWord;
use App\MessageSend;
use App\Collection;
use App\SuggestRead;
use DB;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/api/index/article/list",
     *     summary="前端用户-获取文章列表",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         name="page",
     *         type="integer",
     *         description="页数",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="user_tag",
     *         type="string",
     *         description="用户标签",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="level",
     *         type="integer",
     *         description="用户知识等级",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="content_tag",
     *         type="integer",
     *         description="内容标签（默认为空）",
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
     *                      property="lists",
     *                      type="object",
     *                      @SWG\Property(
     *                          property="id",
     *                          type="number",
     *                          description="文章ID"
     *                      ),
     *                      @SWG\Property(
     *                          property="type",
     *                          type="number",
     *                          description="文章类型"
     *                      ),
     *                      @SWG\Property(
     *                          property="status",
     *                          type="number",
     *                          description="文章状态"
     *                      ),
     *                      @SWG\Property(
     *                          property="title",
     *                          type="string",
     *                          description="文章标题"
     *                      ),
     *                      @SWG\Property(
     *                          property="author",
     *                          type="string",
     *                          description="文章作者"
     *                      ),
     *                         @SWG\Property(
     *                          property="read_num",
     *                          type="number",
     *                          description="文章阅读数"
     *                      ),
     *                      @SWG\Property(
     *                          property="collect_num",
     *                          type="number",
     *                          description="文章收藏数"
     *                      ),
     *                      @SWG\Property(
     *                          property="update_time",
     *                          type="string",
     *                          description="文章更新时间"
     *                      ),
     *                  ),
     *                  @SWG\Property(
     *                      property="pages",
     *                      type="number",
     *                      description="总页数"
     *                  ),
     *                  @SWG\Property(
     *                      property="is_next",
     *                      type="number",
     *                      description="是否显示下一等级内容（0 不显示；1 显示，默认为0）"
     *                  ),
     *                  @SWG\Property(
     *                      property="total",
     *                      type="number",
     *                      description="当前分类总条数"
     *                  ),
     *                   @SWG\Property(
     *                      property="message",
     *                      type="number",
     *                      description="未读消息数"
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
            'msg'    => '查询成功！',
            'data'   => [
                'pages' => 1,
                'lists' => [],
                'is_next' => 0,
                'total' => 0,
                'message'=> 0,
            ],
        ];
        //检测需要的参数是否传递
        $param = ['page','level','user_tag'];
        foreach ($param as $key => $value) {
            if (!$request->input($value)) {
                $ret['status'] = -1;
                $ret['msg']    = $value . '参数错误！';
                return $ret;
            }
        }
        if (!$this->user_id) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }
        //定义变量
        $page         = $request->input('page', 1);
        $level        = $request->input('level');
        $user_tag     = $request->input('user_tag','');
        $content_tag  = $request->input('content_tag','');
        $type = $request->input('type', '');

        $size    = 10; //默认查询条数
        $offsize = ($page - 1) * $size;
        //查询条件
        $orwhere = [];
        $where = [];
        if($user_tag){
            $where[] = ['content_level_id','=',$level];
            $tag_arr = explode(';',$user_tag);
            foreach ($tag_arr as $key => $value) {
                $orwhere[] = ['user_tag_word','like','%;'.$value.'%'];
            }
        }
        
        if ($type) {
            $where[] = ['type', '=', $type];
        }

        if($content_tag){
            $where[] = ['content_tag_word','like','%;'.$content_tag.'%'];
        }
        $where[] = ['status','=',1];
        //需要查询的列
        $field = ['id', 'type', 'status', 'title', 'author', 'read_num', 'collect_num', 'last_update_time'];
        //获取符合条件数据
        if($orwhere){
            $lists = Article::where($where)->where(function($query) use ($orwhere){
                foreach ($orwhere as $key => $value) {
                    $query->orwhere($value[0],$value[1],$value[2]);
                }
            })->select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        }else{
            $lists = Article::where($where)->select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        }
        //消息通知
        $message = MessageSend::where([['user_id','=',$this->user_id],['is_read','=',0]])->count();

        if (!$lists) {
            return $ret;
        }
        //获取符合条件总条数
        $count = Article::where($where)->count();

        $read_where = [
            ['user_id','=',$this->user_id],
            ['article_level','=',$level],
        ];
        $read_num = ArticleRead::where($read_where)->count();

        $is_next = 0;
        if($read_num >= 40){
            $is_next = 1;
        }

        //获取总页数
        $pages = ceil($count / $size);
        //格式化数据
        foreach ($lists as $key => $list) {
            $lists[$key]['update_time'] = date("Y-m-d H:i:s", $list['last_update_time']);
            unset($lists[$key]['last_update_time']);
        }
        $ret['data']['lists'] = $lists;
        $ret['data']['pages'] = $pages;
        $ret['data']['is_next'] = $is_next;
        $ret['data']['total'] = $count;
        $ret['data']['message'] = $message;
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/index/article/info",
     *     summary="前端用户-获取文章内容",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         description="文章ID",
     *         in="query",
     *         name="article_id",
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
     *                      property="info",
     *                      type="object",
     *                      @SWG\Property(
     *                          property="id",
     *                            type="number",
     *                            description="文章ID"
     *                      ),
     *                         @SWG\Property(
     *                             property="type",
     *                           type="number",
     *                           description="文章类型"
     *                         ),
     *                         @SWG\Property(
     *                             property="status",
     *                           type="number",
     *                           description="文章状态"
     *                         ),
     *                         @SWG\Property(
     *                             property="title",
     *                           type="string",
     *                           description="文章标题"
     *                         ),
     *                         @SWG\Property(
     *                             property="author",
     *                           type="string",
     *                           description="文章作者"
     *                         ),
     *                         @SWG\Property(
     *                             property="read_num",
     *                           type="number",
     *                           description="文章阅读数"
     *                         ),
     *                         @SWG\Property(
     *                             property="collect_num",
     *                           type="number",
     *                           description="文章收藏数"
     *                         ),
     *                         @SWG\Property(
     *                             property="update_time",
     *                           type="string",
     *                           description="文章更新时间"
     *                         ),
     *                         @SWG\Property(
     *                             property="is_collection",
     *                           type="number",
     *                           description="是否收藏过（0：未收藏；1：已收藏）"
     *                         ),
     *                  ),
     *                  @SWG\Property(
     *                      property="lists",
     *                      type="object",
     *                      @SWG\Property(
     *                          property="id",
     *                          type="number",
     *                          description="segmentID"
     *                      ),
     *                      @SWG\Property(
     *                          property="content",
     *                          type="string",
     *                          description="segment内容"
     *                      ),
     *                      @SWG\Property(
     *                          property="type",
     *                          type="number",
     *                          description="segment类型"
     *                      ),
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
            'msg'    => '查询成功！',
            'data'   => [
                'info'  => [],
                'lists' => [],
            ],
        ];
        //检测需要的参数是否传递
        $param = ['article_id'];
        foreach ($param as $key => $value) {
            if (!$request->input($value)) {
                $ret['status'] = -1;
                $ret['msg']    = $value . '参数错误！';
                return $ret;
            }
        }
        if (!$this->user_id) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }
        //定义变量
        $article_id = $request->input('article_id');

        $field = ['id', 'type', 'status', 'title', 'author', 'read_num','content_level_id','collect_num', 'last_update_time'];
        $info  = Article::select($field)->find($article_id);    //获取文章内容
        if (!$info) {
            return $ret;
        }
        $info['last_update_time'] = date("Y-m-d H:i:s", $info['last_update_time']);
        $lists                    = Segment::where('article_id', '=', $article_id)->select(['id', 'type', 'content'])->get();
        $ret['data']['info']      = $info;
        $ret['data']['lists']     = $lists;
        //查询收藏记录
        $ret['data']['info']['is_collection'] = 0;    //未收藏
        $coll = Collection::where([['user_id','=',$this->user_id],['article_id','=',$article_id]])->select(['id'])->first();
        if($coll){
            $ret['data']['info']['is_collection'] = 1;    //已收藏
        }
        DB::beginTransaction();
        try {
            Article::where('id', '=', $article_id)->increment('read_num', 1);
            $info = ArticleRead::where([['user_id', '=', $this->user_id], ['article_id', '=', $article_id]])->select(['id'])->first();
            if ($info) {
                ArticleRead::where('id', '=', $info['id'])->increment('read_num', 1);
            } else {
                $time                     = time();
                $data['user_id']          = $this->user_id;
                $data['article_id']       = $article_id;
                $data['article_level']    = $info['content_level_id'];
                $data['last_update_time'] = $time;
                $data['created_at']       = $time;
                DB::table('article_read')->insertGetId($data);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $ret['status'] = -2;
            $ret['msg']    = '更新阅读数失败！' . $e->getMessage();
        }
        return $ret;
    }


    /**
     * @SWG\Post(
     *     path="/api/index/article/content_tag",
     *     summary="前端用户-获取内容标签列表",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         name="user_tag",
     *         type="string",
     *         description="用户标签",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="level",
     *         type="integer",
     *         description="用户知识等级",
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
     *                      property="lists",
     *                      type="object",
     *                  ),
     *                  @SWG\Property(
     *                      property="pages",
     *                      type="number",
     *                      description="总页数"
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function content_tag(Request $request)
    {
        //定义返回格式数组
        $ret = [
            'status' => 0,
            'msg'    => '查询成功！',
            'data'   => [
                'lists' => [],
            ],
        ];
        //检测需要的参数是否传递
        $param = ['level','user_tag'];
        foreach ($param as $key => $value) {
            if (!$request->input($value)) {
                $ret['status'] = -1;
                $ret['msg']    = $value . '参数错误！';
                return $ret;
            }
        }
        if (!$this->user_id) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }
        $type = $request->input('type', '');
        if ($type) {
            $where[] = ['type', '=', $type];
        }

        $user_tag = $request->input('user_tag');
        $level = $request->input('level',1);
        $orwhere = [];
        if($user_tag){
            $where[] = ['content_level_id','=',$level];
            $tag_arr = explode(';',$user_tag);
            foreach ($tag_arr as $key => $value) {
                $orwhere[] = ['user_tag_word','like','%;'.$value.'%'];
            }
        }
        //需要查询的列
        $field = ['id', 'type', 'status', 'title', 'author', 'read_num', 'collect_num', 'last_update_time'];
        //获取符合条件数据

        $content_lists = Article::where($where)->orwhere($orwhere)->select(['content_tag_word'])->get();
        $c_list = [];
        if($content_lists){
            if($content_lists){
                foreach ($content_lists as $key => $value) {
                    $c_list = array_unique(array_merge($c_list,array_filter(explode(';',$value['content_tag_word']))));
                }
            }
        }
        $ret['data']['lists'] = $c_list;
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/index/article/new_article",
     *     summary="前端用户-获取新知识列表",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         name="page",
     *         type="integer",
     *         description="页数",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="user_tag",
     *         type="string",
     *         description="用户标签",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="level",
     *         type="integer",
     *         description="用户知识等级",
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
     *                      property="lists",
     *                      type="object",
     *                      @SWG\Property(
     *                          property="id",
     *                          type="number",
     *                          description="文章ID"
     *                      ),
     *                      @SWG\Property(
     *                          property="type",
     *                          type="number",
     *                          description="文章类型"
     *                      ),
     *                      @SWG\Property(
     *                          property="status",
     *                          type="number",
     *                          description="文章状态"
     *                      ),
     *                      @SWG\Property(
     *                          property="title",
     *                          type="string",
     *                          description="文章标题"
     *                      ),
     *                      @SWG\Property(
     *                          property="author",
     *                          type="string",
     *                          description="文章作者"
     *                      ),
     *                         @SWG\Property(
     *                          property="read_num",
     *                          type="number",
     *                          description="文章阅读数"
     *                      ),
     *                      @SWG\Property(
     *                          property="collect_num",
     *                          type="number",
     *                          description="文章收藏数"
     *                      ),
     *                      @SWG\Property(
     *                          property="update_time",
     *                          type="string",
     *                          description="文章更新时间"
     *                      ),
     *                  ),
     *                  @SWG\Property(
     *                      property="pages",
     *                      type="number",
     *                      description="总页数"
     *                  ),
     *                  @SWG\Property(
     *                      property="total",
     *                      type="number",
     *                      description="当前分类总条数"
     *                  ),
     *              ),
     *          )
     *     ),
     * )
     */
    public function new_article(Request $request)
    {
        //定义返回格式数组
        $ret = [
            'status' => 0,
            'msg'    => '查询成功！',
            'data'   => [
                'lists' => [],
                'pages' => 1,
                'total' => 0,
            ],
        ];

        //检测需要的参数是否传递
        $param = ['level','user_tag'];
        foreach ($param as $key => $value) {
            if (!$request->input($value)) {
                $ret['status'] = -1;
                $ret['msg']    = $value . '参数错误！';
                return $ret;
            }
        }
        if (!$this->user_id) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }

        $where[] = ['status','=',1];
        $level = $request->input('level');
        $user_tag = $request->input('user_tag');
        $orwhere = [];
        $where = [];
        if($user_tag){
            $where[] = ['content_level_id','<=',$level];
            $tag_arr = explode(';',$user_tag);
            foreach ($tag_arr as $key => $value) {
                $orwhere[] = ['user_tag_word','like','%;'.$value.'%'];
            }
        }
        $where[] = [];
        $size    = 10; //默认查询条数
        $offsize = ($page - 1) * $size;
        //需要查询的列
        $field = ['id', 'type', 'status', 'title', 'author', 'read_num', 'collect_num', 'last_update_time'];
        $begin_time = strtotime(data("Y-m-d")." 23:59:59") - (7 * 86400);
        $end_time = strtotime(data("Y-m-d")." 23:59:59");
        //获取符合条件数据
        if($orwhere){
            $lists = Article::where($where)->where(function($query) use ($orwhere){
                foreach ($orwhere as $key => $value) {
                    $query->orwhere($value[0],$value[1],$value[2]);
                }
            })->whereBetween('state_update_time',[$begin_time,$end_time])->select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        }else{
            $lists = Article::where($where)->whereBetween('state_update_time',[$begin_time,$end_time])->select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        }
        
        if (!$lists) {
            return $ret;
        }
        //获取符合条件总条数
        $count = Article::where($where)->count();

        $pages = ceil($count/$size);
        $ret['data']['lists'] = $lists;
        $ret['data']['pages'] = $pages;
        $ret['data']['total'] = $count;
        return $ret;
    }


    /**
     * @SWG\Post(
     *     path="/api/index/article/isUnderstand",
     *     summary="前端用户-看懂看不懂",
     *     produces={"application/json"},
     *     tags={"Article"},
     *     @SWG\Parameter(
     *         name="article_id",
     *         type="string",
     *         description="文章ID",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="level",
     *         type="integer",
     *         description="用户知识等级",
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
     *          )
     *     ),
     * )
     */
    public function isUnderstand(Request $request)
    {
        $ret = [
            'status' => 0,
            'msg' => '操作成功！',
            'data' =>'',
        ];
        $param = ['level','article_id'];
        foreach ($param as $key => $value) {
            if(!$request->input($value)){
                $ret['status'] = -1;
                $ret['msg'] = $value.'参数为空！';
            }
        }

        if(!$this->user_id){
            $ret['status'] = -1000;
            $ret['msg'] = '用户未登录！';
            return $ret;
        }
        $level = $request->input('level'); // 用户本身知识等级
        $article_id = $request->input('article_id');

        $article_info = Article::select(['content_level_id'])->find($article_id);
        if(!$article_info){
            $ret['status'] = -2;
            $ret['msg'] = '该文章不存在或已下线';
            return $ret;
        }

        if($article_info['content_level_id'] - $level != 1){
            return $ret;
        }

        $where = [
            ['user_id','=' ,$this->user_id],
            ['content_id','=',$article_id],
            ['content_level_id','=',$article_info['content_level_id']],
        ];
        $info = SuggestRead::where($where)->select(['id'])->first();
        $time = time();
        if($info){
            $res = SuggestRead::where('id',$info['id'])->update(['status'=>$status,'last_update_time'=>$time]);
        }else{
            $data = [
                'user_id' => $this->user_id,
                'content_level_id' =>$article_info['content_level_id'],
                'content_id' =>$article_id,
                'status' =>$status,
                'last_update_time' =>$time,
                'create_at' =>$time,
            ];
            $res = DB::table('suggest_read_feedback')->insertGetId($data);
        }

        $lists = SuggestRead::where([['user_id','=',$this->user_id],['content_level_id','=',$level+1]])->select(['status'])->orderBy('last_update_time','desc')->limit(10)->get();
        $num = 0;
        foreach ($lists as $key => $value) {
            if($value['status'] == 1){
                $num++;
            }
        }

        if($num > 7 ){
            //去升级
        }

        if(!$res){
            $ret['status'] = -3;
            $ret['msg'] = '操作失败！';
        }
        return $ret;
    }

}