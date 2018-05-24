<?php
namespace App\Http\Controllers\Index;

use App\Article;
use App\Collection;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

/**
 * 收藏表逻辑处理
 */
class CollectionController extends Controller
{

    /**
     * @SWG\Post(
     *     path="/api/index/collection/list",
     *     summary="前端用户-收藏列表",
     *     produces={"application/json"},
     *     tags={"Collection"},
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
     *                      property="lists",
     *                      type="object",
     *                      @SWG\Property(
     *                          property="id",
     *                            type="number",
     *                            description="文章ID"
     *                      ),
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
     *                             property="created_at",
     *                           type="string",
     *                           description="消息发送时间"
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
        $param = ['page'];
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
        $page  = $request->input('page', 1);
        $where = [
            ['user_id', '=', $this->user_id],
        ];

        $size        = 10;
        $offsize     = ($page - 1) * $size;
        $colle_lists = Collection::where($where)->select(['article_id'])->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        $lists       = [];
        foreach ($colle_lists as $key => $colle) {
            $info                      = Article::select(['id', 'title', 'author', 'read_num', 'collect_num', 'state_update_time'])->find($colle['article_id']);
            $info['state_update_time'] = date('Y-m-d H:i:s', $info['state_update_time']);
            $lists[]                   = $info;
        }
        $count                = Collection::where($where)->count();
        $pages                = ceil($count / $size);
        $ret['data']['pages'] = $pages;
        $ret['data']['lists'] = $lists;
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/index/collection/add",
     *     summary="前端用户-添加收藏",
     *     produces={"application/json"},
     *     tags={"Collection"},
     *     @SWG\Parameter(
     *         name="article_id",
     *         type="integer",
     *         description="文章ID",
     *         required=true,
     *         in="query",
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
     *     ),
     * )
     */
    public function add(Request $request)
    {
        //定义返回格式数组
        $ret = [
            'status' => 0,
            'msg'    => '操作成功！',
            'data'   => '',
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
        $where      = [
            ['user_id', '=', $this->user_id],
            ['article_id', '=', $article_id],
        ];
        $info = Collection::where($where)->select(['id'])->first();
        if ($info) {
            $ret['status'] = -2;
            $ret['msg']    = '已经收藏过该文章！';
            return $ret;
        }
        $time                     = time();
        $data['user_id']          = $this->user_id;
        $data['article_id']       = $article_id;
        $data['last_update_time'] = $time;
        $data['created_at']       = $time;

        DB::beginTransaction();
        try {
            Article::where('id', '=', $article_id)->increment('collect_num', 1);
            DB::table('article_collection')->insertGetId($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $ret['status'] = -10;
            $ret['msg']    = '收藏失败！' . $e->getMessage();
        }
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/index/collection/delete",
     *     summary="前端用户-删除收藏",
     *     produces={"application/json"},
     *     tags={"Collection"},
     *     @SWG\Parameter(
     *         name="article_id",
     *         type="integer",
     *         description="文章ID",
     *         required=true,
     *         in="query",
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
     *     ),
     * )
     */
    public function delete(Request $request)
    {
        //定义返回格式数组
        $ret = [
            'status' => 0,
            'msg'    => '操作成功！',
            'data'   => '',
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
        if ($this->user_id <= 0) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }

        //定义变量

        $article_id = $request->input('article_id');
        $where      = [
            ['user_id', '=', $this->user_id],
            ['article_id', '=', $article_id],
        ];
        $info = Collection::where($where)->select(['id'])->first();
        if (!$info) {
            $ret['status'] = -2;
            $ret['msg']    = '您未收藏过该文章！';
            return $ret;
        }
        DB::beginTransaction();
        try {
            Article::where('id', '=', $article_id)->decrement('collect_num', 1);
            Collection::where($where)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $ret['status'] = -10;
            $ret['msg']    = '取消收藏失败！' . $e->getMessage();
        }
        return $ret;
    }
}
