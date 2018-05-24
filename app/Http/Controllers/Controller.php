<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Common\Functions;

use Swagger\Annotations as SWG;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $user_id = 0;
    protected $user_name = '';

    function __construct(Request $request)
    {
        $this->check_token($request);
    }

    /**
     * 验证token
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function check_token(Request $request){
    	//$token = $request->header('token');
    	$token = $request->header('_token');
        echo $token;
    	$functions = new Functions;
    	$url = 'www.com/ceshi.php';
    	$post_data = [
    		'token' => '2',
    	];
    	$json_str = $functions->postUrl($url,$post_data);
    	$res = json_decode($json_str,true);
        $this->user_id = $res['user_id'] ?? 0;
        $this->user_name = $res['user_name'] ?? '';
    }


    public function getTree($data, $pId = 0)
    {
        $tree = [];
        foreach ($data as $k => $v) {
            if ($v['parent_id'] == $pId) {
                //父亲找到儿子
                $v['children'] = $this->getTree($data, $v['id']);
                $tree[] = $v;
                //unset($data[$k]);
            }
        }
        return $tree;
    }
}