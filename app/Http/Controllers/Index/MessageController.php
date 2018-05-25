<?php
namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use App\Message;
use App\MessageSend;
use DB;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    /**
     * @SWG\Post(
     *     path="/api/index/message/list",
     *     summary="前端用户-获取消息列表",
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
                'pages' => 1,
                'lists' => [],
                'total' => 0,
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
        $where = [
            ['user_id','=',$this->user_id],
        ];
        //需要查询的列
        $field   = ['id', 'title', 'sender', 'created_at'];
        $size    = 10;
        $offsize = ($page - 1) * $size;

        $lists = MessageSend::where($where)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        //$lists = Message::select($field)->offset($offsize)->limit($size)->orderBy('id', 'desc')->get();
        foreach ($lists as $key => $value) {
            $lists[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
        }
        $count                = MessageSend::where($where)->count();
        $pages                = ceil($count / $size);
        $ret['data']['pages'] = $pages;
        $ret['data']['lists'] = $lists;
        $ret['data']['total'] = $count;
        return $ret;
    }

    /**
     * @SWG\Post(
     *     path="/api/index/message/info",
     *     summary="前端用户-获取消息内容",
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

        $info = MessageSend::where([['user_id','=',$this->user_id],['message_id','=',$message_id]])->select(['id','is_read'])->first();
        if(!$info){
            $ret['status'] = -2;
            $ret['msg'] = '没有该消息通知！';
            return $ret;
        }
        //需要查询的列
        $field = [
            'title',
            'content',
            'link',
            'sender',
            'last_update_time',
        ];
        $message_info                = Message::where($where)->select($field)->first();
        $ret['dara']['info'] = $message_info;
        //如果该消息是未读状态 则改为已读状态
        if(!$info['is_read']){
            MessageSend::where([['user_id','=',$this->user_id],['message_id','=',$message_id]])->update(['is_read'=>1]);
        }
        return $ret;
    }
}
