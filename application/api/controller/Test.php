<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/17
 * Time: 10:46
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
class Test extends Common {

    public function echart() {
        return $this->fetch();
    }

    public function index() {
        $start = microtime(true);
        try {
            $this->asyn_tpl_send(['order_id'=>4]);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        $end = microtime(true);
        echo $end-$start;
        echo ' 秒 ';

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