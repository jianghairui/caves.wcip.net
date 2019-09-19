<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/27
 * Time: 11:24
 */
namespace app\user\controller;
use think\Db;
use think\Loader;
class Login extends Base {

    public function index() {
        if(session('userinfo')) {
            $this->redirect('Index/index');
            exit();
        }

        $cookie = cookie('mp_password');
        if(isset($cookie) && $cookie != '') {
            $data['mp_username'] = cookie('mp_username');
            $data['mp_password'] = cookie('mp_password');
            $data['remember_pwd'] = 1;
        }else {
            $data['mp_username'] = '';
            $data['mp_password'] = '';
            $data['remember_pwd'] = 0;
        }
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function login() {
        if(request()->isPost()) {
            $login_vcode = input('post.login_vcode');
            if(strtolower($login_vcode) !== strtolower(session('login_vcode'))) {
                $this->error('验证码错误',url('Login/index'));
            }
            $where['username'] = input('post.username');
            $where['password'] = md5(input('post.password') . config('login_key'));
            try {
                $result = Db::table('mp_user')->where($where)->find();
            }catch (\Exception $e) {
                $this->error($e->getMessage(),url('Login/index'));
            }

            if($result) {
                if($result['status'] == 2 && $result['username'] !== config('superman')) {
                    exit($this->fetch('frozen'));
                }
                session('userinfo',$result);
                session('login_vcode',null);

                if(input('post.remember_pwd') == 1) {
                    cookie('mp_username',input('post.username'),3600*24*7);
                    cookie('mp_password',input('post.password'),3600*24*7);
                }else {
                    cookie('mp_username',null);
                    cookie('mp_password',null);
                }
            }else {
                $this->error('用户名密码不匹配',url('Login/index'));exit();
            }
            $this->redirect(url('Index/index'));
//            $this->success('登陆成功',url('Index/index'));

        }
    }

    public function logout() {
        session('userinfo',null);
        $this->redirect('Login/index');
    }

    public function vcode() {
        $vcode = generateVerify(200,50,2,4,24);
        session('login_vcode',$vcode);
    }

    public function personal() {
        $id = session('user_id');
        try {
            $info = Db::table('mp_user')->where('id','=',$id)->find();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function modifyInfo() {
        $id = session('user_id');
        $val['realname'] = input('post.realname');
        $val['gender'] = input('post.gender');
        $val['tel'] = input('post.tel');
        $val['email'] = input('post.email');
        checkInput($val);
        $val['password'] = input('post.password');
        $val['desc'] = input('post.desc');
        if($val['password']) {
            $val['password'] = md5($val['password'] . config('login_key'));
        }else {
            unset($val['password']);
        }
        try {
            Db::table('mp_user')->where('id','=',$id)->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val,1);
    }




}