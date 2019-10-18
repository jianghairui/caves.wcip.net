<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/10/18
 * Time: 9:54
 */
namespace app\admin\controller;

use think\Db;
class Fake extends Base {

    public function roleList() {
        $param['role'] = input('param.role','');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['role','IN',[1,2,3]],
            ['fake','=',1]
        ];

        if(!is_null($param['role']) && $param['role'] !== '') {
            $where[] = ['role','=',$param['role']];
        }
        if($param['datemin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['datemin'])))];
        }

        if($param['datemax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['datemax'])))];
        }

        if($param['search']) {
            $where[] = ['nickname|tel','like',"%{$param['search']}%"];
        }
        $order = ['id'=>'DESC'];
        try {
            $count = Db::table('mp_user')->where($where)->whereNotNull('nickname')->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_user')->where($where)->whereNotNull('nickname')
                ->order($order)
                ->limit(($curr_page - 1)*$perpage,$perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    public function roleAdd() {
        if(request()->isPost()) {
            $val['nickname'] = input('post.nickname');
            $val['avatar'] = input('post.avatar');
            $val['org'] = input('post.org');
            $val['role'] = input('post.role');
            $val['role_check'] = 2;
            $val['create_time'] = time();
            $val['fake'] = 1;

            $role['org'] = input('post.org');
            $role['desc'] = input('post.desc');
            $role['cover'] = input('post.cover');
            $role['role'] = input('post.role');
            $role['create_time'] = time();

            checkInput($val);
            checkInput($role);

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
        return $this->fetch();
    }

    public function roleDetail() {

    }

    public function roleMod() {

    }

    public function worksList() {

    }

    public function worksAdd() {

    }

    public function worksDetail() {

    }

    public function worksMod() {

    }


}