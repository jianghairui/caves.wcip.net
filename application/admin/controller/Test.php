<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/6
 * Time: 17:01
 */
namespace app\admin\controller;

use think\Db;
class Test extends Base {


    //删除角色测试,删除角色图片
    public function roledel() {
        die();
        $arr = [];
        foreach ($arr as $uid) {
//            $uid = $uid;
//            $uid = 2;
            try {
                $info = Db::table('mp_user_role')->where('uid','=',$uid)->find();
                if(!$info) {
                    die('无角色');
                }
                $update_data = [
                    'role' => 0,
                    'role_check' => 0,
                    'org' => ''
                ];
                Db::table('mp_user')->where('id','=',$uid)->update($update_data);
                Db::table('mp_user_role')->where('uid','=',$uid)->delete();
            } catch (\Exception $e) {
                return ajax($e->getMessage(), -1);
            }
            $pics = unserialize($info['works']);
            $pics[] = $info['cover'];
            $pics[] = $info['id_front'];
            $pics[] = $info['id_back'];
            $pics[] = $info['license'];
            foreach ($pics as $v) {
                $this->rs_delete($v);
            }
        }

    }

    //七牛云转移笔记图片

//    public function notemove() {
//        try {
//            $list = Db::table('mp_note_bak')->select();
//        } catch (\Exception $e) {
//            return ajax($e->getMessage(), -1);
//        }
//        foreach ($list as &$v) {
//            $v['pics'] = unserialize($v['pics']);
//            foreach ($v['pics'] as &$vv) {
//                $vv = "upload/note" . substr($vv,30);
//            }
//            $v['pics'] = serialize($v['pics']);
//        }
//        $res = Db::table('mp_note')->insertAll($list);
//        halt($res);
//        halt($list);
//    }


}