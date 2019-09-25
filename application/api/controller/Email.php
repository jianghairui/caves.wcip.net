<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/25
 * Time: 13:57
 */
namespace app\api\controller;

use think\Db;
use my\smtp;
class Email extends Common {

    public function goodsOrder() {

//        if($_SERVER['REMOTE_ADDR'] == '120.27.60.129') {
            try {
                $order_id = input('param.order_id','');
//                $order_id = 19;
                if(!$order_id) {    die('DIE');}
                //订单是否存在
                $whereUnite = [
                    ['id','=',$order_id],
                    ['status','=',1]
                ];
                $order_exist = Db::table('mp_order_unite')->where($whereUnite)->find();
                if(!$order_exist) { die('INVALID ORDER_ID');}

                $whereOrder = [
                    ['o.pay_order_sn','=',$order_exist['pay_order_sn']]
                ];
                $order_list = Db::table('mp_order')->alias('o')
                    ->join('mp_user u','o.shop_id=u.id','left')
                    ->field('o.*,u.order_email')
                    ->where($whereOrder)->select();

                foreach ($order_list as $li) {
                    $whereGoods = [
                        ['order_id','=',$li['id']]
                    ];
                    $goods_name = '';
                    $goods_list = Db::table('mp_order_detail')->where($whereGoods)->field('id,goods_name,num')->select();
                    foreach ($goods_list as $v) {
                        $goods_name .= $v['goods_name'] . ' (数量: x' . $v['num'] . ')<br>';
                    }
                    //使用163邮箱服务器
                    $smtpserver = "smtp.163.com";
//163邮箱服务器端口
                    $smtpserverport = 465;
//你的163服务器邮箱账号
                    $smtpusermail = "git_smtp@163.com";
//收件人邮箱
                    if($li['order_email']) {
                        $smtpemailto = $li['order_email'];
                    }else {
                        $smtpemailto = '1873645345@qq.com';
                    }

//你的邮箱账号(去掉@163.com)
                    $smtpuser = "git_smtp";//你的163邮箱去掉后面的163.com
//你的邮箱密码
                    $smtppass = "jiang22513822"; //你的163邮箱SMTP的授权码，千万不要填密码！！！

//邮件主题
                    $mailsubject = '您有新的订单';
//邮件内容
                    $mailbody = '订单编号: ' . $li['order_sn'] . '<br>';
                    $mailbody .= '购买商品: <br>' . $goods_name . '<br>';
                    $mailbody .= '支付金额: <span style="color: #ff4c4c">' . $li['pay_price'] . '</span><br>';
                    $mailbody .= '下单时间: ' . date('Y年m月d日 H:i:s') . '<br>';

                    //邮件格式（HTML/TXT）,TXT为文本邮件
                    $mailtype = "HTML";
//这里面的一个true是表示使用身份验证,否则不使用身份验证.
                    $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
//是否显示发送的调试信息
                    $smtp->debug = false;
//发送邮件
                    $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
                }

            } catch(\Exception $e) {
                die($e->getMessage());
            }


//        }else {
//            die('IP不在访问白名单内');
//        }
    }


}