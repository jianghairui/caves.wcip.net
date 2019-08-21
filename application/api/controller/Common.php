<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/18
 * Time: 21:36
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\exception\HttpException;
use think\exception\HttpResponseException;
class Common extends Controller {

    protected $cmd = '';
    protected $domain = '';
    protected $weburl = '';
    protected $mp_config = [];
    protected $myinfo = [];

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->cmd = request()->controller() . '/' . request()->action();
        $this->domain = 'www.caves.vip';
        $this->weburl = 'https://www.caves.vip/';
        $this->mp_config = [
            'app_id' => config('appid'),
            'secret' => config('app_secret'),
            'mch_id'             => config('mch_id'),
            'key'                => config('mch_key'),   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          =>  config('cert_path'),
            'key_path'           =>  config('key_path'),
            // 下面为可选项,指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => APP_PATH . '/wechat.log',
            ],
        ];
        $this->checkSession();
    }

    private function checkSession() {
        $noneed = [
            'Login/login',
            'Pay/recharge_notify',
            'Pay/order_notify',
            'Activity/test',
//            'Activity/getqrcode'
        ];
        if (in_array(request()->controller() . '/' . request()->action(), $noneed)) {
            return true;
        }else {
            $token = input('post.token');
            if(!$token) {
                throw new HttpResponseException(ajax('token is empty',-5));
            }
            try {
                $exist = Db::table('mp_token')->where([
                    ['token','=',$token],
                    ['end_time','>',time()]
                ])->find();
            }catch (\Exception $e) {
                throw new HttpResponseException(ajax($e->getMessage(),-1));
            }
            if($exist) {
                $this->myinfo = unserialize($exist['value']);
                $this->myinfo['uid'] = $exist['uid'];
                return true;
            }else {
                throw new HttpResponseException(ajax('invalid token',-3));
            }
        }

    }

    //获取我的个人信息
    protected function getMyInfo() {
        $where = [
            ['id','=',$this->myinfo['uid']]
        ];
        try {
            $info = Db::table('mp_user')->where($where)->find();
        }catch (\Exception $e) {
            throw new HttpResponseException(ajax($e->getMessage(),-1));
        }
        return $info;
    }
    //Exception日志
    protected function log($cmd,$str) {
        $file= ROOT_PATH . '/exception_api.txt';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }
    //支付回调日志
    protected function paylog($cmd,$str) {
        $file= ROOT_PATH . '/notify.txt';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }


}