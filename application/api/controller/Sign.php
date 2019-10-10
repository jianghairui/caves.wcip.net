<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/12
 * Time: 15:53
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
use wx\Jssdk;
class Sign extends Controller {

    protected $appid;
    protected $appsecret;
    protected $cmd;

    public function initialize() {
        $this->appid = config('wx_appid');
        $this->appsecret = config('wx_appsecret');
        $this->cmd = request()->controller() . '/' . request()->action();
        $allow = [
            'Sign/auth'
        ];
        if(!in_array($this->cmd,$allow)) {
            if(!session('userinfo')) {
                $query = http_build_query(input('param.'));
                $url = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/sign/auth?' . $query;
                header("Location:".$url);exit;
            }
        }

    }


    public function index() {
        $userinfo = session('userinfo');
        if(request()->isPost()) {
            $val['company'] = input('post.company');
            $val['busine'] = input('post.busine');
            $val['name'] = input('post.name');
            $val['tel'] = input('post.tel');
            $val['num'] = input('post.num');
            checkPost($val);
            $val['openid'] = $userinfo['openid'];
            $val['nickname'] = $userinfo['nickname'];
            $val['avatar'] = $userinfo['headimgurl'];
            $val['create_time'] = time();

            if(!is_tel($val['tel'])) {
                return ajax('无效的手机号',-1);
            }
            try {
                Db::table('mp_sign')->insert($val);
            } catch (\Exception $e) {
                return ajax($e->getMessage(), -1);
            }
            return ajax();
        }

        $jssdk = new Jssdk($this->appid, $this->appsecret);
        $data = $jssdk->getSignPackage();
        $share_data = [
            'title' => '平津战役纪念馆文创对接会邀请函',
            'desc' => '平津战役纪念馆文创对接会邀请函',
            'link' => $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/sign/index',
            'imgUrl' => $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/static/src/image/shlogo.png'
        ];
        $this->assign('data',$data);
        $this->assign('share_data',$share_data);
        $this->assign('userinfo',$userinfo);
        return $this->fetch();
    }

    /**
     * 1、获取微信用户信息，判断有没有code，有使用code换取access_token，没有去获取code。
     * @return array 微信用户信息数组
     */
    public function auth(){
        $query = http_build_query(input('param.'));

        if(!session('openid')) {
            if (!isset($_GET['code'])){ //没有code，去微信接口获取code码
                $callback = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/sign/auth?'.$query;//微信服务器回调url，这里是本页url http://fx.jianghairui.com/index/index/get_user_all
                $this->get_code($callback);
            } else {    //获取code后跳转回来到这里了
                $code = $_GET['code'];
                $data = $this->get_access_token($code);//获取网页授权access_token和用户openid
                $data_all = $this->get_user_info($data['access_token'],$data['openid']);//获取微信用户信息

                /*保存用户信息到数据库并设置session*/
//                $insert_data = [
//                    'openid' => $data_all['openid'],
//                    'nickname' => $data_all['nickname'],
//                    'avatar' => $data_all['headimgurl']
//                ];
//
//                try {
//                    $whereUser = [
//                        ['openid','=',$data_all['openid']]
//                    ];
//                    $user_exist = Db::table('mp_sign')->where($whereUser)->find();
//                    if($user_exist) {
//                        Db::table('mp_sign')->where($whereUser)->update($insert_data);
//                    }else {
//                        $insert_data['create_time'] = time();
//                        Db::table('mp_sign')->insert($insert_data);
//                    }
//                }catch (\Exception $e) {
//                    die('系统错误,请联系管理员 :' . $e->getMessage());
//                }

                session('userinfo',$data_all);
            }
        }
        $url = $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'] . '/api/sign/index?' . $query;
        header("Location:".$url);exit;

    }

    /**
     * 2、用户授权并获取code
     * @param string $callback 微信服务器回调链接url
     */
    private function get_code($callback){
        $appid = $this->appid;
        $scope = 'snsapi_userinfo';
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
        $appsecret = $this->appsecret;
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $appsecret . '&code=' . $code . '&grant_type=authorization_code';
        $user = json_decode(file_get_contents($url));
        if (isset($user->errcode)) {
            if($user->errcode == '40163') {
                echo 'Code been used!!!';exit;
            }
            echo 'error:' . $user->errcode.'<hr>msg :' . $user->errmsg;exit;
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