<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/8/31
 * Time: 15:25
 */
namespace app\admin\controller;

use think\Db;
class Funding extends Base {

    //众筹列表
    public function fundingList() {
        $param['status'] = input('param.status','');
        $param['req_id'] = input('param.req_id');
        $param['work_id'] = input('param.work_id');
        $param['idea_id'] = input('param.idea_id');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['f.del','=',0]
        ];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['f.status','=',$param['status']];
        }
        if($param['req_id']) {
            $where[] = ['f.req_id','=',$param['req_id']];
        }
        if($param['work_id']) {
            $where[] = ['f.work_id','=',$param['work_id']];
        }
        if($param['search']) {
            $where[] = ['f.title','like',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_funding')->alias('f')
                ->join('mp_req r','f.req_id=r.id','left')
                ->join('mp_req_works w','f.work_id=w.id','left')
                ->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_funding')->alias('f')
                ->join('mp_req r','f.req_id=r.id','left')
                ->join('mp_req_works w','f.work_id=w.id','left')
                ->join('mp_req_idea i','f.idea_id=i.id','left')
                ->join('mp_user_role role','f.factory_id=role.uid','left')
                ->field('f.*,r.title AS req_title,w.title AS work_title,i.title AS idea_title,role.org AS factory_name')
                ->order(['f.id'=>'DESC'])
                ->where($where)
                ->order(['r.id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //发起众筹
    public function fundingAdd() {
        if(request()->isPost()) {
            $val['work_id'] = input('post.work_id');
            $val['title'] = input('post.title');
            $val['need_money'] = input('post.need_money');
            $val['start_time'] = input('post.start_time');
            $val['end_time'] = input('post.end_time');
            $val['desc'] = input('post.desc');
            checkInput($val);
            $val['content'] = input('post.content');
            $val['status'] = 1;
            $val['start_time'] = strtotime($val['start_time'] . '00:00:00');
            $val['end_time'] = strtotime($val['end_time'] . ' 23:59:59');
            $val['create_time'] = time();
            $cover = input('post.cover');
            try {
                if(!$cover) {
                    return ajax('请传入封面图',-1);
                }
                $whereWork = [
                    ['id','=',$val['work_id']],
                    ['del','=',0]
                ];
                $work_exist = Db::table('mp_req_works')
                    ->where($whereWork)->where('factory_id','NOT NULL')->find();
                if(!$work_exist) {
                    return ajax('非法参数',-1);
                }
                $val['req_id'] = $work_exist['req_id'];
                $val['idea_id'] = $work_exist['idea_id'];
                $val['factory_id'] = $work_exist['factory_id'];
                $whereFunding = [
                    ['work_id','=',$val['work_id']],
                    ['del','=',0]
                ];
                $funding_exist = Db::table('mp_funding')->where($whereFunding)->find();
                if($funding_exist) {
                    return ajax('此作品已发起过众筹',-1);
                }
                $qiniu_exist = $this->qiniuFileExist($cover);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'],-1);
                }

                $qiniu_move = $this->moveFile($cover,'upload/funding/');
                if($qiniu_move['code'] == 0) {
                    $val['cover'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],-2);
                }
                Db::table('mp_funding')->insert($val);
            } catch (\Exception $e) {
                if(isset($val['cover'])) {
                    $this->rs_delete($val['cover']);
                }
                return ajax($e->getMessage(), -1);
            }
            return ajax();
        }
        try {
            $worklist = Db::table('mp_req_works')->where('factory_id','NOT NULL')->field('id,title')->select();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('worklist',$worklist);
        return $this->fetch();
    }
    //众筹详情
    public function fundingDetail() {
        $param['id'] = input('param.id','');
        try {
            $where = [
                ['f.id','=',$param['id']]
            ];
            $info = Db::table('mp_funding')->alias('f')
                ->join('mp_req_works w','f.work_id=w.id','left')
                ->where($where)->find();
            if(!$info) { die('非法操作');}
            $worklist = Db::table('mp_req_works')->where('factory_id','NOT NULL')->field('id,title')->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        $this->assign('worklist',$worklist);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //编辑众筹
    public function fundingMod() {
        if(request()->isPost()) {
            $val['work_id'] = input('post.work_id');
            $val['title'] = input('post.title');
            $val['need_money'] = input('post.need_money');
            $val['start_time'] = input('post.start_time');
            $val['end_time'] = input('post.end_time');
            $val['desc'] = input('post.desc');
            $val['id'] = input('post.id');
            checkInput($val);
            $val['content'] = input('post.content');
            $val['status'] = 1;
            $val['start_time'] = strtotime($val['start_time'] . '00:00:00');
            $val['end_time'] = strtotime($val['end_time'] . ' 23:59:59');
            $val['create_time'] = time();
            $cover = input('post.cover');
            try {
                if(!$cover) {
                    return ajax('请传入封面图',-1);
                }
                $whereWork = [
                    ['id','=',$val['work_id']],
                    ['del','=',0]
                ];
                $work_exist = Db::table('mp_req_works')
                    ->where($whereWork)->where('factory_id','NOT NULL')->find();
                if(!$work_exist) {
                    return ajax('非法参数',-1);
                }
                $val['req_id'] = $work_exist['req_id'];
                $val['idea_id'] = $work_exist['idea_id'];
                $val['factory_id'] = $work_exist['factory_id'];
                $whereFunding = [
                    ['work_id','=',$val['work_id']],
                    ['del','=',0]
                ];
                $funding_exist = Db::table('mp_funding')->where($whereFunding)->find();
                if($funding_exist) {
                    return ajax('此作品已发起过众筹',-1);
                }
                $qiniu_exist = $this->qiniuFileExist($cover);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'],-1);
                }

                $qiniu_move = $this->moveFile($cover,'upload/funding/');
                if($qiniu_move['code'] == 0) {
                    $val['cover'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],-2);
                }
                Db::table('mp_funding')->insert($val);
            } catch (\Exception $e) {
                if(isset($val['cover'])) {
                    $this->rs_delete($val['cover']);
                }
                return ajax($e->getMessage(), -1);
            }
            return ajax();
        }
    }
    //隐藏众筹
    public function fundingHide() {

    }
    //显示
    public function fundingShow() {

    }
    //众筹终止,用户退款,不可逆操作
    public function fundingStop() {

    }
    //置顶,取消置顶
    public function recommend() {

    }

    public function goodsList() {

    }

    public function goodsAdd() {

    }

    public function goodsDetail() {

    }

    public function goodsMod() {

    }

    public function goodsDel() {

    }





}