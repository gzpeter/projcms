<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Message;
use DB;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    /**
     * @SWG\Post(
     *     path="/api/admin/message/list",
     *     summary="管理后台-获取消息列表",
     *     produces={"application/json"},
     *     tags={"Message"},
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
     *                            description="消息ID"
     *                      ),
     *                      @SWG\Property(
     *                          property="title",
     *                          type="string",
     *                          description="消息标题"
     *                      ),
     *                      @SWG\Property(
     *                           property="sender",
     *                           type="string",
     *                           description="消息发送者"
     *                      ),
     *                      @SWG\Property(
     *                           property="created_at",
     *                           type="string",
     *                           description="消息发送时间"
     *                      ),
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
            if (!$request->has($value)) {
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

        $page = $request->input('page', 1);

        //查询条件
        /*$where = [
        ['message_send.user_id','=',$user_id]
        ];*/
        //需要查询的列
        $field   = ['id', 'title', 'sender', 'created_at'];
        $size    = 10;
        $offsize = ($page - 1) * $size;

        $lists = Message::select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        if(!$lists){
            return $ret;
        }
        foreach ($lists as $key => $value) {
            $lists[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
        }
        $count                = Message::count();
        $pages                = ceil($count / $size);
        $ret['data']['pages'] = $pages;
        $ret['data']['lists'] = $lists;
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/admin/message/info",
     *     summary="管理后台-获取消息内容",
     *     produces={"application/json"},
     *     tags={"Message"},
     *     @SWG\Parameter(
     *         description="消息ID",
     *         in="query",
     *         name="message_id",
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
     *                         @SWG\Property(
     *                             property="title",
     *                           type="number",
     *                          description="消息title"
     *                         ),
     *                         @SWG\Property(
     *                             property="content",
     *                           type="number",
     *                           description="消息内容"
     *                         ),
     *                         @SWG\Property(
     *                             property="link",
     *                           type="string",
     *                           description="消息活动链接"
     *                         ),
     *                         @SWG\Property(
     *                             property="sender",
     *                           type="string",
     *                           description="消息发送者"
     *                         ),
     *                         @SWG\Property(
     *                           property="created_at",
     *                         type="number",
     *                         description="消息创建时间"
     *                       ),
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
            'data'   => '',
        ];
        //检测需要的参数是否传递
        $param = ['message_id'];
        foreach ($param as $key => $value) {
            if (!$request->has($value)) {
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

        $message_id = $request->input('message_id');
        //查询条件
        $where = [
            ['id', '=', $message_id],
        ];

        //需要查询的列
        $field = [
            'title',
            'content',
            'link',
            'sender',
            'last_update_time',
        ];
        $info                = Message::where($where)->select($field)->first();
        if(!$info){
            return $ret;
        }
        $ret['dara']['info'] = $info;
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/admin/message/send",
     *     summary="管理后台-发送站内消息",
     *     produces={"application/json"},
     *     tags={"Message"},
     *     @SWG\Parameter(
     *         description="消息标题",
     *         in="query",
     *         name="title",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="消息发送者",
     *         in="query",
     *         name="sender",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="消息内容",
     *         in="query",
     *         name="content",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="消息活动链接",
     *         in="query",
     *         name="link",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         description="接受的用户ids",
     *         in="query",
     *         name="receiver_ids",
     *         required=true,
     *         type="string",
     *         description="用户id字符串，多个用户英文逗号隔开,例如1,2,3,4",
     *     ),
     *     @SWG\Response (
     *          response="200",
     *          description="发送成功！",
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
    public function send(Request $request)
    {
        //定义返回格式数组
        $ret = [
            'status' => 0,
            'msg'    => '添加成功！',
            'data'   => '',
        ];
        //检测需要的参数是否传递
        $param = ['title', 'content', 'link', 'receiver_ids', 'sender'];
        foreach ($param as $key => $value) {
            if (!$request->has($value)) {
                $ret['status'] = -1;
                $ret['msg']    = '参数错误！';
                return $ret;
            }
        }

        if (!$this->user_id) {
            $ret['status'] = -1000;
            $ret['msg']    = '用户未登录！';
            return $ret;
        }

        $title        = $request->input('title');
        $content      = $request->input('content');
        $link         = $request->input('link');
        $receiver_ids = $request->input('receiver_ids');
        $sender       = $request->input('sender');
        $time         = time();
        $send_data    = [];

        $data = ['title' => $title, 'content' => $content, 'link' => $link, 'sender' => $sender, 'last_update_time' => $time, 'created_at' => $time];
        $ids = explode(",", $receiver_ids);
        DB::beginTransaction();
        try {
            $message_id = DB::table('message')->insertGetId($data);
            foreach ($ids as $id) {
                $send_data[] = ['user_id' => $id, 'message_id' => $message_id, 'send_status' => 1, 'last_update_time' => $time, 'created_at' => $time];
            }
            DB::table('message_send')->insert($send_data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $ret['status'] = -1;
            $ret['msg']    = '添加失败！' . $e->getMessage();
        }
        return $ret;
    }
}
