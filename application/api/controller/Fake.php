<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/10/10
 * Time: 10:39
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
class Fake extends Common {

    public function addRole() {
        die();
        $val['nickname'] = input('post.nickname');
        $val['avatar'] = input('post.avatar');
        $val['org'] = input('post.org');
        $val['role'] = 1;
        $val['role_check'] = 2;
        $val['create_time'] = time();
        $val['fake'] = 1;

        $role['org'] = input('post.org');
        $role['desc'] = input('post.desc');
        $role['cover'] = input('post.cover');
        $role['role'] = 1;
        $role['create_time'] = time();

        try {

            $qiniu_exist = $this->qiniuFileExist($val['avatar']);
            if($qiniu_exist !== true) {
                return ajax($qiniu_exist['msg'],5);
            }
            $qiniu_move = $this->moveFile($val['avatar'],'upload/avatar/');

            if($qiniu_move['code'] == 0) {
                $val['avatar'] = $qiniu_move['path'];
            }else {
                return ajax($qiniu_move['msg'],101);
            }

            $qiniu_exist = $this->qiniuFileExist($role['cover']);
            if($qiniu_exist !== true) {
                return ajax($qiniu_exist['msg'],5);
            }
            $qiniu_move = $this->moveFile($role['cover'],'upload/role/');

            if($qiniu_move['code'] == 0) {
                $role['cover'] = $qiniu_move['path'];
            }else {
                return ajax($qiniu_move['msg'],101);
            }

            Db::startTrans();
            $uid = Db::table('mp_user')->insertGetId($val);
            $role['uid'] = $uid;
            Db::table('mp_user_role')->insert($role);
            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            if(isset($val['avatar'])) {
                $this->rs_delete($val['avatar']);
            }
            if(isset($role['cover'])) {
                $this->rs_delete($role['cover']);
            }
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    public function roleClear() {
        $role['uid'] = input('post.uid');
        $role['cover'] = input('post.cover');
        $role['org'] = input('post.org');
        try {
            $whereRole = [
                ['uid','=',$role['uid']]
            ];
            $exist = Db::table('mp_user_role')->where($whereRole)->find();
            if(!$exist) {
                return ajax('非法参数',-4);
            }

            $qiniu_exist = $this->qiniuFileExist($role['cover']);
            if($qiniu_exist !== true) {
                return ajax($qiniu_exist['msg'],5);
            }
            $qiniu_move = $this->moveFile($role['cover'],'upload/role/');

            if($qiniu_move['code'] == 0) {
                $role['cover'] = $qiniu_move['path'];
            }else {
                return ajax($qiniu_move['msg'],101);
            }

            Db::table('mp_user_role')->where($whereRole)->update($role);
            Db::table('mp_user')->where('id','=',$role['uid'])->update(['org'=>$role['org']]);
        } catch (\Exception $e) {
            if(isset($role['cover'])) {
                $this->rs_delete($role['cover']);
            }
            return ajax($e->getMessage(), -1);
        }
        if(isset($role['cover'])) {
            $this->rs_delete($exist['cover']);
        }
        return ajax();
    }

    public function userRestore() {
        $val['uid'] = input('post.uid');

    }

    public function orgMod() {
        $val['uid'] = input('post.uid');
    }


    private function userInfo() {

    }






}