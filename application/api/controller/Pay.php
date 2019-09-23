<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/4
 * Time: 10:50
 */
namespace app\api\controller;
use EasyWeChat\Factory;
use think\Db;

class Pay extends Common {

    //充值支付
    public function vipPay() {
        $val['order_sn'] = input('post.order_sn');
        checkPost($val);
        $where = [
            ['order_sn','=',$val['order_sn']],
            ['status','=',0],
            ['uid','=',$this->myinfo['id']]
        ];

        $app = Factory::payment($this->mp_config);
        try {
            $order_exist = Db::table('mp_vip_order')->where($where)->find();
            if(!$order_exist) {
                return ajax('order_sn',4);
            }
            $result = $app->order->unify([
                'body' => 'VIP充值',
                'out_trade_no' => $val['order_sn'],
//                'total_fee' => 1,
                'total_fee' => floatval($order_exist['price'])*100,
                'notify_url' => $this->weburl . 'api/pay/recharge_notify',
                'trade_type' => 'JSAPI',
                'openid' => $this->myinfo['openid'],
            ]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ajax($result['err_code_des'],-1);
        }
        try {
            $sign['appId'] = $result['appid'];
            $sign['timeStamp'] = strval(time());
            $sign['nonceStr'] = $result['nonce_str'];
            $sign['signType'] = 'MD5';
            $sign['package'] = 'prepay_id=' . $result['prepay_id'];
            $sign['paySign'] = getSign($sign);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($sign);
    }
    //充值支付回调接口
    public function recharge_notify() {
        //将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = xml2array($xml);
        $this->paylog($this->cmd,var_export($data,true));
        if($data) {
            if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $map = [
                    ['order_sn','=',$data['out_trade_no']],
                    ['status','=',0],
                ];
                try {
                    $order_exist = Db::table('mp_vip_order')->where($map)->find();
                    if($order_exist) {
                        $update_data = [
                            'status' => 1,
                            'trans_id' => $data['transaction_id'],
                            'pay_time' => time(),
                        ];
                        Db::table('mp_vip_order')->where('order_sn','=',$data['out_trade_no'])->update($update_data);
                        $user = Db::table('mp_user')->where('id',$order_exist['uid'])->find();
                        if($user['vip'] == 1) {
                            $update_user = [
                                'vip' => 1,
                                'vip_time' => $user['vip_time'] + $order_exist['days']*3600*24
                            ];
                        }else {
                            $update_user = [
                                'vip' => 1,
                                'vip_time' => time() + $order_exist['days']*3600*24
                            ];
                        }
                        Db::table('mp_user')->where('id',$order_exist['uid'])->update($update_user);
                    }
                }catch (\Exception $e) {
                    $this->log($this->cmd,$e->getMessage());
                }
            }

        }
        exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));

    }
    //支付单号支付
    public function orderIdPay() {
        $order_id = input('post.order_id',[]);
        if(!is_array($order_id) || empty($order_id)) {
            return ajax('请选择要支付的订单',79);
        }
        $whereOrder = [
            ['id','IN',$order_id],
            ['status','=',0],
            ['uid','=',$this->myinfo['id']]
        ];
        try {
            $pay_price = 0;
            $order_list = Db::table('mp_order')->where($whereOrder)->select();
            if(!$order_list || count($order_list) !== count($order_id)) {
                return ajax($order_id,4);
            }
            foreach ($order_list as $v) {
                $pay_price += $v['pay_price'];
            }
            $pay_order_sn = create_unique_number('');
            $insert_data = [
                'uid' => $this->myinfo['id'],
                'pay_order_sn' => $pay_order_sn,
                'pay_price' => $pay_price,
                'order_ids' => implode(',',$order_id),
                'status' => 0,
                'create_time' => time()
            ];
            Db::table('mp_order_unite')->insert($insert_data);
            Db::table('mp_order')->where($whereOrder)->update(['pay_order_sn' => $pay_order_sn]);
            $app = Factory::payment($this->mp_config);
            $result = $app->order->unify([
                'body' => '山洞文创产品',
                'out_trade_no' => $pay_order_sn,
                'total_fee' => 1,
//                'total_fee' => floatval($pay_price)*100,
                'notify_url' => $this->weburl . 'api/pay/order_notify',
                'trade_type' => 'JSAPI',
                'openid' => $this->myinfo['openid']
            ]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ajax($result['err_code_des'],-1);
        }
        try {
            $sign['appId'] = $result['appid'];
            $sign['timeStamp'] = strval(time());
            $sign['nonceStr'] = $result['nonce_str'];
            $sign['signType'] = 'MD5';
            $sign['package'] = 'prepay_id=' . $result['prepay_id'];
            $sign['paySign'] = getSign($sign);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($sign);
    }
    //订单号支付
    public function orderSnPay() {
        $pay_order_sn = input('post.pay_order_sn');
        if(!$pay_order_sn) {
            return ajax('请选择要支付的订单',-2);
        }
        $whereUnite = [
            ['pay_order_sn','=',$pay_order_sn],
            ['status','=',0],
            ['uid','=',$this->myinfo['id']]
        ];
        $whereOrder = [
            ['pay_order_sn','=',$pay_order_sn],
            ['status','=',0],
            ['uid','=',$this->myinfo['id']]
        ];
        try {
            $unite_exist =  Db::table('mp_order_unite')->where($whereUnite)->find();
            if(!$unite_exist) {
                return ajax($whereUnite,4);
            }
            $order_ids = explode(',',$unite_exist['order_ids']);
            $order_list = Db::table('mp_order')->where($whereOrder)->column('id');
            if(!$order_list || count($order_list) !== count($order_ids)) {
                return ajax($order_ids,4);
            }
            $app = Factory::payment($this->mp_config);
            $result = $app->order->unify([
                'body' => '山洞文创产品',
                'out_trade_no' => $pay_order_sn,
                'total_fee' => 1,
//                'total_fee' => floatval($pay_price)*100,
                'notify_url' => $this->weburl . 'api/pay/order_notify',
                'trade_type' => 'JSAPI',
                'openid' => $this->myinfo['openid']
            ]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ajax($result['err_code_des'],-1);
        }
        try {
            $sign['appId'] = $result['appid'];
            $sign['timeStamp'] = strval(time());
            $sign['nonceStr'] = $result['nonce_str'];
            $sign['signType'] = 'MD5';
            $sign['package'] = 'prepay_id=' . $result['prepay_id'];
            $sign['paySign'] = getSign($sign);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($sign);
    }
    //订单支付回调接口
    public function order_notify() {
//将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = xml2array($xml);
        $this->paylog($this->cmd,var_export($data,true));
        if($data) {
            if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $whereUnite = [
                    ['pay_order_sn','=',$data['out_trade_no']],
                    ['status','=',0]
                ];
                try {
                    $unite_exist = Db::table('mp_order_unite')->where($whereUnite)->find();
                    if($unite_exist) {
                        $update_data = [
                            'status' => 1,
                            'trans_id' => $data['transaction_id'],
                            'pay_time' => time()
                        ];
                        Db::table('mp_order_unite')->where($whereUnite)->update($update_data);
                        Db::table('mp_order')->where($whereUnite)->update($update_data);
                        //todo 发送模板消息
                        //todo 变更商品销量
                    }
                }catch (\Exception $e) {
                    $this->log($this->cmd,$e->getMessage());
                }
            }
        }
        exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
    }
    //订单支付
    public function fundingPay() {
        $val['pay_order_sn'] = input('post.pay_order_sn');
        checkPost($val);
        $where = [
            ['pay_order_sn','=',$val['pay_order_sn']],
            ['status','=',0],
            ['uid','=',$this->myinfo['id']]
        ];
        $app = Factory::payment($this->mp_config);
        try {
            $order_exist = Db::table('mp_funding_order')->where($where)->find();
            if(!$order_exist) {
                return ajax($val['pay_order_sn'],4);
            }
            $total_price = $order_exist['pay_price'];
            $result = $app->order->unify([
                'body' => '山洞文创众筹',
                'out_trade_no' => $val['pay_order_sn'],
                'total_fee' => 1,
//                'total_fee' => floatval($total_price)*100,
                'notify_url' => $this->weburl . 'api/pay/funding_notify',
                'trade_type' => 'JSAPI',
                'openid' => $this->myinfo['openid']
            ]);
        }catch (\Exception $e) {
            $this->log($this->cmd . '-1',$e->getMessage());
            return ajax($e->getMessage(),-1);
        }
        $this->log($this->cmd,var_export($result,true));

        if($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ajax($result['err_code_des'],-1);
        }
        try {
            $sign['appId'] = $result['appid'];
            $sign['timeStamp'] = strval(time());
            $sign['nonceStr'] = $result['nonce_str'];
            $sign['signType'] = 'MD5';
            $sign['package'] = 'prepay_id=' . $result['prepay_id'];
            $sign['paySign'] = getSign($sign);
        }catch (\Exception $e) {
            $this->log($this->cmd . '-2',$e->getMessage());
            return ajax($e->getMessage(),-1);
        }
        return ajax($sign);
    }
    //订单支付回调接口
    public function funding_notify() {
//将返回的XML格式的参数转换成php数组格式
        $xml = file_get_contents('php://input');
        $data = xml2array($xml);
        $this->paylog($this->cmd,var_export($data,true));
        if($data) {
            if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $map = [
                    ['pay_order_sn','=',$data['out_trade_no']],
                    ['status','=',0]
                ];
                try {
                    $order_exist = Db::table('mp_funding_order')->where($map)->find();
                    if($order_exist) {
                        $update_data = [
                            'trans_id' => $data['transaction_id'],
                            'pay_time' => time()
                        ];
                        $whereFunding = [
                            ['id','=',$order_exist['funding_id']]
                        ];
                        $funding_exist = Db::table('mp_funding')->where($whereFunding)->find();
                        if(!$funding_exist) {
                            $this->paylog($this->cmd,$data['out_trade_no'] . '未找到此众筹项目');
                            exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
                        }
                        $funding_data['curr_money'] = $funding_exist['curr_money'] + $order_exist['total_price'];
                        $funding_data['order_num'] = $funding_exist['order_num'] + 1;
                        if($order_exist['type'] == 1) {//有偿订单
                            $update_data['status'] = 1;
                            $funding_data['paid_money'] = $funding_exist['paid_money'] + $order_exist['total_price'];
                            $whereGoods = [
                                ['id','=',$order_exist['goods_id']]
                            ];
                            //商品+销量
                            Db::table('mp_funding_goods')->where($whereGoods)->setInc('sales',$order_exist['num']);//销量+1
                        }else {                         //无偿订单
                            $update_data['status'] = 3;
                            $funding_data['free_money'] = $funding_exist['free_money'] + $order_exist['total_price'];
                        }
                        //修改订单状态,众筹金额增加
                        Db::table('mp_funding_order')->where('pay_order_sn','=',$data['out_trade_no'])->update($update_data);
                        Db::table('mp_funding')->where($whereFunding)->update($funding_data);
                    }
                }catch (\Exception $e) {
                    $this->log($this->cmd,$e->getMessage());
                }
            }
        }
        exit(array2xml(['return_code'=>'SUCCESS','return_msg'=>'OK']));
    }


    protected function asyn_tpl_send($data) {
        $param = http_build_query($data);
        $allow = [
            'fundingOrder'
        ];
        if(!in_array($data['action'],$allow)) {
            $this->log('Pay/asyn_tpl_send',$data['action'] . ' not in allow actions');
        }
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