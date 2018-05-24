<?php

namespace App\Http\Controllers;

use App\MedicalTag;
use Illuminate\Http\Request;

class TagsController extends Controller
{
	 /**
     * @SWG\Post(
     *     path="/api/tags/list",
     *     summary="获取标签列表",
     *     produces={"application/json"},
     *     tags={"Tags"},
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
     *                      @SWG\Property(
     *                          property="id",
     *                          type="number",
     *                          description="标签ID"
     *                      ),
     *                      @SWG\Property(
     *                           property="name",
     *                           type="string",
     *                           description="标签名称"
     *                      ),
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
                'lists' => [],
            ],
        ];

        /* $param = ['parent_id'];
        foreach ($param as $key => $value) {
            if (!$request->input($value)) {
                $ret['status'] = -1;
                $ret['msg']    = $value . '参数不正确！';
                return $ret;
            }
        }*/

        if (!$this->user_id) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }

        //$parent_id = $request->input('parent_id',0);

        /*$where = [
            ['parent_id', '=', $parent_id],
        ];*/

        $lists = MedicalTag::select(['id','parent_id','name','is_leaf'])->get();
        $ret['data']['lists'] = $this->getTree($lists);
        return $ret;
    }
    
}
