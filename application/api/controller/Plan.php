<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/29
 * Time: 16:57
 */
namespace app\api\controller;

use think\Db;
class Plan extends Common {

    public function index() {
        try {
            $whereFunding = [
                ['end_time','<',time()],

            ];
            $funding_list = Db::table('mp_funding')->where($whereFunding)->whereExp('need_money',' > curr_money')->select();
            foreach ($funding_list as $v) {
                $whereOrder = [
                    ['funding_id','=',$v['id']],
                    ['status','=',1],
                    ['refund_apply','=',0],
                    ['result','=',0]
                ];
                $order_list = Db::table('mp_funding_order')->alias('o')
                    ->join('mp_')
                    ->where($whereOrder)->select();

            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($funding_list as &$v) {
            $v['end_time'] = date('Y-m-d H:i:s',$v['end_time']);
        }

        halt($funding_list);
    }

    public function fundingSuccess() {

    }

//    public function



}