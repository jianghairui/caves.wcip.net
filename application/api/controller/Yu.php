<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/12/30
 * Time: 10:07
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use my\Sendsms;
class Yu extends Controller {

    protected $appid = '';
    protected $app_secret = '';
    protected $allow_list = [];
    protected $cmd = '';

    public function initialize() {

        parent::initialize(); //
        $this->appid = config('wx_appid');
        $this->app_secret = config('wx_appsecret');
        $this->cmd = request()->controller() . '/' . request()->action();
        $this->allow_list = [
            'Yu/auth',
            'Yu/sendsms'
        ];

        if(!in_array($this->cmd,$this->allow_list)) {
            $userinfo = session('userinfo');
            $auth_url = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/yu/auth';
            if(!$userinfo) {
                if(request()->isPost()) {
                    throw new HttpResponseException(ajax('请刷新页面重试',-1));
                }else {
                    header("Location:" . $auth_url);exit;
                }
            }else {
                if(md5($userinfo['uid'] . $userinfo['openid'] . $userinfo['login_time'] . config('login_key')) !== $userinfo['verify'] ) {
                    if(request()->isPost()) {
                        throw new HttpResponseException(ajax('请刷新页面重试',-1));
                    }else {
                        header("Location:" . $auth_url);exit;
                    }
                }
            }
        }

    }

    public function index() {
        $userinfo = session('userinfo');
        halt($userinfo);
    }



    //获取城市列表
    public function getCityList() {
        $val['provinceCode'] = input('post.provinceCode');
        try {
            if($val['provinceCode']) {
                $where = [
                    ['pcode','=',$val['provinceCode']],
                    ['level','=',2]
                ];
            }else {
                return ajax([]);
            }
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取区列表
    public function getRegionList() {
        $val['cityCode'] = input('post.cityCode');
        try {
            if($val['cityCode']) {
                $where = [
                    ['pcode','=',$val['cityCode']],
                    ['level','=',3]
                ];
            }else {
                return ajax([]);
            }
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //提交表单发送手机短信
    public function sendSms() {
        $val['tel'] = input('post.tel');
        checkPost($val);
        $sms = new Sendsms();
        $tel = $val['tel'];

        if(!is_tel($tel)) {
            return ajax('invalid tel',6);
        }
        try {
            $code = mt_rand(100000,999999);
            $insert_data = [
                'tel' => $tel,
                'code' => $code,
                'create_time' => time()
            ];
            $sms_data['tel'] = $val['tel'];
            $sms_data['param'] = [
                'code' => $code
            ];
            $exist = Db::table('mp_verify')->where('tel','=',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($sms_data,'SMS_181850052');
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($insert_data);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($sms_data);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->insert($insert_data);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }


    //兑换成功发送手机通知短信
    public function orderSms() {
        $sms = new Sendsms();
        try {
            $card_no = "12251646";
            $sms_data['tel'] = '13102163019';
            $sms_data['param'] = [
                'name' => $card_no
            ];
            $res = $sms->send($sms_data);
            if($res->Code === 'OK') {
                return ajax();
            }else {
                return ajax($res->Message,12);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
    }




    /**
     * 1、获取微信用户信息，判断有没有code，有使用code换取access_token，没有去获取code。
     * @return array 微信用户信息数组
     */
    public function auth(){
        if (!isset($_GET['code'])){ //没有code，去微信接口获取code码
            $callback = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/yu/auth';//微信服务器回调url，这里是本页url
            $this->get_code($callback);
        } else {    //获取code后跳转回来到这里了
            $code = $_GET['code'];
            $access_token = $this->get_access_token($code);//获取网页授权access_token和用户openid
            $data_all = $this->get_user_info($access_token['access_token'],$access_token['openid']);//获取微信用户信息
            /*保存用户信息到数据库并设置session*/
            try {
                $whereUser = [
                    ['openid','=',$data_all['openid']]
                ];
                $user_exist = Db::table('mp_yu_user')->where($whereUser)->find();
                if($user_exist) {
                    $update_data = [
                        'nickname' => $data_all['nickname'],
                        'avatar' => $data_all['headimgurl'],
                        'sex' => $data_all['sex']
                    ];
                    Db::table('mp_yu_user')->where($whereUser)->update($update_data);
                    $uid = $user_exist['id'];
                }else {
                    $insert_data = [
                        'openid' => $data_all['openid'],
                        'nickname' => $data_all['nickname'],
                        'avatar' => $data_all['headimgurl'],
                        'sex' => $data_all['sex'],
                        'create_time' => time()
                    ];
                    $uid = Db::table('mp_yu_user')->insertGetId($insert_data);
                }
            }catch (\Exception $e) {
                die($e->getMessage());
            }
        }
        $login_time = time();
        $session_info = [
            'uid' => $uid,
            'openid' => $data_all['openid'],
            'login_time' => $login_time,
            'verify' => md5($uid . $data_all['openid'] . $login_time . config('login_key'))
        ];
        session('userinfo',$session_info);
        header("Location:" . $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/yu/index');exit;

    }
    /**
     * 2、用户授权并获取code
     * @param string $callback 微信服务器回调链接url
     */
    private function get_code($callback,$scope = 'snsapi_userinfo'){
        $appid = $this->appid;
        $state = md5(uniqid(rand(), true));//唯一ID标识符绝对不会重复
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . urlencode($callback) .  '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
        header("Location:".$url);exit;
    }
    /**
     * 3、使用code换取access_token
     * @param string 用于换取access_token的code，微信提供
     * @return array access_token和用户openid数组
     */
    private function get_access_token($code){
        $appid = $this->appid;
        $appsecret = $this->app_secret;
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $appsecret . '&code=' . $code . '&grant_type=authorization_code';
        $user = json_decode(file_get_contents($url));
        if (isset($user->errcode)) {
            if($user->errcode == '40163') {
                echo 'Code been used!';exit;
            }else {
                echo 'error:' . $user->errcode.'<hr>msg :' . $user->errmsg;exit;
            }
        }
        $data = json_decode(json_encode($user),true);//返回的json数组转换成array数组
        return $data;
    }
    /**
     * 4、使用access_token获取用户信息
     * @param string access_token
     * @param string 用户的openid
     * @return array 用户信息数组
     */
    private function get_user_info($access_token,$openid){
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $user = json_decode(file_get_contents($url));
        if (isset($user->errcode)) {
            echo 'error:' . $user->errcode.'<hr>msg  :' . $user->errmsg;exit;
        }
        $data = json_decode(json_encode($user),true);//返回的json数组转换成array数组
        return $data;
    }























}