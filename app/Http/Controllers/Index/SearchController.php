<?php

namespace App\Http\Controllers\Index;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Article;

class SearchController extends Controller
{

	/**
     * @SWG\Post(
     *     path="/api/index/search/list",
     *     summary="前端用户-获取搜索结果列表",
     *     produces={"application/json"},
     *     tags={"Search"},
     *     @SWG\Parameter(
     *         name="page",
     *         type="integer",
     *         description="页数(默认为1)",
     *         required=true,
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="search_key",
     *         type="string",
     *         description="搜索关键词",
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
        $param = ['page','search_key'];
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

        $page = $request->input('page',1);
        $search_key = $request->input('search_key','');
        $size    = 10; //默认查询条数
        $offsize = ($page - 1) * $size;
        if(!$search_key){
        	return $ret;
        }
        $where = [
        	['title','like','%'.$search_key.'%'],
        ];
        $orwhere = [
        	['medical_tag_word','like','%;'.$search_key.'%'],
        ];

        //需要查询的列
        $field = ['id', 'type', 'status', 'title', 'author', 'read_num', 'collect_num', 'last_update_time'];
        //获取符合条件数据
        $lists = Article::where($where)->orwhere($orwhere)->select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();

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
}