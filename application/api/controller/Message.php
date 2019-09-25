<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/16
 * Time: 15:39
 */
namespace app\api\controller;
use EasyWeChat\Factory;
use think\Db;

class Message extends Common {

    public function index() {

    }

    //众筹订单支付通知
    public function fundingOrder() {
        $order_id = input('param.order_id','');
        if(!$order_id) {
            die('DIE');
        }
        try {
            //订单是否存在
            $whereOrder = [
                ['id','=',$order_id]
            ];
            $order_exist = Db::table('mp_funding_order')->where($whereOrder)->find();
            if(!$order_exist) {
                die('DIE');
            }
            //formid是否存在
            $whereFormid = [
                ['uid','=',$order_exist['uid']],
                ['create_time','>',time()-(7200*24*7+7200*2)]
            ];
            $formid_exist = Db::table('mp_formid')->where($whereFormid)->order(['id'=>'DESC'])->find();
            if(!$formid_exist) {
                $this->msglog($this->cmd,'not found formid ,uid:' . $order_exist['uid']);
                die('not found formid');
            }
            $formid = $formid_exist['formid'];
            //查找用户openid
            $whereUser = [
                ['id','=',$order_exist['uid']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if(!$user_exist) {
                $this->msglog($this->cmd,'user not found , openid:' . $order_exist['uid']);
                die('user not found');
            }
            //发送消息
            $app = Factory::miniProgram($this->mp_config);

            if($order_exist['type'] == 1) {
                $whereGoods = [
                    ['id','=',$order_exist['goods_id']]
                ];
                $order_exist['goods_name'] = Db::table('mp_funding_goods')->where($whereGoods)->value('name');
                if(!$order_exist['goods_name']) {
                    $this->msglog($this->cmd,'goods not found , goods_id:' . $order_exist['goods_id']);
                    die('goods_id not found');
                }
                $res = $app->template_message->send([
                    'touser' => $user_exist['openid'],
                    'template_id' => 'cruzZq_l8eGfn86bP7B6ZQvDNaeKd8blD9lkOiQMjOQ',
                    'page' => 'index',
                    'form_id' => $formid,
                    'data' => [
                        'keyword1' => $order_exist['pay_order_sn'],
                        'keyword2' => $order_exist['goods_name'],
                        'keyword3' => $order_exist['num'],
                        'keyword4' => $order_exist['pay_price'],
                        'keyword5' => date('Y年m月d日 H:i:s',$order_exist['pay_time']),
                    ]
                ]);
            }else {
                $res = $app->template_message->send([
                    'touser' => $user_exist['openid'],
                    'template_id' => 'ThEod2BWBdSCC0C1xk8bz9A5BVmQkZdKJw2H7GIqT84',
                    'page' => 'index',
                    'form_id' => $formid,
                    'data' => [
                        'keyword1' => $order_exist['pay_order_sn'],
                        'keyword2' => '山洞文创平台无偿众筹',
                        'keyword3' => $order_exist['pay_price'],
                        'keyword4' => date('Y年m月d日 H:i:s',$order_exist['pay_time'])
                    ]
                ]);
            }
            if($res['errcode'] != 0) {
                $this->msglog($this->cmd, var_export($res,true));
            }
            Db::table('mp_formid')->where('formid','=',$formid)->delete();
        } catch (\Exception $e) {
            $this->log($this->cmd, $e->getMessage());
        }
        exit('SUCCESS');
    }
    //博物馆选择工厂时给工厂发送消息
    public function chooseFactory() {

    }

    //作品未通过审核时给设计师发送消息
    public function worksReject() {

    }

    //笔记未通过审核时给发布人发送消息
    public function noteReject() {

    }

    //商城订单支付成功给用户发送消息
    public function order() {
        $order_id = input('param.order_id','');
        if(!$order_id) {    die('DIE');}
        try {
            //订单是否存在
            $whereOrder = [
                ['id','=',$order_id],
                ['status','=',1]
            ];
            $order_exist = Db::table('mp_order_unite')->where($whereOrder)->find();
            if(!$order_exist) { die('DIE');}
            $uid = $order_exist['uid'];
            //formid是否存在
            $whereFormid = [
                ['uid','=',$uid],
                ['create_time','>',time()-(3600*24*7-3600*2)]
            ];
            $formid_exist = Db::table('mp_formid')->where($whereFormid)->find();
            if(!$formid_exist) {
                $this->msglog($this->cmd,'not found formid ,uid:' . $uid);
                die('not found formid');
            }
            $this->msglog($this->cmd, var_export($formid_exist,true));
            $formid = $formid_exist['formid'];
            //查找用户openid
            $whereUser = [
                ['id','=',$uid]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if(!$user_exist) {
                $this->msglog($this->cmd,'user not found , openid:' . $uid);
                die('user not found');
            }
            //发送消息
            $app = Factory::miniProgram($this->mp_config);

            $res = $app->template_message->send([
                'touser' => $user_exist['openid'],
                'template_id' => 'cruzZq_l8eGfn86bP7B6ZYo-CCBiEZDYQHeFz-WrYvE',
                'page' => 'index',
                'form_id' => $formid,
                'data' => [
                    'keyword1' => $order_exist['pay_order_sn'],
                    'keyword2' => '山洞文创商品',
                    'keyword3' => $order_exist['pay_price'],
                    'keyword4' => date('Y年m月d日 H:i:s',$order_exist['pay_time']),
                ]
            ]);

            if($res['errcode'] != 0) {
                $this->msglog($this->cmd, var_export($res,true));
            }
            Db::table('mp_formid')->where('formid','=',$formid)->delete();
        } catch (\Exception $e) {
            $this->log($this->cmd, $e->getMessage());
        }
        exit('SUCCESS');
    }

    //申请平台角色未过审,给用户发送消息
    public function roleReject() {

    }







}