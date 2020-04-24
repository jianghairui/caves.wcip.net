<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/28
 * Time: 11:49
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
class Wxpay extends Controller {

    public function orderPay() {
        $pay_order_sn = create_unique_number('app');
        $appid = config('wx_appid');
        $mch_id = config('mch_id');
        $total_price = 0.02;
        $arr = [
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => randomkeys(32),
            'sign_type' => 'MD5',
            'body' => 'app支付测试',
            'out_trade_no' => $pay_order_sn,
            'total_fee' => floatval($total_price)*100,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/api/wxpay/orderNotify",
            'trade_type' => 'JSAPI',
            'openid' => 'oNEu_s8TWzpK6p6-kUFnFHaS1GiI'
        ];
        $arr['sign'] = getSign($arr);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $result = curl_post_data($url, array2xml($arr));
        $result = xml2array($result);
        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ajax($result['err_code_des'],-1);
        }
        try {
            $prepay['appId'] = $arr['appid'];
            $prepay['timeStamp'] = strval(time());
            $prepay['nonceStr'] = $arr['nonce_str'];
            $prepay['signType'] = $arr['sign_type'];
            $prepay['package'] = 'prepay_id=' . $result['prepay_id'];
            $prepay['paySign'] = getSign($prepay);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        halt($prepay);
        return ajax($prepay);

    }

    public function orderNotify() {
//将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = xml2array($xml);
        $this->paylog('okoko',var_export($data,true));
        exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
    }



    //支付回调日志
    protected function paylog($cmd,$str) {
        $file= ROOT_PATH . '/log/appnotify.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }




}