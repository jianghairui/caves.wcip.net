<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/8
 * Time: 11:11
 */

namespace app\api\controller;

use think\Db;
use think\Exception;

class Api extends Common
{
    //获取轮播图列表
    public function slideList() {
        $where = [
            ['status', '=', 1]
        ];
        try {
            $list = Db::table('mp_slideshow')->where($where)
                ->field('id,title,url,pic')
                ->order(['sort' => 'ASC'])->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //获取活动列表
    public function getReqList()
    {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        $where = [
            ['status', '=', 1],
            ['show', '=', 1],
            ['del', '=', 0]
        ];
        try {
            $list = Db::table('mp_req')
                ->where($where)->order(['start_time' => 'ASC'])
                ->field("id,title,works_num,idea_num,cover,org,start_time,end_time")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //获取活动详情
    public function getReqDetail() {
        $val['req_id'] = input('post.req_id');
        checkPost($val);
        try {
            $where = [
                ['status', '=', 1],
                ['show', '=', 1],
                ['del', '=', 0],
                ['id', '=', $val['req_id']],
            ];
            $info = Db::table('mp_req')
                ->field('id,uid,title,cover,theme,explain,desc,org,linkman,tel,email,weixin,start_time,deadline,vote_time,end_time,works_num,idea_num')
                ->where($where)->find();
            if (!$info) {
                return ajax($val['req_id'], -4);
            }
            $user = Db::table('mp_user')->where('id','=',$info['uid'])->field('nickname,avatar')->find();
            $info['nickname'] = $user['nickname'];
            $info['avatar'] = $user['avatar'];
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        if ($info['start_time'] <= time() && $info['deadline'] > time()) {
            $info['join_btn'] = true;
        } else {
            $info['join_btn'] = false;
        }
        if ($info['deadline'] <= time() && $info['vote_time'] > time()) {
            $info['vote_btn'] = true;
        } else {
            $info['vote_btn'] = false;
        }
        if ($info['start_time'] <= time() && $info['end_time'] > time()) {
            $info['bidding_btn'] = true;
        } else {
            $info['bidding_btn'] = false;
        }
        return ajax($info);
    }
    //提出创意
    public function createIdea() {
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        $val['req_id'] = input('post.req_id');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
        $val['create_time'] = time();
        $val['status'] = 1;
        if(!$this->msgSecCheck($val['title'])) {
            return ajax('标题包含敏感词',63);
        }
        if(!$this->msgSecCheck($val['content'])) {
            return ajax('内容包含敏感词',64);
        }
        try {
            $whereReq = [
                ['id', '=', $val['req_id']]
            ];
            $exist = Db::table('mp_req')->where($whereReq)->find();
            if (!$exist) {
                return ajax('非法参数req_id', -4);
            }
            if ($exist['start_time'] > time()) {
                return ajax('活动未开始', 26);
            }
            if ($exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if ($exist['deadline'] <= time()) {
                return ajax('创意时间已结束', 57);
            }
            if (!$this->myinfo['user_auth']) {
                return ajax('用户未授权', 56);
            }
            Db::table('mp_req')->where($whereReq)->setInc('idea_num',1);
            Db::table('mp_req_idea')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //创意列表
    public function ideaList() {
        $val['req_id'] = input('post.req_id');
        $val['order'] = input('post.order',1);
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',20);
        try {
            $where = [
                ['i.status','=',1]
            ];
            if($val['req_id']) {
                $where[] = ['i.req_id','=',$val['req_id']];
            }
            if($val['order'] == 1) {
                $order = ['i.id'=>'DESC'];
            }else {
                $order = ['i.vote'=>'DESC'];
            }
            $list = Db::table('mp_req_idea')->alias('i')
                ->join('mp_user u','i.uid=u.id','left')
                ->where($where)
                ->field('i.id,i.title,i.content,i.works_num,i.vote,i.create_time,u.nickname,u.avatar')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
            $myvote = Db::table('mp_idea_vote')->where('uid','=',$this->myinfo['id'])->column('idea_id');
            foreach ($list as &$v) {
                if(in_array($v['id'],$myvote)) {
                    $v['if_vote'] = true;
                }else {
                    $v['if_vote'] = false;
                }
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //创意详情
    public function ideaDetail() {
        $val['idea_id'] = input('post.idea_id');
        checkPost($val);
        try {
            $whereIdea = [
                ['i.id','=',$val['idea_id']]
            ];
            $info = Db::table('mp_req_idea')->alias('i')
                ->join('mp_user u','i.uid=u.id','left')
                ->join('mp_req r','i.req_id=r.id','left')
                ->where($whereIdea)
                ->field('i.id,i.title,i.content,i.works_num,i.vote,i.create_time,u.nickname,u.avatar,i.req_id,r.title AS req_title,r.cover,r.org')
                ->find();
            if(!$info) {
                return ajax('invalid idea_id',-4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }

    //我要参加
    public function takePartIn() {
        $val['req_id'] = input('post.req_id');
        checkPost($val);
        $user = $this->myinfo;
        try {
            $where = [
                ['id', '=', $val['req_id']]
            ];
            $exist = Db::table('mp_req')->where($where)->find();
            if (!$exist) {
                return ajax('非法参数', -4);
            }
            if ($exist['start_time'] > time()) {
                return ajax('活动未开始', 26);
            }
            if ($exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if ($exist['deadline'] <= time()) {
                return ajax('报名时间已结束', 27);
            }
            if ($user['role'] != 2) {
                return ajax('只有设计师可以参加', 28);
            }
            if ($user['role_check'] != 2) {
                return ajax('角色未认证', 29);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //上传参赛作品
    public function uploadWorks() {
        $val['uid'] = $this->myinfo['id'];
        $val['req_id'] = input('post.req_id');
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        checkPost($val);
        $val['idea_id'] = input('post.idea_id');
        $val['create_time'] = time();
        $user = $this->myinfo;
        $image = input('post.pics', []);
        if(!$this->msgSecCheck($val['title'])) {
            return ajax('标题包含敏感词',63);
        }
        if(!$this->msgSecCheck($val['desc'])) {
            return ajax('内容包含敏感词',64);
        }
        if (is_array($image) && !empty($image)) {
            if (count($image) > 9) {
                return ajax('最多上传9张图片', 67);
            }
        } else {
            return ajax('请传入图片', 3);
        }
        try {
            $whereReq = [
                ['id', '=', $val['req_id']]
            ];
            $exist = Db::table('mp_req')->where($whereReq)->find();
            if (!$exist) {
                return ajax('非法参数req_id', -4);
            }
            if ($exist['start_time'] > time()) {
                return ajax('活动未开始', 26);
            }
            if ($exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if ($exist['deadline'] <= time()) {
                return ajax('投稿时间已结束', 27);
            }
            if ($user['role'] != 2) {
                return ajax('只有设计师可以参加', 28);
            }
            if (!$user['user_auth']) {
                return ajax('用户未授权', 56);
            }
            if ($user['role_check'] != 2) {
                return ajax('角色还未认证', 29);
            }
            if($val['idea_id']) {
                $whereIdea = [
                    ['id','=',$val['idea_id']]
                ];
                $idea_exist = Db::table('mp_req_idea')->where($whereIdea)->find();
                if(!$idea_exist) {
                    return ajax('非法参数idea_id',-4);
                }
//                $whereWork = [
//                    ['req_id', '=', $val['req_id']],
//                    ['idea_id', '=', $val['idea_id']],
//                    ['uid', '=', $this->myinfo['id']]
//                ];
//                $workExist = Db::table('mp_req_works')->where($whereWork)->find();
//                if ($workExist) {
//                    return ajax('已投过此创意,不可重复投稿', 58);
//                }
            }
            //七牛云上传多图
            $image_array = [];
            foreach ($image as $v) {
                $qiniu_exist = $this->qiniuFileExist($v);
                if($qiniu_exist !== true) {
                    return ajax('图片已失效请重新上传',66);
                }
            }
            foreach ($image as $v) {
                $qiniu_move = $this->moveFile($v,'upload/works/');
                if($qiniu_move['code'] == 0) {
                    $image_array[] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],101);
                }
            }
            $val['pics'] = serialize($image_array);

            Db::table('mp_req_works')->insert($val);
            Db::table('mp_req')->where($whereReq)->setInc('works_num',1);
            if($val['idea_id']) {
                Db::table('mp_req_idea')->where($whereIdea)->setInc('works_num',1);
            }
        } catch (\Exception $e) {
            foreach ($image_array as $v) {
                $this->rs_delete($v);
            }
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //获取参赛作品列表
    public function worksList() {
        $val['req_id'] = input('post.req_id');
        $val['idea_id'] = input('post.idea_id');
        $val['order'] = input('post.order',1);
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        $where = [
            ['w.status','=',1]
        ];
        try {
            if($val['req_id']) {
                $where[] = ['w.req_id', '=', $val['req_id']];
            }
            if($val['idea_id']) {
                $where[] = ['w.idea_id', '=', $val['idea_id']];
            }
            if($val['order'] == 1) {
                $order = ['w.id'=>'DESC'];
            }else {
                $order = ['w.vote'=>'DESC'];
            }
            $list = Db::table('mp_req_works')->alias('w')
                ->join("mp_req r", "w.req_id=r.id", "left")
                ->join("mp_user u", "w.uid=u.id", "left")
                ->where($where)
                ->field("w.id,w.title,w.vote,w.pics,w.bid_num,w.desc,w.create_time,u.nickname,u.avatar")
                ->order($order)
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
            $myvote = Db::table('mp_works_vote')->where('uid','=',$this->myinfo['id'])->column('work_id');
            foreach ($list as &$v) {
                if(in_array($v['id'],$myvote)) {
                    $v['if_vote'] = true;
                }else {
                    $v['if_vote'] = false;
                }
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }
    //参赛作品详情
    public function worksDetail() {
        $val['id'] = input('post.id');
        checkPost($val);
        try {
            $whereWorks = [
                ['w.id', '=',$val['id']]
            ];
            $exist = Db::table('mp_req_works')->alias('w')
                ->join("mp_req_idea i", "w.idea_id=i.id", "left")
                ->join("mp_user u", "w.uid=u.id", "left")
                ->where($whereWorks)
                ->field("w.id,w.uid,w.title,w.desc,w.pics,w.vote,w.bid_num,w.req_id,w.create_time,w.status,u.avatar,u.nickname,i.title AS idea_title,i.vote AS idea_vote")
                ->find();
            if (!$exist) {
                return ajax($val['id'], -4);
            }
            $bidding_exist = Db::table('mp_bidding')->where([
                ['work_id', '=', $val['id']],
                ['uid', '=', $this->myinfo['id']]
            ])->find();
            if ($bidding_exist) {
                $exist['bidding_btn'] = true;
                $where_req = [['id','=',$exist['req_id']]];
                $req_exist = Db::table('mp_req')->where($where_req)->find();
                if ($req_exist['end_time'] <= time()) {
                    $exist['bidding_btn'] = false;
                }
            } else {
                $exist['bidding_btn'] = false;
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $exist['pics'] = unserialize($exist['pics']);
        return ajax($exist);
    }
    //工厂接单竞标
    public function bidding() {
        $val['work_id'] = input('post.work_id');
        checkPost($val);
        $val['desc'] = input('post.desc');
        $user = $this->myinfo;
        $val['uid'] = $user['id'];
        $val['create_time'] = time();
        try {
            $where_work = [
                ['id', '=', $val['work_id']]
            ];
            $work_exist = Db::table('mp_req_works')->where($where_work)->find();
            if (!$work_exist) {
                return ajax('非法参数work_id', -4);
            }

            $bidding_exist = Db::table('mp_bidding')->where([
                ['work_id', '=', $val['work_id']],
                ['uid', '=', $val['uid']]
            ])->find();
            if ($bidding_exist) {
                return ajax('已经参与竞标', 37);
            }

            if ($user['role'] != 3) {
                return ajax('只有工厂可以参加竞标', 35);
            }
            if ($user['role_check'] != 2) {
                return ajax('角色未认证', 29);
            }
            $where_req = [
                ['id', '=', $work_exist['req_id']]
            ];
            $req_exist = Db::table('mp_req')->where($where_req)->find();
            $val['req_id'] = $req_exist['id'];
            if ($req_exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            Db::table('mp_bidding')->insert($val);
            Db::table('mp_req_works')->where($where_work)->setInc('bid_num',1);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //获取竞标列表
    public function biddingList() {
        $val['work_id'] = input('post.work_id');
        checkPost($val);
        try {
            $where = [
                ['b.work_id', '=', $val['work_id']]
            ];
            $list = Db::table('mp_bidding')->alias("b")
                ->join("mp_user u", "b.uid=u.id", "left")
                ->field("b.*,u.nickname,u.org,u.avatar")
                ->where($where)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //作品投票
    public function worksVote()
    {
        $val['work_id'] = input('post.work_id');
        checkPost($val);
        $user = $this->myinfo;
        try {
            $whereWork = [
                ['id', '=', $val['work_id']]
            ];
            $workExist = Db::table('mp_req_works')->where($whereWork)->find();
            if (!$workExist) {
                return ajax($val['work_id'], -4);
            }

            $whereVote = [
                ['work_id', '=', $val['work_id']],
                ['uid', '=', $this->myinfo['id']]
            ];
            $vote_exist = Db::table('mp_works_vote')->where($whereVote)->find();
            if ($vote_exist) {
                return ajax('只能投一次票', 32);
            }

            $whereReq = [
                ['id', '=', $workExist['req_id']]
            ];
            $req_exist = Db::table('mp_req')->where($whereReq)->find();
            if ($req_exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if ($req_exist['vote_time'] <= time()) {
                return ajax('投票时间已结束', 30);
            }
            $insert_data = [
                'work_id' => $val['work_id'],
                'uid' => $this->myinfo['id'],
                'vip' => $user['vip'],
                'req_id' => $workExist['req_id'],
                'create_time' => time()
            ];
            Db::table('mp_works_vote')->insert($insert_data);
            Db::table('mp_req_works')->where($whereWork)->setInc('vote', 1);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //创意投票
    public function ideaVote()
    {
        $val['idea_id'] = input('post.idea_id');
        checkPost($val);
        $user = $this->myinfo;
        try {
            $whereIdea = [
                ['id', '=', $val['idea_id']]
            ];
            $ideaExist = Db::table('mp_req_idea')->where($whereIdea)->find();
            if (!$ideaExist) {
                return ajax('非法参数idea_id', -4);
            }

            $whereVote = [
                ['idea_id', '=', $val['idea_id']],
                ['uid', '=', $this->myinfo['id']]
            ];
            $vote_exist = Db::table('mp_idea_vote')->where($whereVote)->find();
            if ($vote_exist) {
                return ajax('只能投一次票', 32);
            }

            $whereReq = [
                ['id', '=', $ideaExist['req_id']]
            ];
            $req_exist = Db::table('mp_req')->where($whereReq)->find();
            if ($req_exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if ($req_exist['vote_time'] <= time()) {
                return ajax('投票时间已结束', 30);
            }
            $insert_data = [
                'idea_id' => $val['idea_id'],
                'uid' => $this->myinfo['id'],
                'vip' => $user['vip'],
                'req_id' => $ideaExist['req_id'],
                'create_time' => time()
            ];
            Db::table('mp_idea_vote')->insert($insert_data);
            Db::table('mp_req_idea')->where($whereIdea)->setInc('vote', 1);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //众筹列表
    public function fundingList() {
        $param['search'] = input('post.search');
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);

        $where = [
            ['del','=',0]
        ];
        if($param['search']) {
            $where[] = ['f.title','like',"%{$param['search']}%"];
        }
        try {
            $list = Db::table('mp_funding')
                ->field('id,title,cover,need_money,curr_money,order_num,start_time,end_time')
                ->where($where)
                ->order(['id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        return ajax($list);
    }
    //众筹列表
    public function allFundingList() {
        $param['req_id'] = input('post.req_id','');
        $param['status'] = input('post.status','');
        $param['search'] = input('post.search');
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);

        $where = [
            ['del','=',0]
        ];
        if($param['req_id']) {
            $where[] = ['req_id','=',$param['req_id']];
        }
        if($param['status'] !== '' && !is_null($param['status'])) {
            $where[] = ['status','=',$param['status']];
        }
        if($param['search']) {
            $where[] = ['title','like',"%{$param['search']}%"];
        }

        try {
            $list = Db::table('mp_funding')
                ->field('id,title,cover,need_money,curr_money,order_num,start_time,end_time,status')
                ->where($where)
                ->order(['id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        return ajax($list);
    }
    //众筹详情
    public function fundingDetail() {
        $val['id'] = input('param.id','');
        checkPost($val);
        try {
            $where = [
                ['f.id','=',$val['id']],
                ['f.del','=',0]
            ];
            $info = Db::table('mp_funding')->alias('f')
                ->join('mp_req r','f.req_id=r.id','left')
                ->join('mp_req_idea i','f.idea_id=i.id','left')
                ->join('mp_req_works w','f.work_id=w.id','left')
                ->field('f.id,f.title,f.cover,f.need_money,f.curr_money,f.order_num,f.start_time,f.end_time,r.title AS req_title,r.explain AS req_detail,i.title AS idea_title,i.content AS idea_detail,w.uid,w.title AS work_title,w.desc AS work_detail,w.pics AS works_pics,f.desc,f.content')
                ->where($where)->find();
            $whereUser = [
                ['id','=',$info['uid']]
            ];
            $designer = Db::table('mp_user')->where($whereUser)->field('id,nickname,avatar')->find();
            $info['nickname'] = $designer['nickname'];
            $info['avatar'] = $designer['avatar'];
            $info['time_count'] = $info['end_time'] - time();
            if(!$info) { return ajax('非法参数id',-4);}
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $info['works_pics'] = unserialize($info['works_pics']);
        return ajax($info);
    }
    //众筹商品列表
    public function fundingGoodsList() {
        $val['funding_id'] = input('post.funding_id');
        checkPost($val);
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        try {
            $where = [
                ['funding_id','=',$val['funding_id']],
                ['del','=',0]
            ];
            $list = Db::table('mp_funding_goods')->where($where)
                ->field('id,price,name,desc,pics,sales')
                ->limit(($curr_page - 1)*$perpage,$perpage)
                ->order(['id'=>'DESC'])->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }
    //众筹商品详情
    public function fundingPurchase() {
        $val['funding_id'] = input('post.funding_id');
        checkPost($val);
        $val['goods_id'] = input('post.goods_id','');
        $val['num'] = input('post.num',0);
        $val['receiver'] = input('post.receiver','');
        $val['tel'] = input('post.tel','');
        $val['address'] = input('post.address','');
        $val['desc'] = input('post.desc','');
        $val['uid'] = $this->myinfo['id'];
        $val['create_time'] = time();
        $val['pay_order_sn'] = create_unique_number('F');

        try {
            $whereFunding = [
                ['id','=',$val['funding_id']]
            ];
            $funding_exist = Db::table('mp_funding')->where($whereFunding)->find();
            if($funding_exist['start_time'] > time()) {
                return ajax('众筹未开始',59);
            }
            if($funding_exist['end_time'] < time()) {
                return ajax('众筹已结束',60);
            }
            if($val['goods_id']) {
                $val['type'] = 1;
                $postArray['num'] = $val['num'];
                $postArray['receiver'] = $val['receiver'];
                $postArray['tel'] = $val['tel'];
                $postArray['address'] = $val['address'];
                foreach ($postArray as $value) {
                    if (is_null($value) || $value === '') {
                        return ajax($postArray,-2);
                    }
                }
                if(!if_int($val['num'])) {return ajax('非法参数num',-4);}
                if(!is_tel($val['tel'])) {return ajax('无效的手机号',6);}
                $whereGoods = [
                    ['id','=',$val['goods_id']],
                    ['del','=',0]
                ];
                $goods_exist = Db::table('mp_funding_goods')->where($whereGoods)->find();
                if(!$goods_exist) {
                    return ajax('非法参数goods_id',-4);
                }
                if($goods_exist['funding_id'] != $val['funding_id']) {
                    return ajax('goods_id does not match the funding_id',-4);
                }
                $val['unit_price'] = $goods_exist['price'];
                $val['pay_price'] = $goods_exist['price']*$val['num'];
                $val['total_price'] = $val['pay_price'];
            }else {
                $val['goods_id'] = 0;
                $val['pay_price'] = input('post.pay_price');
                $val['total_price'] = $val['pay_price'];
                if(!$val['pay_price']) {
                    return ajax(['pay_price'=>$val['pay_price']],-2);
                }
                if(!is_currency($val['pay_price'])) {
                    return ajax('无效的金额',70);
                }
                $val['type'] = 2;
            }
            Db::table('mp_funding_order')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1999);
        }
        return ajax($val['pay_order_sn']);

    }




























//设计师列表
    public function designerList() {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        try {
            $where = [
                ['role', '=', 3]
            ];
            $whereFocus = [
                ['uid','=',$this->myinfo['id']]
            ];
            $myFocus = Db::table('mp_user_focus')->where($whereFocus)->column('to_uid');
            $list = Db::table('mp_user')
                ->where($where)
                ->field("id,nickname,avatar,focus,sex,age")
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            if(in_array($v['id'],$myFocus)) {
                $v['if_focus'] = true;
            }else {
                $v['if_focus'] = false;
            }
        }
        return ajax($list);
    }
//设计师参赛作品
    public function designerReqWorkList()
    {
        $val['uid'] = input('post.uid');
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        checkPost($val);
        try {
            $where = [
                ['type', '=', 2],
                ['uid', '=', $val['uid']]
            ];
            $list = Db::table('mp_req_works')
                ->where($where)
                ->field("id,title,req_id,vote,pics")
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['cover'] = unserialize($v['pics'])[0];
            unset($v['pics']);
        }
        return ajax($list);
    }
//设计师展示作品
    public function designerShowWorkList()
    {
        $val['uid'] = input('post.uid');
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        checkPost($val);
        try {
            $where = [
                ['type', '=', 1],
                ['uid', '=', $val['uid']]
            ];
            $list = Db::table('mp_req_works')
                ->where($where)
                ->field("id,title,pics")
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['cover'] = unserialize($v['pics'])[0];
            unset($v['pics']);
        }
        return ajax($list);
    }
//设计师详情
    public function designerDetail() {
        $val['uid'] = input('post.uid');
        checkPost($val);
        try {
            $whereFocus = [
                ['uid','=',$this->myinfo['id']]
            ];
            $myFocus = Db::table('mp_user_focus')->where($whereFocus)->column('to_uid');
            $info = Db::table('mp_user')->alias('u')
                ->join("mp_user_role r","u.id=r.uid","left")
                ->where('u.id', $val['uid'])
                ->field("u.id,u.nickname,u.avatar,u.sex,u.focus,u.age,r.desc")
                ->find();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        if(in_array($info['id'],$myFocus)) {
            $info['if_focus'] = true;
        }else {
            $info['if_focus'] = false;
        }
        return ajax($info);

    }
//充值类目列表
    public function getVipList() {
        $where = [
            ['status','=',1]
        ];
        try {
            $list = Db::table('mp_vip')->where($where)
                ->field('id,title,detail,price,pic,days')
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
//充值
    public function recharge()
    {
        $val['vip_id'] = input('post.vip_id');
        $val['name'] = input('post.name');
        $val['tel'] = input('post.tel');
        $val['address'] = input('post.address');
        $val['uid'] = $this->myinfo['id'];

        checkPost($val);
        try {
            $exist = Db::table('mp_vip')->where('id', $val['vip_id'])->find();
            if (!$exist) {
                return ajax('invalid vip_id', -4);
            }
            $val['price'] = $exist['price'];
            $val['days'] = $exist['days'];
            $val['create_time'] = time();
            $val['order_sn'] = create_unique_number('v');
            Db::table('mp_vip_order')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($val);

    }
//博文列表
    public function orgList()
    {
        $val['role'] = input('post.role');
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        checkPost($val);
        if (!in_array($val['role'], [1, 2])) {
            return ajax($val['role'], -4);
        }
        try {
            $where = [
                ['role', '=', $val['role']]
            ];
            $list = Db::table('mp_user_role')
                ->where($where)
                ->field("uid,cover,role,org,name,desc")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
//博文详情
    public function orgDetail()
    {
        $val['uid'] = input('post.uid');
        checkPost($val);
        $where = [
            ['uid', '=', $val['uid']],
            ['role', 'in', [1, 2]]
        ];
        try {
            $info = Db::table('mp_user_role')->where($where)->field("uid,cover,role,org,name,desc")->find();
            if (!$info) {
                return ajax($val['uid'], -4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }
//博文工设笔记
    public function userNoteList()
    {
        $page = input('page', 1);
        $perpage = input('perpage', 10);
        $val['uid'] = input('post.uid');
        checkPost($val);
        $where = [
            ['n.uid', '=', $val['uid']],
            ['n.del', '=', 0]
        ];
        try {
            $ret['count'] = Db::table('mp_note')->alias('n')->where($where)->count();
            $list = Db::table('mp_note')->alias('n')
                ->join('mp_user u', 'n.uid=u.id', 'left')
                ->where($where)
                ->field('n.id,n.title,n.pics,n.width,n.height,n.like,n.status')
                ->order(['n.create_time' => 'DESC'])
                ->limit(($page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        $ret['list'] = $list;
        return ajax($ret);
    }
//博文需求列表
    public function orgReqList()
    {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        $val['uid'] = input('post.uid');
        checkPost($val);
        $where = [
            ['r.status', '=', 1],
            ['r.show', '=', 1],
            ['r.del', '=', 0],
            ['r.uid', '=', $val['uid']]
        ];
        try {
            $list = Db::table('mp_req')
                ->alias('r')
                ->join("mp_user u", "r.uid=u.id", "left")
                ->where($where)->order(['r.start_time' => 'ASC'])
                ->field("r.id,r.title,r.cover,r.part_num,r.start_time,r.end_time,u.org as user_org")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['start_time'] = date('Y-m-d', strtotime($v['start_time']));
            $v['end_time'] = date('Y-m-d', strtotime($v['end_time']));
        }
        return ajax($list);
    }
//工厂列表
    public function factoryList()
    {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        try {
            $where = [
                ['role', '=', 4]
            ];
            $list = Db::table('mp_user_role')
                ->where($where)
                ->field("uid,cover,role,org,name,desc")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
//工厂详情
    public function factoryDetail() {
        $val['uid'] = input('post.uid');
        checkPost($val);
        $where = [
            ['uid', '=', $val['uid']],
            ['role', '=', 4]
        ];
        try {
            $info = Db::table('mp_user_role')->where($where)->field("uid,cover,role,org,desc")->find();
            if (!$info) {
                return ajax($val['uid'], -4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }
//工厂竞标列表
    public function factoryBiddingList()
    {
        $val['uid'] = input('post.uid');
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        checkPost($val);
        try {
            $where = [
                ['b.uid','=',$val['uid']]
            ];
            $list = Db::table('mp_bidding')->alias('b')
                ->join("mp_req_works w","b.work_id=w.id","left")
                ->join("mp_req r","b.req_id=r.id","left")
                ->join("mp_user_role ro","r.uid=ro.uid","left")
                ->field("b.work_id,b.req_id,b.create_time,w.title as work_title,w.pics,r.title as req_title,ro.org")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->where($where)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }


}