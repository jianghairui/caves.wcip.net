<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/8/31
 * Time: 10:30
 */
namespace app\admin\controller;

use think\Db;
class Req extends Base {
    //活动列表
    public function reqList() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['r.del','=',0]
        ];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['r.status','=',$param['status']];
        }

        if($param['logmin']) {
            $where[] = ['r.create_time','>=',$param['logmin']];
        }

        if($param['logmax']) {
            $where[] = ['r.create_time','<=',$param['logmax']];
        }

        if($param['search']) {
            $where[] = ['r.title|r.org','like',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_req')->alias('r')
                ->join('mp_user_role ro','r.uid=ro.uid','left')
                ->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_req')->alias('r')
                ->join('mp_user_role ro','r.uid=ro.uid','left')
                ->field('r.*,ro.org')
                ->order(['r.id'=>'DESC'])
                ->where($where)
                ->order(['r.id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //活动详情
    public function reqDetail() {
        $id = input('param.id');
        try {
            $info = Db::table('mp_req')
                ->where('id','=',$id)
                ->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //添加活动
    public function reqAdd() {
        if(request()->isPost()) {
            $val['uid'] = input('post.uid');
            $val['title'] = input('post.title');
            $val['theme'] = input('post.theme');
            $val['explain'] = input('post.explain');
            $val['linkman'] = input('post.linkman');
            $val['tel'] = input('post.tel');
            $val['email'] = input('post.email');
            $val['start_time'] = input('post.start_time');
            $val['deadline'] = input('post.deadline');
            $val['vote_time'] = input('post.vote_time');
            $val['end_time'] = input('post.end_time');
            checkInput($val);
            $val['weixin'] = input('post.weixin');
            $val['status'] = 1;
            $val['start_time'] = strtotime($val['start_time'] . ' 00:00:00');
            $val['deadline'] = strtotime($val['deadline'] . ' 23:59:59');
            $val['vote_time'] = strtotime($val['vote_time'] . ' 23:59:59');
            $val['end_time'] = strtotime($val['end_time'] . ' 23:59:59');
            $cover = input('post.cover');
            try {
                if(!$cover) {
                    return ajax('请传入封面图',-1);
                }
                $whereUser = [
                    ['id','=',$val['uid']],
                    ['role','=',1],
                    ['role_check','=',2]
                ];
                $org_exist = Db::table('mp_user')->where($whereUser)->find();
                if(!$org_exist) {
                    return ajax('博物馆ID不存在',-1);
                }
                $val['org'] = $org_exist['org'];
                $qiniu_exist = $this->qiniuFileExist($cover);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'],-1);
                }

                $qiniu_move = $this->moveFile($cover,'upload/req/');
                if($qiniu_move['code'] == 0) {
                    $val['cover'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],-2);
                }
                Db::table('mp_req')->insert($val);
            } catch (\Exception $e) {
                $this->rs_delete($val['cover']);
                return ajax($e->getMessage(), -1);
            }
            return ajax();
        }
        try {
            $whereUser = [
                ['role','=',1],
                ['role_check','=',2]
            ];
            $list = Db::table('mp_user')->where($whereUser)->field('id,org')->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('list',$list);
        return $this->fetch();

    }
    //修改活动
    public function reqMod() {
        if(request()->isPost()) {
            $val['title'] = input('post.title');
            $val['theme'] = input('post.theme');
            $val['explain'] = input('post.explain');
            $val['linkman'] = input('post.linkman');
            $val['tel'] = input('post.tel');
            $val['email'] = input('post.email');
            $val['start_time'] = input('post.start_time');
            $val['deadline'] = input('post.deadline');
            $val['vote_time'] = input('post.vote_time');
            $val['end_time'] = input('post.end_time');
            $val['id'] = input('post.id');
            checkInput($val);
            $val['weixin'] = input('post.weixin');
            $val['status'] = 1;
            $val['start_time'] = strtotime($val['start_time'] . ' 00:00:00');
            $val['deadline'] = strtotime($val['deadline'] . ' 23:59:59');
            $val['vote_time'] = strtotime($val['vote_time'] . ' 23:59:59');
            $val['end_time'] = strtotime($val['end_time'] . ' 23:59:59');
            $cover = input('post.cover');
            try {
                if(!$cover) {
                    return ajax('请传入封面图',-1);
                }
                $where = [
                    ['id','=',$val['id']]
                ];
                $req_exist = Db::table('mp_req')->where($where)->find();
                if(!$req_exist) {
                    return ajax('非法操作',-1);
                }
                $qiniu_exist = $this->qiniuFileExist($cover);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'],-1);
                }
                $qiniu_move = $this->moveFile($cover,'upload/req/');
                if($qiniu_move['code'] == 0) {
                    $val['cover'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],-2);
                }
                Db::table('mp_req')->update($val);
            } catch (\Exception $e) {
                if($val['cover'] != $req_exist['cover']) {
                    $this->rs_delete($val['cover']);
                }
                return ajax($e->getMessage(), -1);
            }
            if($val['cover'] != $req_exist['cover']) {
                $this->rs_delete($req_exist['cover']);
            }
            return ajax();
        }
    }

    //活动审核-通过
    public function reqPass() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_req')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //活动审核-拒绝
    public function reqReject() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        $reason = input('post.reason','');
        try {
            $exist = Db::table('mp_req')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req')->where($map)->update(['status'=>2,'reason'=>$reason]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //删除活动
    public function reqDel() {
        $map = [
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_req')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req')->where($map)->update(['del'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //活动展示
    public function reqShow() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_req')->where($map)->update(['show'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //活动隐藏
    public function reqHide() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_req')->where($map)->update(['show'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //置顶、取消置顶
    public function reqRecommend() {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']]
            ];
            $exist = Db::table('mp_req')->where($where)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            if($exist['recommend'] == 1) {
                Db::table('mp_req')->where($where)->update(['recommend'=>0]);
                return ajax(0);
            }else {
                Db::table('mp_req')->where($where)->update(['recommend'=>1]);
                return ajax(1);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
    }

    public function ideaList() {
        $param['status'] = input('param.status','');
        $param['req_id'] = input('param.req_id');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['r.del','=',0]
        ];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['r.status','=',$param['status']];
        }

        if($param['req_id']) {
            $where[] = ['r.req_id','=',$param['req_id']];
        }

        if($param['search']) {
            $where[] = ['i.title','like',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_req_idea')->alias('i')
                ->join('mp_req r','i.req_id=r.id','left')
                ->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_req_idea')->alias('i')
                ->join('mp_req r','i.req_id=r.id','left')
                ->field('i.*,r.title AS req_title,r.org')
                ->order(['r.id'=>'DESC'])
                ->where($where)
                ->order(['r.id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        return $this->fetch();
    }

    public function ideaDetail() {
        $param['id'] = input('param.id','');
        try {
            $where = [
                ['i.id','=',$param['id']]
            ];
            $info = Db::table('mp_req_idea')->alias('i')
                ->join('mp_req r','i.req_id=r.id','left')
                ->join('mp_user u','i.uid=u.id','left')
                ->field('i.*,r.title AS req_title,r.org,u.nickname,u.avatar')
                ->where($where)
                ->find();
            if(!$info) {
                die('非法操作');
            }
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function ideaMod() {
        $val['id'] = input('post.id');
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        checkInput($val);
        try {
            $whereIdea = [['id','=',$val['id']]];
            $idea_exist = Db::table('mp_req_idea')->where($whereIdea)->find();
            if(!$idea_exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req_idea')->where($whereIdea)->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    public function ideaPass() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_req_idea')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req_idea')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function ideaReject() {
        $val['id'] = input('post.id','');
        $val['reason'] = input('post.reason','');
        checkInput($val);
        $map = [
            ['status','=',0],
            ['id','=',$val['id']]
        ];
        try {
            $exist = Db::table('mp_req_idea')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req_idea')->where($map)->update(['status'=>2,'reason'=>$val['reason']]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function workList() {
        return $this->fetch();
    }


}