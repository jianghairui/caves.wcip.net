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

    protected function checkPost($postArray) {
        if(empty($postArray)) {
            throw new HttpResponseException(ajax($postArray,-2));
        }
        foreach ($postArray as $value) {
            if (is_null($value) || $value === '') {
                throw new HttpResponseException(ajax($postArray,-2));
            }
        }
        return true;
    }

    protected function upload($k,$maxsize=512) {
        $allowType = array(
            "image/gif",
            "image/jpeg",
            "image/jpg",
            "image/png",
            "image/pjpeg",
            "image/bmp"
        );
        if($_FILES[$k]["type"] == '') {
            throw new HttpResponseException(ajax('图片存在中文名或超过2M',20));
        }
        if(!in_array($_FILES[$k]["type"],$allowType)) {
            throw new HttpResponseException(ajax('文件类型不符' . $_FILES[$k]["type"],21));
        }
        if($_FILES[$k]["size"] > $maxsize*1024) {
            throw new HttpResponseException(ajax('图片大小不超过'.$maxsize.'Kb',22));
        }
        if ($_FILES[$k]["error"] > 0) {
            throw new HttpResponseException(ajax("error: " . $_FILES[$k]["error"],-1));
        }

        $filename_array = explode('.',$_FILES[$k]['name']);
        $ext = array_pop($filename_array);

        $path =  'static/tmp/';
        is_dir($path) or mkdir($path,0755,true);
        //转移临时文件
        $newname = create_unique_number() . '.' . $ext;
        move_uploaded_file($_FILES[$k]["tmp_name"], $path . $newname);
        $filepath = $path . $newname;
        return $filepath;
    }

    protected function rename_file($tmp,$path = '') {
        $filename = substr(strrchr($tmp,"/"),1);
        $path = $path ? $path : 'static/uploads/note/';
        $path.= date('Y-m-d') . '/';
        is_dir($path) or mkdir($path,0755,true);
        @rename($tmp, $path . $filename);
        return $path . $filename;
    }

    //生成签名
    protected function getSign($arr)
    {
        //去除数组中的空值
        $arr = array_filter($arr);
        //如果数组中有签名删除签名
        if(isset($arr['sing'])) {
            unset($arr['sing']);
        }
        //按照键名字典排序
        ksort($arr);
        //生成URL格式的字符串
        $str = http_build_query($arr)."&key=".$this->mp_config['key'];
        $str = $this->arrToUrl($str);
        return  strtoupper(md5($str));
    }
    //URL解码为中文
    protected function arrToUrl($str)
    {
        return urldecode($str);
    }

    protected function xml2array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    protected function array2xml($arr) {
        if(!is_array($arr) || count($arr) <= 0) {
            return false;
        }
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
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