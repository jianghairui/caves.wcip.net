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
        $app = Factory::miniProgram($this->mp_config);
        $res = $app->template_message->send([
            'touser' => 'o2UbX5d2Zz7wRFoRXAiEErs7cM4g',
            'template_id' => 'cruzZq_l8eGfn86bP7B6ZQvDNaeKd8blD9lkOiQMjOQ',
            'page' => 'index',
            'form_id' => '83c465c2b46044eb90996732340613de',
            'data' => [
                'keyword1' => time() . mt_rand(100,999),
                'keyword2' => '文创小茶杯,',
                'keyword3' => '3',
                'keyword4' => '150',
                'keyword5' => date('Y年m月d日 H:i:s'),
                // ...
            ]
        ]);
        halt($res);
    }

    public function collectFormid() {
        $val['formid'] = input('post.formid');
        checkPost($val);
        if($val['formid'] == 'the formId is a mock one') {
            return ajax();
        }
        $val['uid'] = $this->myinfo['id'];
        $val['create_time'] = time();
        try {
            Db::table('mp_formid')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($val);
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
        halt($res);
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

    }

    //申请平台角色未过审,给用户发送消息
    public function roleReject() {

    }







}