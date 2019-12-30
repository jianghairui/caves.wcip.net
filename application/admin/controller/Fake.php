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
            $val['org'] = input('post.org');
            $val['role'] = input('post.role');
            $val['role_check'] = 2;
            $val['create_time'] = time();
            $val['fake'] = 1;

            $role['org'] = input('post.org');
            $role['desc'] = input('post.desc');
            $role['role'] = input('post.role');
            $role['tel'] = input('post.tel');
            $role['create_time'] = time();
            checkInput($val);
            checkInput($role);
            $val['avatar'] = input('post.avatar');
            $role['cover'] = input('post.cover');
            if(!$val['avatar'] || !$role['cover']) {
                return ajax('请上传图片',-1);
            }
            $role['province_code'] = input('post.provinceCode',0);
            $role['city_code'] = input('post.cityCode',0);
            $role['region_code'] = input('post.regionCode',0);
            if($role['role'] == 3) {
                if($role['province_code'] == 0 || $role['city_code'] == 0 || $role['region_code'] == 0) {
                    return ajax('省市区必选',-1);
                }
            }
            try {
                $whereOrg = [
                    ['org','=',$val['org']]
                ];
                $org_exist = Db::table('mp_user')->where($whereOrg)->find();
                if($org_exist) { return ajax('此公司已存在',-1); }

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

        try {
            $whereProvince = [
                ['pcode','=',0]
            ];
            $province_list = Db::table('mp_city')->where($whereProvince)->select();
            $city_list = [];
            $region_list = [];
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('province_list',$province_list);
        $this->assign('city_list',$city_list);
        $this->assign('region_list',$region_list);
        return $this->fetch();
    }

    public function roleDetail() {
        $id = input('param.id');
        $where = [
            ['u.id','=',$id]
        ];
        try {
            $info = Db::table('mp_user')->alias('u')
                ->join('mp_user_role r','u.id=r.uid','LEFT')
                ->field('u.id,u.avatar,u.nickname,u.org,r.role,r.name,r.identity,r.id_front,r.id_back,r.cover,r.tel,r.weixin,r.works,r.license,r.province_code,r.city_code,r.region_code,r.desc')
                ->where($where)
                ->find();
            $whereProvince = [
                ['pcode','=',0]
            ];
            $province_list = Db::table('mp_city')->where($whereProvince)->select();
            $city_list = [];
            $region_list = [];
            if($info['province_code']) {
                $whereCity = [
                    ['pcode','=',$info['province_code']]
                ];
                $city_list = Db::table('mp_city')->where($whereCity)->select();
            }
            if($info['city_code']) {
                $whereRegion = [
                    ['pcode','=',$info['city_code']]
                ];
                $region_list = Db::table('mp_city')->where($whereRegion)->select();
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        $this->assign('province_list',$province_list);
        $this->assign('city_list',$city_list);
        $this->assign('region_list',$region_list);
        return $this->fetch();
    }

    public function roleMod() {
        if(request()->isPost()) {
            $val['id'] = input('post.id');
            $val['nickname'] = input('post.nickname');
            $val['org'] = input('post.org');
            $val['role'] = input('post.role');
            $val['role_check'] = 2;
            $val['create_time'] = time();
            $val['fake'] = 1;

            $role['org'] = input('post.org');
            $role['desc'] = input('post.desc');
            $role['role'] = input('post.role');
            $role['tel'] = input('post.tel');
            $role['create_time'] = time();
            checkInput($val);
            checkInput($role);
            $avatar = input('post.avatar');
            $cover = input('post.cover');
            if(!$avatar || !$cover) {
                return ajax('请上传图片',-1);
            }
            $role['province_code'] = input('post.provinceCode',0);
            $role['city_code'] = input('post.cityCode',0);
            $role['region_code'] = input('post.regionCode',0);
            if($role['role'] == 3) {
                if($role['province_code'] == 0 || $role['city_code'] == 0 || $role['region_code'] == 0) {
                    return ajax('省市区必选',-1);
                }
            }
            try {

                $whereUser = [
                    ['id','=',$val['id']],
                    ['role','=',$val['role']]
                ];
                $whereRole = [
                    ['uid','=',$val['id']],
                    ['role','=',$val['role']]
                ];
                $user_exist = Db::table('mp_user')->where($whereUser)->find();
                $role_exist = Db::table('mp_user_role')->where($whereRole)->find();
                if(!$user_exist) { return ajax('非法参数',-1); }
                if(!$role_exist) { return ajax('非法参数',-1); }

                $whereOrg = [
                    ['org','=',$val['org']],
                    ['id','<>',$val['id']]
                ];
                $org_exist = Db::table('mp_user')->where($whereOrg)->find();
                if($org_exist) { return ajax('此公司已存在',-1); }

                $qiniu_exist = $this->qiniuFileExist($avatar);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'],5);
                }
                $qiniu_move = $this->moveFile($avatar,'upload/avatar/');

                if($qiniu_move['code'] == 0) {
                    $val['avatar'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],101);
                }

                $qiniu_exist = $this->qiniuFileExist($cover);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'],5);
                }
                $qiniu_move = $this->moveFile($cover,'upload/role/');

                if($qiniu_move['code'] == 0) {
                    $role['cover'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],101);
                }


                Db::startTrans();
                Db::table('mp_user')->where($whereUser)->update($val);
                Db::table('mp_user_role')->where($whereRole)->update($role);
                Db::commit();

            } catch (\Exception $e) {
                Db::rollback();
                if(isset($val['avatar']) && $val['avatar'] != $user_exist['avatar']) {
                    $this->rs_delete($val['avatar']);
                }
                if(isset($role['cover']) && $role['cover'] != $role_exist['cover']) {
                    $this->rs_delete($role['cover']);
                }
                return ajax($e->getMessage(), -1);
            }
            if(isset($val['avatar']) && $val['avatar'] != $user_exist['avatar']) {
                $this->rs_delete($user_exist['avatar']);
            }
            if(isset($role['cover']) && $role['cover'] != $role_exist['cover']) {
                $this->rs_delete($role_exist['cover']);
            }
            return ajax();
        }

    }

    public function cityList() {
        try {
            $list = Db::table('mp_city')->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $newlist = $this->recursion($list);

        halt($newlist);
        echo (json_encode($newlist));
    }
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

    private function recursion($list,$pcode=0)
    {
        $arr = array();
        foreach($list as $key=>$v)
        {
            if($v['pcode'] == $pcode)
            {
                $v['child'] = $this->recursion($list,$v['code']);
                $arr[] = $v;
            }
        }
        return $arr;
    }


}