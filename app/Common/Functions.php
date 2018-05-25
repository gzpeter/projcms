<?php
namespace App\Common;

/**
 *  公共函数
 */
class Functions
{

    /**
     * post请求
     * @param  [type] $url   [请求地址url]
     * @param  array  $param [请求参数]
     * @return [type]        [description]
     */
    public function postUrl($url, $username,$password, $data)
    {
        if (!$url || !$data) {
            return false;
        }
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $data = curl_exec($ch); //运行curl
        curl_close($ch);
        return $data;
    }

}
