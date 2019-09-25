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
use think\exception\HttpResponseException;

require_once ROOT_PATH . '/extend/qiniu/autoload.php';
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use EasyWeChat\Factory;

class Common extends Controller {

    protected $controller = '';
    protected $cmd = '';
    protected $domain = '';
    protected $weburl = '';
    protected $mp_config = [];
    protected $myinfo = [];

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->controller = request()->controller();
        $this->cmd = request()->controller() . '/' . request()->action();
        $this->domain = config('domain');
        $this->weburl = config('weburl');
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
            'Test',
            'Message',
            'Email',
            'Email',
            'Login/login',
            'Pay/recharge_notify',
            'Pay/order_notify',
            'Pay/funding_notify',
            'Activity/test',
            'Message/index',
            'Message/fundingorder',
            'Message/order',
//            'Activity/getqrcode'
        ];
        if (in_array($this->controller,$noneed) || in_array($this->cmd, $noneed)) {
            return true;
        }else {
            $token = input('post.token');
            if(!$token) {
                throw new HttpResponseException(ajax('token is empty',-5));
            }
            try {
                $exist = Db::table('mp_user')->where([
                    ['token','=',$token]
                ])->find();
            }catch (\Exception $e) {
                throw new HttpResponseException(ajax($e->getMessage(),-1));
            }
            if($exist) {
                if(($exist['last_login_time'] + 3600*24*7) < time()) {
                    throw new HttpResponseException(ajax('invalid token',-3));
                }
                $this->myinfo = $exist;
                return true;
            }else {
                throw new HttpResponseException(ajax('invalid token',-3));
            }
        }

    }

    //七牛云判断文件是否存在
    public function qiniuFileExist($key) {
        $auth = new \Qiniu\Auth(config('qiniu_ak'), config('qiniu_sk'));
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        list($fileInfo, $err) = $bucketManager->stat(config('qiniu_bucket'), $key);
        if ($err) {
            return [
                'code' => 1,
                'msg' => 'qiniu_code:' . $err->code() .' , '. $err->message()
            ];
        }
        return true;
    }

    //七牛云移动文件
    protected function moveFile($srcKey,$destpath='upload/public/') {
        $auth = new \Qiniu\Auth(config('qiniu_ak'), config('qiniu_sk'));
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);

        $srcBucket = config('qiniu_bucket');
        $destBucket = config('qiniu_bucket');
        $arr = explode('/',$srcKey);
        $destKey = $destpath . end($arr);
        //如果一样不需要挪动
        if($srcKey == $destKey) {
            return [
                'code' => 0,
                'path' => $destKey
            ];
        }
        $err = $bucketManager->move($srcBucket, $srcKey, $destBucket, $destKey, true);
        if($err) {
            return [
                'code' => 1,
                'msg' => 'qiniu_code:' . $err->code() .' , '. $err->message()
            ];
        }else {
            return [
                'code' => 0,
                'path' => $destKey
            ];
        }

    }

    //七牛云删除文件
    protected function rs_delete($key) {
        $auth = new \Qiniu\Auth(config('qiniu_ak'), config('qiniu_sk'));
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $bucketManager->delete(config('qiniu_bucket'), $key);
    }

    //小程序验证文本内容是否违规
    protected function msgSecCheck($msg) {
        $content = $msg;
        $app = Factory::payment($this->mp_config);
        $access_token = $app->access_token;
        $token = $access_token->getToken();
        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $token['access_token'];
        $res = curl_post_data($url, '{ "content":"'.$content.'" }');

        $result = json_decode($res,true);
        try {
            $audit = true;
            if($result['errcode'] !== 0) {
                $this->mplog($this->cmd,$this->myinfo['id'] .' : '. $content .' : '. var_export($result,true));
                switch ($result['errcode']) {
                    case 87014: $audit = false;break;
                    case 40001:
                        $audit = false;break;
                    default:$audit = false;;
                }
            }
        } catch (\Exception $e) {
            throw new HttpResponseException(ajax($e->getMessage(),-1));
        }
        return $audit;
    }

    /*微信图片敏感内容检测*/
    public function imgSecCheck($image_path) {
        $audit = true;
        $img = @file_get_contents($image_path);
        if(!$img) {
            $this->mplog($this->cmd,'file_get_contents(): php_network_getaddresses: getaddrinfo failed: Name or service not known');
            return true;
        }
        $filePath = '/dev/shm/tmp1.png';
        file_put_contents($filePath, $img);
        $obj = new \CURLFile(realpath($filePath));
        $obj->setMimeType("image/jpeg");
        $file['media'] = $obj;
        $app = Factory::payment($this->mp_config);
        $access_token = $app->access_token;
        $token = $access_token->getToken();
        $url = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=' . $token['access_token'];
        $info = curl_post_data($url,$file);
        $result = json_decode($info,true);
        try {
            if($result['errcode'] !== 0) {
                $this->mplog($this->cmd,$this->myinfo['id'] .' : '. $image_path .' : '. var_export($result,true));
                switch ($result['errcode']) {
                    case 87014: $audit = false;break;
                    case 40001:
                        $audit = false;break;
                    default:$audit = false;;
                }
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return $audit;
    }


    //Exception日志
    protected function log($cmd,$str) {
        $file= ROOT_PATH . '/log/exception_api.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }
    //支付回调日志
    protected function paylog($cmd,$str) {
        $file= ROOT_PATH . '/log/notify.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }
    //七牛云日志
    public function qiniuLog($cmd,$str) {
        $file= ROOT_PATH . '/log/qiniu_error.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }

    //Exception日志
    protected function msglog($cmd,$str) {
        $file= ROOT_PATH . '/log/message.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }

    //小程序验证内容违规
    protected function mplog($cmd,$str) {
        $file= ROOT_PATH . '/log/mp.log';
        $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
        if(false !== fopen($file,'a+')){
            file_put_contents($file,$text,FILE_APPEND);
        }else{
            echo '创建失败';
        }
    }


}