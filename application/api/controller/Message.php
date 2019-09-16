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
            'touser' => 'o2UbX5SVvuPYwfC47Ej-SedRDr-g',
            'template_id' => 'cruzZq_l8eGfn86bP7B6ZQvDNaeKd8blD9lkOiQMjOQ',
            'page' => 'index',
            'form_id' => 'e57019cc64d34647b29a894f9fb52c7a',
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
            $whereOrder = [
                ['id','=',$order_id]
            ];
            Db::table('mp_funding_order')->where($whereOrder)->find();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
    }




}