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

            $qiniu_exist = $this->qiniuFileExist($val['cover']);
            if($qiniu_exist !== true) {
                return ajax($qiniu_exist['msg'],5);
            }
            $qiniu_move = $this->moveFile($val['cover'],'upload/role/');

            if($qiniu_move['code'] == 0) {
                $val['cover'] = $qiniu_move['path'];
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
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }



    public function test() {

    }






}