<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/20
 * Time: 16:36
 */
namespace app\api\controller;
use EasyWeChat\Factory;
use think\Db;

class Login extends Common {

    //小程序登录
    public function login()
    {
        $code = input('post.code');
        checkPost(['code'=>$code]);
        $app = Factory::miniProgram($this->mp_config);
        $info = $app->auth->session($code);
        if(isset($info['errcode']) && $info['errcode'] !== 0) {
            return ajax($info,-1);
        }
        $ret['openid'] = $info['openid'];
        $ret['session_key'] = $info['session_key'];

        try {
            $token = md5($ret['openid'] . time());
            $exist = Db::table('mp_user')->where('openid',$ret['openid'])->find();
            if($exist) {
                Db::table('mp_user')->where('openid',$ret['openid'])->update([
                    'last_login_time'=>time(),
                    'token'=>$token,
                    'session_key'=>$ret['session_key']
                ]);
                $uid = $exist['id'];
            }else {
                $insert = [
                    'create_time' => time(),
                    'last_login_time' => time(),
                    'openid' => $ret['openid'],
                    'session_key' => $ret['session_key'],
                    'token' => $token
                ];
                $uid = Db::table('mp_user')->insertGetId($insert);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $json['token'] = $token;
        $json['uid'] = $uid;
        return ajax($json);
    }

    //保存用户信息
    public function userAuth() {
        $iv = input('post.iv');
        $encryptData = input('post.encryptedData');
        checkPost([
            'iv' => $iv,
            'encryptedData' => $encryptData
        ]);
        if(!$iv || !$encryptData) {
            return ajax([],-2);
        }
        $app = Factory::miniProgram($this->mp_config);
        try {
            $decryptedData = $app->encryptor->decryptData($this->myinfo['session_key'], $iv, $encryptData);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $user = $this->myinfo;
        try {
            $data['nickname'] = $decryptedData['nickName'];
            $data['avatar'] = $decryptedData['avatarUrl'];
            $data['sex'] = $decryptedData['gender'];
            $data['unionid'] = $decryptedData['unionId'];
            //未授权过的新用户
            if(!$user['user_auth']) {
                $data['user_auth'] = 1;
            }
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($data);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->rs_delete($user['avatar']);//删除旧头像
        return ajax('保存成功',1);
    }

    //检测用户是否授权
    public function checkUserAuth() {
        $uid = $this->myinfo['id'];
        try {
            $userauth = Db::table('mp_user')->where('id',$uid)->value('user_auth');
            if($userauth == 1) {
                return ajax(true);
            }else {
                return ajax(false);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

    //保存手机号
    public function getPhoneNumber() {
        $iv = input('post.iv');
        $encryptData = input('post.encryptedData');
        checkPost([
            'iv' => $iv,
            'encryptedData' => $encryptData
        ]);
        if(!$iv || !$encryptData) {
            return ajax([],-2);
        }
        $app = Factory::miniProgram($this->mp_config);
        try {
            $decryptedData = $app->encryptor->decryptData($this->myinfo['session_key'], $iv, $encryptData);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        $inviter_id = input('post.inviter_id');
        try {
            $data['tel'] = $decryptedData['phoneNumber'];
            Db::table('mp_user')->where('openid','=',$this->myinfo['openid'])->update($data);
            //如果已授权手机号
            if($this->myinfo['tel']) {
                return ajax($decryptedData);
            }
            //是否有邀请人ID
            if($inviter_id) {
                $score = 50;
                $data['inviter_id'] = $inviter_id;
                $insert_data = [
                    'inviter_id' => $inviter_id,
                    'to_uid' => $this->myinfo['id'],
                    'score' => $score,
                    'create_time' => time()
                ];
                Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($data);
                Db::table('mp_invite')->insert($insert_data);
                Db::table('mp_user')->where('id','=',$inviter_id)->setInc('score',$score);
            }else {
                Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($data);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($decryptedData);
    }


}