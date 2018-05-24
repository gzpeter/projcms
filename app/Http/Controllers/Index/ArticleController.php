<?php
namespace App\Http\Controllers\Index;

use App\Article;
use App\ArticleRead;
use App\Http\Controllers\Controller;
use App\Segment;
use App\TagWord;
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
    public function lists(Request $request)
    {
        //定义返回格式数组
        $ret = [
            'status' => 0,
            'msg'    => '查询成功！',
            'data'   => [
                'pages' => 0,
                'lists' => [],
            ],
        ];
        //检测需要的参数是否传递
        $param = ['page','level'];
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
        
        if (!$lists) {
            return $ret;
        }
        //获取符合条件总条数
        $count = Article::where($where)->count();
        //获取总页数
        $pages = ceil($count / $size);
        //格式化数据
        foreach ($lists as $key => $list) {
            $lists[$key]['update_time'] = date("Y-m-d H:i:s", $list['last_update_time']);
            unset($lists[$key]['last_update_time']);
        }
        $ret['data']['lists'] = $lists;
        $ret['data']['pages'] = $pages;
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

        $field = ['id', 'type', 'status', 'title', 'author', 'read_num', 'collect_num', 'last_update_time'];
        $info  = Article::select($field)->find($article_id);
        if (!$info) {
            return $ret;
        }
        $info['last_update_time'] = date("Y-m-d H:i:s", $info['last_update_time']);
        $lists                    = Segment::where('article_id', '=', $article_id)->select(['id', 'type', 'content'])->get();
        $ret['data']['info']      = $info;
        $ret['data']['lists']     = $lists;
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
}