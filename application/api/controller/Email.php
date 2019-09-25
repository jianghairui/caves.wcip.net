<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/25
 * Time: 13:57
 */
namespace app\api\controller;

use think\Db;
class Email extends Common {

    public function goodsOrder() {
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


}