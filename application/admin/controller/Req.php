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
        $param['datetype'] = input('param.datetype','1');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $param['search'] = input('param.search');
        $param['state'] = input('param.state');
        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['r.del','=',0]
        ];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['r.status','=',$param['status']];
        }

        switch ($param['datetype']) {
            case '1':$condition_date = 'r.create_time';break;
            case '2':$condition_date = 'r.start_time';break;
            case '3':$condition_date = 'r.deadline';break;
            case '4':$condition_date = 'r.vote_time';break;
            case '5':$condition_date = 'r.end_time';break;
            default:$condition_date = 'r.create_time';
        }
        switch ($param['state']) {
            case '1'://未开始
                $where[] = ['r.start_time','>',time()];break;
            case '2'://创意中
                $where[] = ['r.start_time','<',time()];
                $where[] = ['r.deadline','>=',time()];break;
            case '3'://投票中
                $where[] = ['r.deadline','<',time()];
                $where[] = ['r.vote_time','>=',time()];break;
            case '4'://已结束
                $where[] = ['r.end_time','<',time()];break;
            default:;
        }
        if($param['datemin']) {
            $where[] = [$condition_date,'>=',strtotime($param['datemin'])];
        }
        if($param['datemax']) {
            $where[] = [$condition_date,'<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['datemax'])))];
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
            $val['desc'] = input('post.desc');
            checkInput($val);
            $val['weixin'] = input('post.weixin');
            $val['status'] = 1;
            $val['start_time'] = strtotime($val['start_time'] . ' 00:00:00');
            $val['deadline'] = strtotime($val['deadline'] . ' 23:59:59');
            $val['vote_time'] = strtotime($val['vote_time'] . ' 23:59:59');
            $val['end_time'] = strtotime($val['end_time'] . ' 23:59:59');

            $use_video = input('post.use_video',0);
            $cover = input('post.cover');
            $video_url = input('post.video_url');
            try {
                if(!$cover) {
                    return ajax('请传入封面图',-1);
                }
                if($use_video) {
                    if(!$video_url) {
                        return ajax('请上传视频',-1);
                    }
                    $val['use_video'] = 1;
                }else {
                    $val['use_video'] = 0;
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
                if($use_video) {
                    $qiniu_exist = $this->qiniuFileExist($video_url);
                    if($qiniu_exist !== true) {
                        return ajax($qiniu_exist['msg'],-1);
                    }
                    $qiniu_move = $this->moveFile($video_url,'upload/req/');
                    if($qiniu_move['code'] == 0) {
                        $val['video_url'] = $qiniu_move['path'];
                    }else {
                        return ajax($qiniu_move['msg'],-2);
                    }
                }
                Db::table('mp_req')->insert($val);
                Db::table('mp_user')->where('id','=',$val['uid'])->setInc('req_num',1);
            } catch (\Exception $e) {
                $this->rs_delete($val['cover']);
                if($use_video) {
                    $this->rs_delete($val['video_url']);
                }
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
            $val['desc'] = input('post.desc');
            $val['id'] = input('post.id');
            checkInput($val);
            $val['weixin'] = input('post.weixin');
            $val['status'] = 1;
            $val['start_time'] = strtotime($val['start_time'] . ' 00:00:00');
            $val['deadline'] = strtotime($val['deadline'] . ' 23:59:59');
            $val['vote_time'] = strtotime($val['vote_time'] . ' 23:59:59');
            $val['end_time'] = strtotime($val['end_time'] . ' 23:59:59');
            $use_video = input('post.use_video',0);
            $cover = input('post.cover');
            $video_url = input('post.video_url');
            try {
                if(!$cover) {
                    return ajax('请传入封面图',-1);
                }
                if($use_video) {
                    if(!$video_url) {
                        return ajax('请上传视频',-1);
                    }
                    $val['use_video'] = 1;
                }else {
                    $val['use_video'] = 0;
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
                if($use_video) {
                    $qiniu_exist = $this->qiniuFileExist($video_url);
                    if($qiniu_exist !== true) {
                        return ajax($qiniu_exist['msg'],-1);
                    }
                    $qiniu_move = $this->moveFile($video_url,'upload/req/');
                    if($qiniu_move['code'] == 0) {
                        $val['video_url'] = $qiniu_move['path'];
                    }else {
                        return ajax($qiniu_move['msg'],-2);
                    }
                }
                Db::table('mp_req')->update($val);
            } catch (\Exception $e) {
                if($val['cover'] != $req_exist['cover']) {
                    $this->rs_delete($val['cover']);
                }
                if($use_video) {
                    if($val['video_url'] != $req_exist['video_url']) {
                        $this->rs_delete($val['video_url']);
                    }
                }
                return ajax($e->getMessage(), -1);
            }
            if($val['cover'] != $req_exist['cover']) {
                $this->rs_delete($req_exist['cover']);
            }
            if($use_video) {
                if($val['video_url'] != $req_exist['video_url']) {
                    $this->rs_delete($req_exist['video_url']);
                }
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
            //todo  奖励用户积分
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

    //创意列表
    public function ideaList() {
        $param['status'] = input('param.status','');
        $param['req_id'] = input('param.req_id');
        $param['search'] = input('param.search');
        $param['sort'] = input('param.sort');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['i.del','=',0]
        ];
        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['i.status','=',$param['status']];
        }

        if($param['req_id']) {
            $where[] = ['i.req_id','=',$param['req_id']];
        }
        if($param['search']) {
            $where[] = ['i.title','like',"%{$param['search']}%"];
        }

        switch ($param['sort']) {
            case '1':$order = ['i.vote'=>'DESC'];break;
            case '2':$order = ['i.vote'=>'ASC'];break;
            case '3':$order = ['i.works_num'=>'DESC'];break;
            case '4':$order = ['i.works_num'=>'ASC'];break;
            default:$order = ['i.id'=>'DESC'];
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
                ->field('i.*,r.title AS req_title,r.org')->where($where)
                ->order($order)->limit(($curr_page - 1)*$perpage,$perpage)->select();
            $whereReq = [
                ['del','=',0]
            ];
            $reqlist = Db::table('mp_req')->where($whereReq)->field('id,title')->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        $this->assign('reqlist',$reqlist);
        return $this->fetch();
    }
    //创意详情
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
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //创意编辑
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
    //创意审核-通过
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
            //todo  奖励用户积分
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //创意审核-拒绝
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
    //创意审核-拒绝
    public function ideaDel() {
        $val['id'] = input('post.id','');
        checkInput($val);
        $map = [
            ['id','=',$val['id']],
            ['del','=',0]
        ];
        try {
            $exist = Db::table('mp_req_idea')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req_idea')->where($map)->update(['del'=>1]);
            Db::table('mp_req')->where('id','=',$exist['req_id'])->setDec('idea_num',1);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }
    //作品列表
    public function workList() {
        $param['status'] = input('param.status','');
        $param['req_id'] = input('param.req_id');
        $param['idea_id'] = input('param.idea_id');
        $param['search'] = input('param.search');
        $param['sort'] = input('param.sort');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['w.del','=',0]
        ];
        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['r.status','=',$param['status']];
        }
        if($param['req_id']) {
            $where[] = ['w.req_id','=',$param['req_id']];
        }
        if($param['search']) {
            $where[] = ['w.title','like',"%{$param['search']}%"];
        }
        switch ($param['sort']) {
            case '1':$order = ['w.vote'=>'DESC'];break;
            case '2':$order = ['w.vote'=>'ASC'];break;
            case '3':$order = ['w.bid_num'=>'DESC'];break;
            case '4':$order = ['w.bid_num'=>'ASC'];break;
            default:$order = ['w.id'=>'DESC'];
        }
        try {
            $count = Db::table('mp_req_works')->alias('w')
                ->join('mp_req r','w.req_id=r.id','left')
                ->join('mp_req_idea i','w.idea_id=i.id','left')
                ->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_req_works')->alias('w')
                ->join('mp_req r','w.req_id=r.id','left')
                ->join('mp_req_idea i','w.idea_id=i.id','left')
                ->field('w.*,r.title AS req_title,r.org,i.title AS idea_title')
                ->where($where)
                ->order($order)->limit(($curr_page - 1)*$perpage,$perpage)->select();
            $whereReq = [
                ['del','=',0]
            ];
            $reqlist = Db::table('mp_req')->where($whereReq)->field('id,title')->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        $this->assign('reqlist',$reqlist);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //作品详情
    public function workDetail() {
        $param['id'] = input('param.id','');
        try {
            $where = [
                ['w.id','=',$param['id']]
            ];
            $info = Db::table('mp_req_works')->alias('w')
                ->join('mp_req r','w.req_id=r.id','left')
                ->join('mp_req_idea i','w.idea_id=i.id','left')
                ->join('mp_user u','w.uid=u.id','left')
                ->field('w.*,r.title AS req_title,r.org,u.nickname,u.avatar,i.title AS idea_title,i.content AS idea_content')
                ->where($where)
                ->find();
            if(!$info) {
                die('非法操作');
            }
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //作品编辑
    public function workMod() {
        $val['id'] = input('post.id');
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        checkInput($val);
        try {
            $whereIdea = [['id','=',$val['id']]];
            $idea_exist = Db::table('mp_req_works')->where($whereIdea)->find();
            if(!$idea_exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req_works')->where($whereIdea)->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //作品审核-通过
    public function workPass() {
        $whereWorks = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_req_works')->where($whereWorks)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            $whereReq = [
                ['id','=',$exist['req_id']]
            ];

            Db::table('mp_req_works')->where($whereWorks)->update(['status'=>1]);
            Db::table('mp_req')->where($whereReq)->setInc('works_num',1);

            if($exist['idea_id']) {
                $whereIdea = [
                    ['id','=',$exist['idea_id']]
                ];
                Db::table('mp_req_idea')->where($whereIdea)->setInc('works_num',1);
            }
            Db::table('mp_user')->where('id','=',$exist['uid'])->setInc('works_num',1);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //作品审核-拒绝
    public function workReject() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        $reason = input('post.reason','');
        try {
            $exist = Db::table('mp_req_works')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_req_works')->where($map)->update(['status'=>2,'reason'=>$reason]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //作品显示
    public function workShow() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_req_works')->where($map)->update(['show'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //作品隐藏
    public function workHide() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_req_works')->where($map)->update(['show'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //作品删除-拒绝
    public function workDel() {
        $map = [
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_req_works')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            $whereReq = [
                ['id','=',$exist['req_id']]
            ];
            Db::table('mp_req_works')->where($map)->update(['del'=>1]);
            Db::table('mp_req')->where($whereReq)->setDec('works_num',1);
            if($exist['idea_id']) {
                $whereIdea = [
                    ['id','=',$exist['idea_id']]
                ];
                Db::table('mp_req_idea')->where($whereIdea)->setDec('works_num',1);
            }
            Db::table('mp_user')->where('id','=',$exist['uid'])->setDec('works_num',1);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }



    //作品列表
    public function showWorkList() {
        $param['status'] = input('param.status','');
        $param['search'] = input('param.search');
        $param['sort'] = input('param.sort');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['del','=',0]
        ];
        $order = ['id'=>'DESC'];
        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['status','=',$param['status']];
        }
        if($param['search']) {
            $where[] = ['title','like',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_show_works')
                ->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_show_works')
                ->where($where)
                ->order($order)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    //作品详情
    public function showWorkDetail() {
        $param['id'] = input('param.id','');
        try {
            $where = [
                ['w.id','=',$param['id']]
            ];
            $info = Db::table('mp_show_works')->alias('w')
                ->join('mp_user u','w.uid=u.id','left')
                ->where($where)
                ->field('w.*,u.nickname,u.avatar')
                ->find();
            if(!$info) {
                die('非法操作');
            }
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    //作品审核-通过
    public function showWorkPass() {
        $whereWorks = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_show_works')->where($whereWorks)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_show_works')->where($whereWorks)->update(['status'=>1]);
            Db::table('mp_user')->where('id','=',$exist['uid'])->setInc('works_show_num',1);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //作品审核-拒绝
    public function showWorkReject() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        $reason = input('post.reason','');
        try {
            $exist = Db::table('mp_show_works')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_show_works')->where($map)->update(['status'=>2,'reason'=>$reason]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //作品显示
    public function showWorkShow() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_show_works')->where($map)->update(['show'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //作品隐藏
    public function showWorkHide() {
        $map[] = ['id','=',input('post.id',0)];
        try {
            Db::table('mp_show_works')->where($map)->update(['show'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //作品删除-拒绝
    public function showWorkDel() {
        $map = [
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_show_works')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_show_works')->where($map)->update(['del'=>1]);
            Db::table('mp_user')->where('id','=',$exist['uid'])->setDec('works_show_num',1);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }




}