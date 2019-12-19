<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/10/8
 * Time: 11:11
 */

namespace app\api\controller;
use my\Sendsms;
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
    //获取首页列表
    public function getReqList()
    {
        $where = [
            ['r.recommend', '=', 1],
            ['r.status', '=', 1],
            ['r.show', '=', 1],
            ['r.del', '=', 0]
        ];
        try {
            $list = Db::table('mp_req')->alias('r')
                ->join('mp_user u','r.uid=u.id','left')
                ->where($where)->order(['r.start_time' => 'ASC'])
                ->field("r.id,r.uid,r.title,r.works_num,r.idea_num,r.cover,u.org,r.start_time,r.end_time")
                ->limit(0, 5)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //获取活动列表
    public function getAllReqList()
    {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        $where = [
            ['r.status', '=', 1],
            ['r.show', '=', 1],
            ['r.del', '=', 0]
        ];
        try {
            $list = Db::table('mp_req')->alias('r')
                ->join('mp_user u','r.uid=u.id','left')
                ->where($where)->order(['r.start_time' => 'ASC'])
                ->field("r.id,r.uid,r.title,r.works_num,r.idea_num,r.cover,u.org,r.start_time,r.end_time")
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
                ->field('id,uid,title,cover,theme,explain,desc,org,linkman,tel,email,weixin,start_time,deadline,vote_time,end_time,works_num,idea_num,use_video,video_url')
                ->where($where)->find();
            if (!$info) {
                return ajax($val['req_id'], 87);
            }
            $user = Db::table('mp_user')->where('id','=',$info['uid'])->field('nickname,avatar,org')->find();
            $info['nickname'] = $user['nickname'];
            $info['avatar'] = $user['avatar'];
            $info['org'] = $user['org'];
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }

    //获取创意标签
    public function ideaTagsList() {
        try {
            $list = Db::table('mp_req_idea_tags')->field('id,tag_name,type')->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $types = [
            ['type'=>1,'name'=>'功能'],
            ['type'=>2,'name'=>'风格']
        ];
        foreach ($types as &$type) {
            $type['child'] = [];
            foreach ($list as $v) {
                if($v['type'] == $type['type']) {
                    $type['child'][] = $v;
                }
            }
        }
        return ajax($types);
    }
    //提出创意
    public function createIdea() {
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        $val['req_id'] = input('post.req_id');
        checkPost($val);
        $val['tags'] = input('post.tags','');
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
            $req_exist = Db::table('mp_req')->where($whereReq)->find();
            if (!$req_exist) {
                return ajax('非法参数req_id', -4);
            }
            if ($req_exist['start_time'] > time()) {
                return ajax('活动未开始', 26);
            }
            if ($req_exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if ($req_exist['deadline'] <= time()) {
                return ajax('创意时间已结束', 57);
            }
            if (!$this->myinfo['user_auth']) {
                return ajax('用户未授权', 56);
            }

            $whereIdea = [
                ['uid','=',$this->myinfo['id']],
                ['req_id','=',$val['req_id']]
            ];
            $idea_num = Db::table('mp_req_idea')->where($whereIdea)->count();
//            if($idea_num > 3) {
//                return ajax('最多发三条创意',84);
//            }

            if($val['tags']) {
                $tags_arr = explode(',',$val['tags']);
                if(empty($tags_arr)) {
                    return ajax('无效的标签',80);
                }
                if(count($tags_arr) > 5) {
                    return ajax('无法选择更多的标签',81);
                }
                $whereTag = [
                    ['id','IN',$tags_arr]
                ];
                $tags_list = Db::table('mp_req_idea_tags')->where($whereTag)->select();
                if(count($tags_list) !== count($tags_arr)) {
                    return ajax('无效的标签',80);
                }
            }

            Db::table('mp_req')->where($whereReq)->setInc('idea_num',1);
            Db::table('mp_req_idea')->insert($val);
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setInc('idea_num',1);
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
                ['i.status','=',1],
                ['i.del','=',0]
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
                ->field('i.id,i.title,i.content,i.works_num,i.vote,i.tags,i.create_time,u.nickname,u.avatar')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
            $myvote = Db::table('mp_idea_vote')->where('uid','=',$this->myinfo['id'])->column('idea_id');
            $tag_list = Db::table('mp_req_idea_tags')->field('id,tag_name')->select();
            $tag_arr = [];
            foreach ($tag_list as $v) {
                $tag_arr[$v['id']] = $v['tag_name'];
            }
            foreach ($list as &$v) {
                if(in_array($v['id'],$myvote)) {
                    $v['if_vote'] = true;
                }else {
                    $v['if_vote'] = false;
                }
                $tags = explode(',',$v['tags']);
                if(empty($tags)) {
                    $v['tags_name'] = [];
                }else {
                    $tags_name = [];
                    foreach ($tags as $vv) {
                        if(isset($tag_arr[$vv])) {
                            $tags_name[] = $tag_arr[$vv];
                        }
                    }
                    $v['tags_name'] = $tags_name;
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
                ->field('i.id,i.uid,i.title,i.content,i.works_num,i.vote,i.create_time,i.tags,u.nickname,u.avatar,i.req_id,r.title AS req_title,r.cover,r.org')
                ->find();
            if(!$info) {
                return ajax('invalid idea_id',88);
            }
            $myvote = Db::table('mp_idea_vote')->where('uid','=',$this->myinfo['id'])->column('idea_id');
            if(in_array($info['id'],$myvote)) {
                $info['if_vote'] = true;
            }else {
                $info['if_vote'] = false;
            }
            $whereFocus = [
                ['uid','=',$this->myinfo['id']],
                ['to_uid','=',$info['uid']]
            ];
            $exist = Db::table('mp_user_focus')->where($whereFocus)->find();
            if($exist) {
                $info['ifocus'] = true;
            }else {
                $info['ifocus'] = false;
            }
            $tag_list = Db::table('mp_req_idea_tags')->field('id,tag_name')->select();
            $tag_arr = [];
            foreach ($tag_list as $v) {
                $tag_arr[$v['id']] = $v['tag_name'];
            }
            $tags = explode(',',$info['tags']);
            if(empty($tags)) {
                $info['tags_name'] = [];
            }else {
                $tags_name = [];
                foreach ($tags as $vv) {
                    if(isset($tag_arr[$vv])) {
                        $tags_name[] = $tag_arr[$vv];
                    }
                }
                $info['tags_name'] = $tags_name;
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
                $whereWork = [
                    ['req_id', '=', $val['req_id']],
                    ['idea_id', '=', $val['idea_id']],
                    ['uid', '=', $this->myinfo['id']]
                ];
                $workExist = Db::table('mp_req_works')->where($whereWork)->find();
                if ($workExist) {
                    return ajax('已投过此创意,不可重复投稿', 58);
                }
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
            ['w.status','=',1],
            ['w.del','=',0]
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
            //作品是否存在
            $whereWorks = [
                ['w.id', '=',$val['id']],
                ['w.del', '=',0]
            ];
            $work_exist = Db::table('mp_req_works')->alias('w')
                ->join("mp_req_idea i", "w.idea_id=i.id", "left")
                ->join("mp_user u", "w.uid=u.id", "left")
                ->where($whereWorks)
                ->field("w.id,w.uid,w.title,w.desc,w.pics,w.vote,w.bid_num,w.req_id,w.create_time,w.status,w.reason,u.avatar,u.nickname,i.title AS idea_title,i.vote AS idea_vote")
                ->find();
            if (!$work_exist) {
                return ajax($val['id'], 89);
            }
            //我是否投票
            $myvote = Db::table('mp_works_vote')->where('uid','=',$this->myinfo['id'])->column('work_id');
            if(in_array($work_exist['id'],$myvote)) {
                $work_exist['if_vote'] = true;
            }else {
                $work_exist['if_vote'] = false;
            }
            //我是否竞标
            $bidding_exist = Db::table('mp_bidding')->where([
                ['work_id', '=', $val['id']],
                ['uid', '=', $this->myinfo['id']]
            ])->find();
            $where_req = [['id','=',$work_exist['req_id']]];
            $req_exist = Db::table('mp_req')->where($where_req)->find();
            if ($bidding_exist) {
                $work_exist['bidding_btn'] = false;
                if ($req_exist['end_time'] <= time()) {
                    $work_exist['bidding_btn'] = true;
                }
            } else {
                $work_exist['bidding_btn'] = true;
            }
            $whereFocus = [
                ['uid','=',$this->myinfo['id']],
                ['to_uid','=',$work_exist['uid']]
            ];
            $focus_exist = Db::table('mp_user_focus')->where($whereFocus)->find();
            if($focus_exist) {
                $work_exist['ifocus'] = true;
            }else {
                $work_exist['ifocus'] = false;
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $work_exist['req_uid'] = $req_exist['uid'];
        $work_exist['req_title'] = $req_exist['title'];
        $work_exist['pics'] = unserialize($work_exist['pics']);
        return ajax($work_exist);
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
            if($work_exist['factory_id']) {
                return ajax('此作品已有选中工厂,无法再接单',86);
            }
            $bidding_exist = Db::table('mp_bidding')->where([
                ['work_id', '=', $val['work_id']],
                ['uid', '=', $val['uid']]
            ])->find();
            if ($bidding_exist) {
                return ajax('已经参与接单', 37);
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

            $whereToday = [
                ['uid','=',$this->myinfo['id']],
                ['create_time','>=',strtotime(date('Y-m-d 00:00:00'))],
                ['create_time','<=',strtotime(date('Y-m-d 23:59:59'))]
            ];
            $today_count = Db::table('mp_bidding')->where($whereToday)->count('id');

            $today_limit = 3;
            if($today_count > $today_limit) {
                return ajax('每天最多接三个订单',85);
            }

            Db::table('mp_bidding')->insert($val);
            Db::table('mp_req_works')->where($where_work)->setInc('bid_num',1);
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setInc('bid_num',1);

            $bwg = Db::table('mp_user_role')->where('uid','=',$req_exist['uid'])->find();
            /*给博物馆发短信通知*/
            $sms = new Sendsms();
            $param = [
                'tel' => $bwg['tel'],
                'param' => [
                    'req_title' => mb_substr($req_exist['title'],0,20,'utf-8'),
                    'work_title' => mb_substr($work_exist['title'],0,20,'utf-8'),
                    'org' => mb_substr($this->myinfo['org'],0,20,'utf-8')
                ]
            ];
            $res = $sms->send($param,'SMS_174992129');
            if($res->Code !== 'OK') {
                $this->smslog($this->cmd,$res->Message);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //获取竞标列表
    public function biddingList() {
        $val['work_id'] = input('post.work_id');
        checkPost($val);
//        if($this->myinfo['role'] != 1 || $this->myinfo['role_check'] != 2) {
//            return ajax('只有认证的博物馆可以操作此项',72);
//        }
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
            if ($req_exist['start_time'] > time()) {
                return ajax('活动未开始', 26);
            }
            if ($req_exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if($req_exist['deadline'] > time()) {
                return ajax('创意期间不可投票',71);
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
            if ($req_exist['start_time'] > time()) {
                return ajax('活动未开始', 26);
            }
            if ($req_exist['end_time'] <= time()) {
                return ajax('活动已结束', 25);
            }
            if($req_exist['deadline'] > time()) {
                return ajax('创意期间不可投票',71);
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
    //博物馆选工厂
    public function chooseFactory() {
        $val['bidding_id'] = input('post.bidding_id');
        checkPost($val);
        try {
            if($this->myinfo['role'] != 1 || $this->myinfo['role_check'] != 2) {
                return ajax('只有认证的博物馆可以操作此项',72);
            }
            $myreqids = Db::table('mp_req')->where('uid','=',$this->myinfo['id'])->column('id');
            $whereBidding = [
                ['id','=',$val['bidding_id']]
            ];
            $bidding_exist = Db::table('mp_bidding')->where($whereBidding)->find();
            if(!$bidding_exist) {
                return ajax('invalid bidding_id',-4);
            }
            if(!in_array($bidding_exist['req_id'],$myreqids)) {
                return ajax('无权操作此活动流程',74);
            }
            $whereWork = [
                ['id','=',$bidding_exist['work_id']]
            ];
            $work_exist = Db::table('mp_req_works')->where($whereWork)->find();
            if($work_exist['factory_id']) {
                return ajax('此作品已有接单工厂',73);
            }
            $factory = Db::table('mp_user_role')->where('uid','=',$bidding_exist['uid'])->field('id,tel,org')->find();
            Db::table('mp_req_works')->where($whereWork)->update(['factory_id'=>$bidding_exist['uid']]);
            Db::table('mp_bidding')->where($whereBidding)->update(['choose'=>1]);
            //todo 给工厂发送通知短信
            $sms = new Sendsms();
            $param = [
                'tel' => $factory['tel'],
                'param' => [
                    'work_title' => $work_exist['title'],
                    'org' => $this->myinfo['org']
                ]
            ];
            $res = $sms->send($param,'SMS_174987199');
            if($res->Code !== 'OK') {
                $this->smslog($this->cmd,$res->Message);
            }
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
                ->field('f.id,f.title,f.cover,f.need_money,f.curr_money,f.order_num,f.start_time,f.end_time,f.req_id,f.idea_id,f.work_id,r.title AS req_title,r.explain AS req_detail,i.title AS idea_title,i.content AS idea_detail,w.uid,w.title AS work_title,w.desc AS work_detail,w.pics AS works_pics,f.desc,f.content')
                ->where($where)->find();
            if(!$info) {
                return ajax('非法参数',-4);
            }
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
                ->field('id,price,name,desc,pics,sales,funding_id')
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
                ['role', '=', 2],
                ['role_check','=',2]
            ];
            $whereFocus = [
                ['uid','=',$this->myinfo['id']]
            ];
            $myFocus = Db::table('mp_user_focus')->where($whereFocus)->column('to_uid');
            $list = Db::table('mp_user')
                ->where($where)
                ->field("id,nickname,avatar,idea_num,works_num,focus,level")
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
    //博物馆列表
    public function bwgList() {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        try {
            $where = [
                ['u.role', '=', 1],
                ['u.role_check','=',2]
            ];
            $list = Db::table('mp_user')->alias('u')
                ->join('mp_user_role r','u.id=r.uid','left')
                ->where($where)
                ->field("u.id,u.org,u.req_num,u.focus,u.level,r.cover")
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //工厂列表
    public function factoryList() {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        $province_code = input('post.province_code');
        $city_code = input('post.city_code');
        $region_code = input('post.region_code');
        $search = input('post.search');
        try {
            $where = [
                ['u.role', '=', 3],
                ['u.role_check','=',2]
            ];
            $order = ['u.id'=>'DESC'];
            if ($province_code) {
                $where[] = ['r.province_code','=',$province_code];
            }
            if ($city_code) {
                $where[] = ['r.city_code','=',$city_code];
            }
            if ($region_code) {
                $where[] = ['r.region_code','=',$region_code];
            }
            if ($search) {
                $where[] = ['u.org','like',"%{$search}%"];
            }
            $list = Db::table('mp_user')->alias('u')
                ->join('mp_user_role r','u.id=r.uid','left')
                ->where($where)
                ->order($order)
                ->field("u.id,u.org,u.bid_num,u.focus,u.level,r.cover")
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
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

    //角色充值类目列表
    public function getRole3LevelList() {
        try {
            $list = Db::table('mp_role3_level')->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }

    public function role3Recharge() {
        $val['level_id'] = input('post.level_id');
        checkPost($val);
        try {
            if($this->myinfo['role'] != 3 || $this->myinfo['role_check'] != 2) {
                return ajax('套餐仅限通过审核的工厂',90);
            }
            $whereLevel = [
                ['id','=',$val['level_id']]
            ];
            $level_exist = Db::table('mp_role3_level')->where($whereLevel)->find();
            if(!$level_exist) {
                return ajax($val['level_id'],-4);
            }
            $whereCurr = [
                ['uid','=',$this->myinfo['id']],
                ['end_time','>',time()]
            ];
            $curr_exist = Db::table('mp_role3_curr')->where($whereCurr)->find();
            if($curr_exist) {
                return ajax('当前套餐结束前无法购买新套餐',91);
            }
            $val['uid'] = $this->myinfo['id'];
            $val['pay_order_sn'] = create_unique_number('');
            $val['pay_price'] = $level_exist['price'];
            $val['role_level'] = $level_exist['level'];
            $val['days'] = $level_exist['days'];
            $val['role_level'] = $level_exist['level'];
            $val['create_time'] = time();
            Db::table('mp_role3_order')->insert($val);

        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($val['pay_order_sn']);
    }









    //收集formid
    public function collectFormid() {
        $val['formid'] = input('post.formid');
        checkPost($val);
        if($val['formid'] == 'the formId is a mock one') {
            return ajax();
        }
        $val['uid'] = $this->myinfo['id'];
        $val['create_time'] = time();
        try {
            Db::table('mp_formid')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($val);
    }

    //获取活动详情
    public function getAcInfo() {
        try {
            $whereAc = [
                ['id','=',1]
            ];
            $info = Db::table('mp_tmp_ac')->where($whereAc)->field('id,title,pics,content')->find();
            if(!$info) {
                return ajax('activity not exists',-4);
            }
            $info['pics'] = unserialize($info['pics']);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }

    //参加活动发送短信
    public function joinAcSendSms() {
        $val['tel'] = input('post.tel');
        checkPost($val);
        $sms = new Sendsms();
        $tel = $val['tel'];

        if(!is_tel($tel)) {
            return ajax('invalid tel',6);
        }
        try {
            $code = mt_rand(100000,999999);
            $insert_data = [
                'tel' => $tel,
                'code' => $code,
                'create_time' => time()
            ];
            $sms_data['tel'] = $val['tel'];
            $sms_data['param'] = [
                'code' => $code
            ];
            $whereTel = [
                ['tel','=',$tel]
            ];

            $exist = Db::table('mp_verify')->where($whereTel)->find();
            if($exist) {

                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($sms_data);

                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where($whereTel)->update($insert_data);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($sms_data,'SMS_178465508');
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->insert($insert_data);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

    //参加活动
    public function joinAc() {
        $val['name'] = input('post.name');
        $val['sex'] = input('post.sex');
        $val['tel'] = input('post.tel');
        $val['code'] = input('post.code');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
        $val['weixin'] = input('post.weixin','');
        $val['create_time'] = date('Y-m-d H:i:s');

        if (!is_tel($val['tel'])) {
            return ajax('无效的手机号', 6);
        }
        try {
            //验证短信验证码
            $whereCode = [
                ['tel','=',$val['tel']],
                ['code','=',$val['code']]
            ];
            $code_exist = Db::table('mp_verify')->where($whereCode)->find();
            if($code_exist) {
                if((time() - $code_exist['create_time']) > 60*5) {
                    return ajax('验证码已过期',17);
                }
            }else {
                return ajax('验证码无效',16);
            }

            unset($val['code']);
            Db::table('mp_tmp_sign')->insert($val);
            Db::table('mp_verify')->where($whereCode)->delete();
        }catch (\Exception $e) {//异常删图
            return ajax($e->getMessage(),-1);
        }
        return ajax();

    }

    //获取省级列表
    public function getProvinceList() {
        try {
            $where = [
                ['pcode','=',0]
            ];
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取城市列表
    public function getCityList() {
        $val['provinceCode'] = input('post.province_code');
        try {
            if($val['provinceCode']) {
                $where = [
                    ['pcode','=',$val['provinceCode']],
                    ['level','=',2]
                ];
            }else {
                return ajax([]);
            }
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取区列表
    public function getRegionList() {
        $val['cityCode'] = input('post.city_code');
        try {
            if($val['cityCode']) {
                $where = [
                    ['pcode','=',$val['cityCode']],
                    ['level','=',3]
                ];
            }else {
                return ajax([]);
            }
            $list = Db::table('mp_city')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }






}