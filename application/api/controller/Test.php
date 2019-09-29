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
use my\Sendsms;

class Test extends Common {

    public function index() {
        $start = microtime(true);
        echo strtotime('+10 days',strtotime(date('Y-m-d 00:00:00'))) . '<hr>';
        try {
//            $whereToday = [
//                ['create_time','>=',strtotime(date('Y-m-d 00:00:00'))],
//                ['create_time','<',strtotime(date('Y-m-d 00:00:00'))]
//            ];
//            $sms = new Sendsms();
//            $param = [
//                'tel' => '13102163019',
//                'param' => [
//                    'req_title' => '海河锦鲤文创大赛',
//                    'work_title' => '好作品',
//                    'org' => '华为工厂'
//                ]
//            ];
//            $res = $sms->send($param,'SMS_174992129');
//            if($res->Code !== 'OK') {
//                $this->smslog($this->cmd,$res->Message);
//            }else {
//
//            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
//        halt($whereToday);

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