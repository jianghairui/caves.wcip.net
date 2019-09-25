<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/17
 * Time: 10:46
 */
namespace app\api\controller;

use EasyWeChat\Factory;
use think\Controller;
use think\Db;
class Test extends Common {

    public function index() {
        $start = microtime(true);
        try {
            $uid = 1;
            //formid是否存在
            $whereFormid = [
                ['uid','=',$uid],
                ['create_time','>',time()-(3600*24*7-3600*2)]
            ];
            $list = Db::table('mp_formid')->where($whereFormid)->select();
            if(!$list) {
//                $this->msglog($this->cmd,'not found formid ,uid:' . $uid);
                die('not found formid');
            }



//            $image_url = 'http://qiniu.wcip.net/public/enter-plat.jpg';
//            $result = $this->msgSecCheck($image_url);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
//        $formid_exist['create_time'] = date('Y-m-d H:i:s');
        foreach ($list as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        }
        halt($list);

        $end = microtime(true);
        echo $end-$start;
        echo ' 秒 ';
    }


    /*微信图片敏感内容检测*/
    public function test() {
        $filelist = [
            'a.jpg',
            'b.jpg',
            'c.jpg',
            'd.jpg',
            'e.jpg',
            'f.jpg',
            'g.jpg',
            'h.jpg',
            'i.jpg',
            'j.jpg',
        ];

        foreach ($filelist as $v) {
            $img = 'tmp/' . $v;
            $img = file_get_contents($img);
            $filePath = '/dev/shm/tmp1.png';
            file_put_contents($filePath, $img);
            $obj = new \CURLFile(realpath($filePath));
            $obj->setMimeType("image/jpeg");
            $file['media'] = $obj;

            $app = Factory::payment($this->mp_config);
            $access_token = $app->access_token;
            $token = $access_token->getToken();
            $url = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=' . $token['access_token'];
            $info = curl_post_data($url,$file);

            $result = json_decode($info,true);
            echo $v;
            dump($result);
            echo '<hr>';
        }
    }

    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    private function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }





    protected function asyn_tpl_send($data) {
        $param = http_build_query($data);
        $fp = @fsockopen('ssl://' . $this->domain, 443, $errno, $errstr, 1);
        if (!$fp){
            $this->log('asyn_tpl_send','error fsockopen:' . $this->domain);
        }else{
            stream_set_blocking($fp,0);
            $http = "GET /api/message/fundingOrder?".$param." HTTP/1.1\r\n";
            $http .= "Host: ".$this->domain."\r\n";
            $http .= "Connection: Close\r\n\r\n";
            fwrite($fp,$http);
            usleep(1000);
            fclose($fp);
        }
    }





}