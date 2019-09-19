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

    public function index() {
        $start = microtime(true);
        try {
            $insert_data = [
                'uid' => 10,
                'shop_id' => 1
            ];
//            Db::startTrans();
//            $order_id = Db::table('mp_order')->insertGetId($insert_data);
//            $data = [
//                'id' => $order_id
//            ];
//            Db::table('mp_order_detail')->insertGetId($data);
//            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            die($e->getMessage());
        }
        $end = microtime(true);
        echo $end-$start;

    }

    protected function asyn_tpl_send($data) {
        $param = http_build_query($data);
        $fp = fsockopen('ssl://' . $this->domain, 443, $errno, $errstr, 20);
        if (!$fp){
            echo 'error fsockopen';
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