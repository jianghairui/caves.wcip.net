<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/3/11
 * Time: 16:00
 */
namespace app\api\controller;
use my\Sendsms;
use my\Kuaidiniao;
use think\Db;
class My extends Common {

    //获取个人信息
    public function mydetail() {
        $map = [
            ['u.id','=',$this->myinfo['id']]
        ];
        try {
            $info = Db::table('mp_user')->alias('u')
                ->join('mp_user_role r','u.id=r.uid','left')
                ->field('u.*,r.cover')
                ->where($map)->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info);
    }
    //修改头像
    public function modAvatar() {
        $user = $this->myinfo;
        try {
            $avatar = input('post.avatar');
            $val['avatar'] = $avatar;
            if($avatar) {
                if (substr($avatar,0,4) == 'http') {
                    $val['avatar'] = $avatar;
                }else {
                    $qiniu_exist = $this->qiniuFileExist($avatar);
                    if($qiniu_exist !== true) {
                        return ajax($qiniu_exist['msg'] . ' :'.$avatar,5);
                    }
                    $img_check = $this->imgSecCheck(config('qiniu_weburl') . $avatar);
                    if(!$img_check) {
                        return ajax('图片包含违规内容',82);
                    }
                    $qiniu_move = $this->moveFile($avatar,'upload/avatar/');
                    if($qiniu_move['code'] == 0) {
                        $val['avatar'] = $qiniu_move['path'];
                    }else {
                        return ajax($qiniu_move['msg'],101);
                    }
                }
            }else {
                return ajax('请上传头像',61);
            }
            Db::table('mp_user')->where('id','=',$user['id'])->update($val);
        } catch (\Exception $e) {
            if ($val['avatar'] != $user['avatar'] &&  substr($val['avatar'],0,4) != 'http') {
                $this->rs_delete($val['avatar']);
            }
            return ajax($e->getMessage(), -1);
        }
        if ($val['avatar'] != $user['avatar'] && substr($user['avatar'],0,4) != 'http') {
            $this->rs_delete($user['avatar']);
        }
        return ajax();

    }
    //修改昵称
    public function modNickname() {
        $val['nickname'] = input('post.nickname');
        checkPost($val);
        if(!$this->msgSecCheck($val['nickname'])) {
            return ajax('昵称包含敏感词',68);
        }
        try {
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //修改真实姓名
    public function modRealname() {
        $val['realname'] = input('post.realname');
        checkPost($val);
        try {
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //修改性别 1 男 2 女
    public function modSex() {
        $val['sex'] = input('post.sex');
        checkPost($val);
        $val['sex'] = intval($val['sex']);
        if(!in_array($val['sex'],[0,1,2], true)) {
            return ajax('非法参数sex',-4);
        }
        try {
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //修改个人简介
    public function modDesc() {
        $val['sign'] = input('post.sign','');
        checkPost($val);
        if(!$this->msgSecCheck($val['sign'])) {
            return ajax('内容包含敏感词',64);
        }
        try {
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //修改角色封面
    public function modCover() {
        try {
            $whereRole = [
                ['uid','=',$this->myinfo['id']]
            ];
            $role_exist = Db::table('mp_user_role')->where($whereRole)->find();
            if(!$role_exist) {
                return ajax('角色未认证',29);
            }
            $cover = input('post.cover');
            if($cover) {
                $qiniu_exist = $this->qiniuFileExist($cover);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'] . ' :'.$cover,5);
                }
                $qiniu_move = $this->moveFile($cover,'upload/role/');
                if($qiniu_move['code'] == 0) {
                    $val['cover'] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],101);
                }
            }else {
                return ajax('请上传封面',33);
            }
            Db::table('mp_user_role')->where($whereRole)->update($val);
        } catch (\Exception $e) {
            if ($val['cover'] != $role_exist['cover']) {
                $this->rs_delete($val['cover']);
            }
            return ajax($e->getMessage(), -1);
        }
        if ($val['cover'] != $role_exist['cover']) {
            $this->rs_delete($role_exist['cover']);
        }
        return ajax();

    }

    //查看签到状态
    public function checkSign() {
        try {
            //判断昨天是否签到
            $whereYesterday = [
                ['uid','=',$this->myinfo['id']],
                ['sign_date','=',date('Y-m-d',strtotime('-1 day'))]
            ];
            $yesterday = Db::table('mp_user_sign')->where($whereYesterday)->find();
            //判断今天是否签到
            $whereToday = [
                ['uid','=',$this->myinfo['id']],
                ['sign_date','=',date('Y-m-d')]
            ];
            $today = Db::table('mp_user_sign')->where($whereToday)->find();
            //$data['days']进度条天数
            if($yesterday) {
                //昨天是第7天
                if($yesterday['days'] == 7) {
                    $data['days'] = 0;
                    if($today) {    //昨天是第7天,今天已经签到了
                        $data['days'] = 1;
                        $data['today'] = true;
                        $data['list'] = Db::table('mp_user_sign')
                            ->where('uid','=',$this->myinfo['id'])
                            ->order(['id'=>'DESC'])
                            ->limit(0,1)
                            ->select();
                    }else {     //昨天是第7天,今天未签到
                        $data['today'] = false;
                        $data['list'] = [];
                    }
                }else {
                    $data['days'] = $yesterday['days'];
                    //昨天不是第7天
                    if($today) {    //今天已经签到了
                        $data['days'] = $data['days'] + 1;
                        $data['today'] = true;
                    }else {         //今天未签到
                        $data['today'] = false;
                    }
                    $data['list'] = Db::table('mp_user_sign')
                        ->where('uid','=',$this->myinfo['id'])
                        ->order(['id'=>'DESC'])
                        ->limit(0,$data['days'])
                        ->select();
                }
            }else {
                //昨天未签到
                $data['days'] = 0;
                if($today) {
                    $data['days'] = 1;
                    $data['today'] = true;
                    $data['list'] = Db::table('mp_user_sign')
                        ->where('uid','=',$this->myinfo['id'])
                        ->order(['id'=>'DESC'])
                        ->limit(0,1)
                        ->select();
                }else {
                    $data['today'] = false;
                    $data['list'] = [];
                }
            }

        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($data);
    }
    //签到
    public function userSign() {
        try {
            $whereToday = [
                ['uid','=',$this->myinfo['id']],
                ['sign_date','=',date('Y-m-d')]
            ];
            $today = Db::table('mp_user_sign')->where($whereToday)->find();
            if($today) {
                return ajax('今日已签到',75);
            }

            $where = [
                ['uid','=',$this->myinfo['id']],
                ['sign_date','=',date('Y-m-d',strtotime('-1 day'))]
            ];
            $yesterday = Db::table('mp_user_sign')->where($where)->find();
            /*----查看昨天有没有签到记录----*/
            if($yesterday) {
                $val['days'] = $yesterday['days'] + 1;
                if($yesterday['days'] == 7) {
                    $val['days'] = 1;
                }
                if($yesterday['days'] == 6) {
                    $score = 100;
                    $val['score'] = $score;
                    $val['desc'] = '+'.$score.'积分';
                    /*---获得积分记录日志修改用户积分---*/
                    Db::table('mp_score_log')->insert([
                        'uid' => $this->myinfo['id'],
                        'score' => $score,
                        'desc' => '连续签到7天',
                        'type' => 2
                    ]);
                    Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setInc('score',$score);
                }else {
                    $score = 10;
                    $val['score'] = $score;
                    $val['desc'] = '+'.$score.'积分';
                    /*---获得积分记录日志修改用户积分---*/
                    Db::table('mp_score_log')->insert([
                        'uid' => $this->myinfo['id'],
                        'score' => $score,
                        'desc' => '签到',
                        'type' => 2
                    ]);
                    Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setInc('score',$score);
                }
            }else {
                $val['days'] = 1;
                $score = 10;
                $val['score'] = $score;
                $val['desc'] = '+'.$score.'积分';
                /*---获得积分记录日志修改用户积分---*/
                Db::table('mp_score_log')->insert([
                    'uid' => $this->myinfo['id'],
                    'score' => $score,
                    'desc' => '签到',
                    'type' => 2
                ]);
                Db::table('mp_user')->where('id','=',$this->myinfo['id'])->setInc('score',$score);
            }
            $val['uid'] = $this->myinfo['id'];
            $val['sign_date'] = date('Y-m-d');
            $val['sign_time'] = time();
            Db::table('mp_user_sign')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $data['days'] = $val['days'];
        $data['score'] = $val['score'];
        return ajax($data);
    }
    //最近30天签到记录
    public function signLog() {
        try {
            $where = [
                ['uid','=',$this->myinfo['id']],
                ['sign_date','>=',date('Y-m-d',strtotime('-29 days'))]
            ];
            $sign_list = Db::table('mp_user_sign')->where($where)->column('sign_date');
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $date_arr = [];
        for($i=0;$i<=29;$i++) {
            $date_arr[] = date('Y-m-d',strtotime('-'.$i.' days'));
        }

        $list = [];
        foreach ($date_arr as $v) {
            $data = [];
            $data['sign_date'] = $v;
            if(in_array($v,$sign_list)) {
                $data['sign'] = true;
            }else {
                $data['sign'] = false;
            }
            $list[] = $data;
        }
        return ajax($list);
    }

    //我的关注
    public function myFocusList() {
        $curr_page = input('page',1);
        $perpage = input('perpage',10);
        $type = input('post.type',1);
        try {
            $whereFocus = [
                ['uid','=',$this->myinfo['id']]
            ];
            $myFocus = Db::table('mp_user_focus')->where($whereFocus)->column('to_uid');
            if(empty($myFocus)) {
                return ajax([]);
            }
            if($type == 1) {
                $where = [
                    ['u.role', '=', 1],
                    ['u.role_check','=',2],
                    ['u.id','IN',$myFocus]
                ];
                $list = Db::table('mp_user')->alias('u')
                    ->join('mp_user_role r','u.id=r.uid','left')
                    ->where($where)
                    ->field("u.id,u.org,u.req_num,u.focus,u.level,r.cover")
                    ->limit(($curr_page - 1) * $perpage, $perpage)->select();

            }elseif ($type == 2) {
                $where = [
                    ['role', '=', 2],
                    ['role_check','=',2],
                    ['id','IN',$myFocus]
                ];
                $list = Db::table('mp_user')
                    ->where($where)
                    ->field("id,nickname,avatar,idea_num,works_num,focus,level")
                    ->limit(($curr_page - 1) * $perpage, $perpage)->select();
            }elseif ($type == 3) {
                $where = [
                    ['u.role', '=', 3],
                    ['u.role_check','=',2],
                    ['u.id','IN',$myFocus]
                ];
                $list = Db::table('mp_user')->alias('u')
                    ->join('mp_user_role r','u.id=r.uid','left')
                    ->where($where)
                    ->field("u.id,u.org,u.bid_num,u.focus,u.level,r.cover")
                    ->limit(($curr_page - 1) * $perpage, $perpage)->select();
            }else {
                $list = [];
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //我的积分日志
    public function myScoreLog() {
        $page = input('page',1);
        $perpage = input('perpage',10);
        $where = [
            ['uid','=',$this->myinfo['id']]
        ];
        try {
            $list = Db::table('mp_score_log')->where($where)
                ->field('id,score,desc,create_time')
                ->order(['id'=>'DESC'])
                ->limit(($page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
    }

    //获取我发的笔记列表
    public function getMyNoteList()
    {
        $page = input('page',1);
        $perpage = input('perpage',10);
        $where = [
            ['n.uid','=',$this->myinfo['id']],
            ['n.del','=',0]
        ];
        try {
            $ret['count'] = Db::table('mp_note')->alias('n')->where($where)->count();
            $list = Db::table('mp_note')->alias('n')
                ->join('mp_user u','n.uid=u.id','left')
                ->where($where)
                ->field('n.id,n.title,n.pics,n.width,n.height,u.nickname,n.like,n.status')
                ->order(['n.create_time'=>'DESC'])
                ->limit(($page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        $ret['list'] = $list;
        return ajax($ret);
    }
    //编辑笔记
    public function noteMod ()
    {
        $val['id'] = input('post.id');
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
        $image = input('post.pics',[]);
        if(!$this->msgSecCheck($val['title'])) {
            return ajax('标题包含敏感词',63);
        }
        if(!$this->msgSecCheck($val['content'])) {
            return ajax('内容包含敏感词',64);
        }
        if(is_array($image) && !empty($image)) {
            if(count($image) > 9) {
                return ajax('最多上传9张图片',8);
            }
            //验证图片是否存在
            foreach ($image as $v) {
                $qiniu_exist = $this->qiniuFileExist($v);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'] . ' :'.$v,5);
                }
            }
        }else {
            return ajax('请传入图片',3);
        }

        try {
            $where = [
                ['id','=',$val['id']],
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_note')->where($where)->find();
            if(!$exist) {
                return ajax($val['id'],-4);
            }
            if($exist['status'] != 2) {
                return ajax('当前状态无法修改',34);
            }
            $old_pics = unserialize($exist['pics']);

            $image_array = [];
            //转移七牛云图片
            foreach ($image as $v) {
                $qiniu_move = $this->moveFile($v,'upload/note/');
                if($qiniu_move['code'] == 0) {
                    $image_array[] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'] .' :' . $v . '',-1);
                }
            }
            $val['pics'] = serialize($image_array);
            $val['status'] = 0;
            Db::table('mp_note')->where($where)->update($val);
        }catch (\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    $this->rs_delete($v);
                }
            }
            return ajax($e->getMessage(),-1);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                $this->rs_delete($v);
            }
        }
        return ajax();
    }
    //获取我的收藏笔记列表
    public function getMyCollectedNoteList() {
        $page = input('page',1);
        $perpage = input('perpage',10);
        try {
            $whereCollect = [
                ['uid','=',$this->myinfo['id']]
            ];
            $note_ids = Db::table('mp_note_collect')->where($whereCollect)->column('note_id');
            if(empty($note_ids)) {
                return ajax([]);
            }
            $whereNote = [
                ['n.id','IN',$note_ids]
            ];
            $count = Db::table('mp_note')->alias('n')
                ->join('mp_user u','n.uid=u.id','left')
                ->where($whereNote)->count();
            $list = Db::table('mp_note')->alias('n')
                ->join('mp_user u','n.uid=u.id','left')
                ->where($whereNote)
                ->field('n.id,n.title,n.pics,n.like,n.width,n.height,u.nickname,u.avatar')
                ->order(['n.create_time'=>'DESC'])
                ->limit(($page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        $data['count'] = $count;
        $data['list'] = $list;
        return ajax($data);
    }

    //我的创意列表
    public function myIdeaList() {
        $val['order'] = input('post.order',1);
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',20);
        try {
            $where = [
                ['i.status','=',1],
                ['i.uid','=',$this->myinfo['id']]
            ];
            if($val['order'] == 1) {
                $order = ['i.id'=>'DESC'];
            }else {
                $order = ['i.vote'=>'DESC'];
            }
            $list = Db::table('mp_req_idea')->alias('i')
                ->join('mp_req r','i.req_id=r.id','left')
                ->where($where)
                ->field('i.id,i.title,i.content,i.works_num,i.vote,i.req_id,i.tags,r.title AS req_title,r.org,i.create_time')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
            $tag_list = Db::table('mp_req_idea_tags')->field('id,tag_name')->select();
            $tag_arr = [];
            foreach ($tag_list as $v) {
                $tag_arr[$v['id']] = $v['tag_name'];
            }
            foreach ($list as &$v) {
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

    /*------ 店铺设置 START ------*/
    //设置账号密码
    public function setAccount() {
        $val['username'] = input('post.username');
        $val['password'] = input('post.password');
        checkPost($val);
        try {
            if(!is_string($val['username']) || !preg_match('/^[_0-9a-zA-Z]{6,32}$/',$val['username'])) {
                return ajax('账号只能为6-32数字或字母下划线组合',76);
            }
            if(!is_string($val['password']) || !preg_match('/^[_0-9a-zA-Z]{6,32}$/',$val['password'])) {
                return ajax('密码只能为6-32数字或字母下划线组合',77);
            }
            $val['password'] = md5($val['password'] . config('login_key'));
            $where = [
                ['username','=',$val['username']]
            ];
            $exist = Db::table('mp_user')->where($where)->find();
            if($exist) {
                return ajax('此账号已被占用',78);
            }
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //修改密码
    public function setPasswd() {
        $val['password'] = input('post.password');
        checkPost($val);
        try {
            if(!is_string($val['password']) || !preg_match('/^[_0-9a-zA-Z]{6,32}$/',$val['password'])) {
                return ajax('密码只能为6-32数字或字母下划线组合',77);
            }
            $val['password'] = md5($val['password'] . config('login_key'));
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //设置订单通知接收邮箱
    public function setOrderEmail() {
        $val['email'] = input('post.email');
        checkPost($val);
        try {
            if(!is_email($val['email'])) {
                return ajax('无效的邮箱',7);
            }
            $whereUser = [
                ['id','=',$this->myinfo['id']]
            ];
            Db::table('mp_user')->where($whereUser)->update(['order_email'=>$val['email']]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    /*------ 店铺设置 END ------*/

    /*------ 博物馆独有接口 START ------*/

    //我发布的需求
    public function myReqList() {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        $where = [
            ['uid','=',$this->myinfo['id']],
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

    //工厂接单处理
    public function biddingWorksList() {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        try {
            $whereReq = [
                ['uid','=',$this->myinfo['id']],
                ['del','=',0]
            ];
            $req_ids = Db::table('mp_req')->where($whereReq)->column('id');
            if(empty($req_ids)) {
                return ajax([]);
            }
            $order = ['w.id'=>'DESC'];
            $whereWorks = [
                ['w.req_id','IN',$req_ids],
                ['w.bid_num','>',0]
            ];
            $workslist = Db::table('mp_req_works')->alias('w')
                ->join("mp_req r", "w.req_id=r.id", "left")
                ->join("mp_user u", "w.uid=u.id", "left")
                ->where($whereWorks)
                ->field("w.id,w.title,w.vote,w.pics,w.bid_num,w.factory_id,w.desc,w.create_time,u.nickname,u.avatar,r.title AS req_title")
                ->order($order)
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($workslist as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($workslist);
    }

    /*------ 博物馆独有接口 END ------*/


    /*------ 设计师独有接口 START ------*/

    //上传展示作品
    public function uploadShowWorks() {
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
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
            if ($user['role'] != 2 || $user['role_check'] != 2) {
                return ajax('只有认证设计师可以投稿', 28);
            }
            if (!$user['user_auth']) {
                return ajax('用户未授权', 56);
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
                $qiniu_move = $this->moveFile($v,'upload/showworks/');
                if($qiniu_move['code'] == 0) {
                    $image_array[] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],101);
                }
            }
            $val['pics'] = serialize($image_array);
            Db::table('mp_show_works')->insert($val);
        } catch (\Exception $e) {
            foreach ($image_array as $v) {
                $this->rs_delete($v);
            }
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    //获取我的展示作品
    public function getMyShowWorks() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $status = input('post.status','');
        try {
            $where = [
                ['uid','=',$this->myinfo['id']],
                ['del','=',0]
            ];
            if(!is_null($status) && $status !== '') {
                $where[] = ['status','=',$status];
            }
            $list = Db::table('mp_show_works')
                ->where($where)
                ->field("id,title,desc,pics,status")
                ->limit(($curr_page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }

    //获取我的参赛作品
    public function getMyReqWorks() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $status = input('post.status','');
        $order = ['w.id'=>'DESC'];
        try {
            $where = [
                ['w.uid','=',$this->myinfo['id']],
                ['w.del','=',0]
            ];
            if($status !== '' && !is_null($status)) {
                $where[] = ['w.status','=',$status];
            }
            $list = Db::table('mp_req_works')->alias('w')
                ->join('mp_req r','w.req_id=r.id','left')
                ->where($where)
                ->field("w.id,w.title,w.desc,w.req_id,w.vote,w.bid_num,w.pics,w.status,w.reason,r.title AS req_title,r.org")
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }

    //修改我的展示作品
    public function myShowWorksMod() {
        $val['work_id'] = input('post.work_id');
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        checkPost($val);
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
            $whereWork = [
                ['id','=',$val['work_id']],
                ['uid','=',$this->myinfo['id']]
            ];
            $work_exist = Db::table('mp_show_works')->where($whereWork)->find();
            if(!$work_exist) {
                return ajax('invalid work_id',-4);
            }
            if($work_exist['status'] !== 2) {
                return ajax('当前状态无法提交审核',62);
            }
            $old_pics = unserialize($work_exist['pics']);
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
            unset($val['work_id']);
            $val['status'] = 0;
            Db::table('mp_show_works')->where($whereWork)->update($val);
        } catch (\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    $this->rs_delete($v);
                }
            }
            return ajax($e->getMessage(), -1);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                $this->rs_delete($v);
            }
        }
        return ajax();
    }

    //修改我的设计作品
    public function myReqWorksMod() {
        $val['work_id'] = input('post.work_id');
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        checkPost($val);
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
            $whereWork = [
                ['id','=',$val['work_id']],
                ['uid','=',$this->myinfo['id']]
            ];
            $work_exist = Db::table('mp_req_works')->where($whereWork)->find();
            if(!$work_exist) {
                return ajax('invalid work_id',-4);
            }
            if($work_exist['status'] !== 2) {
                return ajax('当前状态无法提交审核',62);
            }
            $old_pics = unserialize($work_exist['pics']);
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
            unset($val['work_id']);
            $val['status'] = 0;
            Db::table('mp_req_works')->where($whereWork)->update($val);
        } catch (\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    $this->rs_delete($v);
                }
            }
            return ajax($e->getMessage(), -1);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                $this->rs_delete($v);
            }
        }
        return ajax();
    }

    /*------ 设计师独有接口 END ------*/


    /*------ 工厂独有接口 START ------*/
    //我的竞标列表
    public function myBiddingList() {
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        try {
            $where = [
                ['b.uid','=',$this->myinfo['id']]
            ];
            $list = Db::table('mp_bidding')->alias('b')
                ->join("mp_req_works w","b.work_id=w.id","left")
                ->join("mp_req r","b.req_id=r.id","left")
                ->field("b.work_id,b.req_id,b.create_time,b.choose,w.title as work_title,w.desc AS work_detail,w.pics,r.title as req_title,r.org")
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
    /*------ 工厂独有接口 END ------*/


    /*------   即时需求   ------*/

    public function myXuqiuList() {
        $page = input('page',1);
        $perpage = input('perpage',10);

        $where = [
            ['x.del','=',0],
            ['x.uid','=',$this->myinfo['id']]
        ];
        try {
            $list = Db::table('mp_xuqiu')->alias('x')
                ->join('mp_user u','x.uid=u.id','left')
                ->where($where)
                ->field('x.id,x.title,x.pics,x.content,x.create_time,x.status,u.nickname,u.avatar,u.role,u.role_check')
                ->order(['x.create_time'=>'DESC'])
                ->limit(($page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);

    }



    /*------ 众筹订单管理 START ------*/
    //众筹订单列表
    public function fundingOrderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $status = input('post.status','');
        $order = ['o.id'=>'DESC'];
        $where = [
            ['o.uid','=',$this->myinfo['id']],
            ['o.del','=',0],
            ['o.refund_apply','=',0]
        ];
        if($status !== '') {
            $where[] = ['o.status','=',$status];
        }
        try {
            $list = Db::table('mp_funding_order')->alias('o')
                ->join('mp_funding f','o.funding_id=f.id','left')
                ->join('mp_funding_goods g','o.goods_id=g.id','left')
                ->where($where)
                ->field('o.id,o.pay_order_sn,o.pay_price,o.unit_price,o.num,o.status,o.create_time,o.type,f.title AS funding_title,f.cover,f.id AS funding_id,g.name AS goods_name,g.pics')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }
    //众筹售后列表
    public function fundingRefundList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $type = input('post.type','');
        $order = ['o.id'=>'DESC'];

        $where = [
            ['o.uid','=',$this->myinfo['id']],
            ['o.del','=',0]
        ];
        switch ($type) {
            case '1':$where[] = ['o.refund_apply','=',1];break;
            case '2':$where[] = ['o.refund_apply','=',2];break;
            default:$where[] = ['o.refund_apply','IN',[1,2]];
        }
        try {
            $list = Db::table('mp_funding_order')->alias('o')
                ->join('mp_funding f','o.funding_id=f.id','left')
                ->join('mp_funding_goods g','o.goods_id=g.id','left')
                ->where($where)
                ->field('o.id,o.pay_order_sn,o.pay_price,o.unit_price,o.num,o.type,o.refund_apply,o.status,o.create_time,o.type,f.title AS funding_title,f.cover,g.name AS goods_name,g.pics')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }
    //众筹订单详情
    public function fundingOrderDetail() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['o.uid','=',$this->myinfo['id']],
                ['o.id','=',$val['order_id']],
                ['o.del','=',0]
            ];
            $info = Db::table('mp_funding_order')->alias('o')
                ->join('mp_funding_goods g','o.goods_id=g.id','left')
                ->join('mp_funding f','o.funding_id=f.id','left')
                ->where($where)
                ->field('o.id,o.pay_order_sn,o.trans_id,o.pay_price,o.unit_price,o.num,o.receiver,o.tel,o.address,o.type,o.refund_apply,o.reason,o.status,o.create_time,o.pay_time,o.send_time,o.finish_time,o.refund_time,o.tracking_name,o.tracking_num,g.name AS goods_name,g.pics,f.id AS funding_id,f.title AS funding_title,f.cover')
                ->find();
            if(!$info) {
                return ajax('invalid order_id',4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $info['pics'] = unserialize($info['pics']);
        return ajax($info);
    }
    //申请退款
    public function fundingRefundApply() {
        $val['order_id'] = input('post.order_id');
        $val['reason'] = input('post.reason');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']],
                ['refund_apply','=',0],
                ['status','IN',[1,2,3]],
                ['del','=',0]
            ];
            $info = Db::table('mp_funding_order')->where($where)->find();
            if(!$info) {
                return ajax('invalid order_id',4);
            }
            Db::table('mp_funding_order')->where($where)->update([
                'refund_apply' => 1,
                'reason' => $val['reason']
            ]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //确认收货
    public function fundingOrderConfirm() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']],
                ['status','=',2],
                ['del','=',0]
            ];
            $exist = Db::table('mp_funding_order')->alias('o')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid order_id',4);
            }
            $update_data = [
                'status' => 3,
                'finish_time' => time()
            ];
            Db::table('mp_funding_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //取消众筹订单
    public function fundingOrderCancel() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']],
                ['status','=',0],
                ['del','=',0]
            ];
            $exist = Db::table('mp_funding_order')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid order_id',4);
            }
            $update_data = [
                'del' => 1
            ];
            Db::table('mp_funding_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    /*------ 众筹订单管理 END ------*/


    /*------ 商品订单管理 START------*/
    //我的订单列表
    public function orderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $status = input('post.status','');
        $where = "del=0 AND uid=".$this->myinfo['id'];
        $where .= " AND `status` IN ('0','1','2','3') AND `del`=0 AND `refund_apply`=0";
        $order = " ORDER BY `id` DESC";
        $orderby = " ORDER BY `d`.`id` DESC";
        if($status !== '') {
            $where .= " AND status=" . $status;
        }
        try {
            $list = Db::query("SELECT 
`o`.`id`,`o`.`pay_order_sn`,`o`.`pay_price`,`o`.`total_price`,`o`.`carriage`,`o`.`create_time`,`o`.`refund_apply`,`o`.`status`,`o`.`refund_apply`,`d`.`order_id`,`d`.`goods_id`,`d`.`num`,`d`.`unit_price`,`d`.`goods_name`,`d`.`attr`,`g`.`pics` 
FROM (SELECT * FROM mp_order WHERE " . $where . $order ." LIMIT ".($curr_page-1)*$perpage.",".$perpage.") `o` 
LEFT JOIN `mp_order_detail` `d` ON `o`.`id`=`d`.`order_id`
LEFT JOIN `mp_goods` `g` ON `d`.`goods_id`=`g`.`id`
" . $orderby);

            $order_id = [];
            $newlist = [];
            foreach ($list as $v) {
                $order_id[] = $v['id'];
            }
            $uniq_order_id = array_unique($order_id);
            foreach ($uniq_order_id as $v) {
                $child = [];
                foreach ($list as $li) {
                    if($li['order_id'] == $v) {
                        $data['id'] = $li['id'];
                        $data['pay_order_sn'] = $li['pay_order_sn'];
                        $data['total_price'] = $li['total_price'];
                        $data['carriage'] = $li['carriage'];
                        $data['status'] = $li['status'];
                        $data['refund_apply'] = $li['refund_apply'];
                        $data['create_time'] = date('Y-m-d H:i',$li['create_time']);
                        $data_child['goods_id'] = $li['goods_id'];
                        $data_child['cover'] = unserialize($li['pics'])[0];
                        $data_child['goods_name'] = $li['goods_name'];
                        $data_child['num'] = $li['num'];
                        $data_child['unit_price'] = $li['unit_price'];
                        $data_child['total_price'] = sprintf ( "%1\$.2f",($li['unit_price'] * $li['num']));
                        $data_child['attr'] = $li['attr'];
                        $child[] = $data_child;
                    }
                }
                $data['child'] = $child;
                $newlist[] = $data;
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }

        return ajax($newlist);
    }
    //我的售后列表
    public function refundList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $type = input('post.type',1);
        if(!in_array($type,[1,2,3])) {
            return ajax($type,-4);
        }
        $where = "del=0 AND uid=".$this->myinfo['id'];
        $order = " ORDER BY `id` DESC";
        $orderby = " ORDER BY `d`.`id` DESC";
        if($type == 1) {
            $where .= " AND refund_apply=1";
        }else if($type == 2){
            $where .= " AND refund_apply=2";
        }else {
            $where .= " AND refund_apply IN (1,2)";
        }
        try {
            $list = Db::query("SELECT 
`o`.`id`,`o`.`pay_order_sn`,`o`.`pay_price`,`o`.`total_price`,`o`.`carriage`,`o`.`create_time`,`o`.`refund_apply`,`o`.`status`,`o`.`refund_apply`,`d`.`order_id`,`d`.`goods_id`,`d`.`num`,`d`.`unit_price`,`d`.`goods_name`,`d`.`attr`,`g`.`pics` 
FROM (SELECT * FROM mp_order WHERE " . $where . $order . " LIMIT ".($curr_page-1)*$perpage.",".$perpage.") `o` 
LEFT JOIN `mp_order_detail` `d` ON `o`.`id`=`d`.`order_id`
LEFT JOIN `mp_goods` `g` ON `d`.`goods_id`=`g`.`id`
".$orderby);

            $order_id = [];
            $newlist = [];
            foreach ($list as $v) {
                $order_id[] = $v['id'];
            }
            $uniq_order_id = array_unique($order_id);
            foreach ($uniq_order_id as $v) {
                $child = [];
                foreach ($list as $li) {
                    if($li['order_id'] == $v) {
                        $data['id'] = $li['id'];
                        $data['pay_order_sn'] = $li['pay_order_sn'];
                        $data['total_price'] = $li['total_price'];
                        $data['carriage'] = $li['carriage'];
                        $data['status'] = $li['status'];
                        $data['refund_apply'] = $li['refund_apply'];
                        $data['create_time'] = date('Y-m-d H:i',$li['create_time']);
                        $data_child['goods_id'] = $li['goods_id'];
                        $data_child['cover'] = unserialize($li['pics'])[0];
                        $data_child['goods_name'] = $li['goods_name'];
                        $data_child['num'] = $li['num'];
                        $data_child['unit_price'] = $li['unit_price'];
                        $data_child['total_price'] = sprintf ( "%1\$.2f",($li['unit_price'] * $li['num']));
                        $data_child['attr'] = $li['attr'];
                        $child[] = $data_child;
                    }
                }
                $data['child'] = $child;
                $newlist[] = $data;
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }

        return ajax($newlist);
    }
    //查看订单详情
    public function orderDetail() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        $where = [
            ['o.id','=',$val['order_id']],
            ['o.uid','=',$this->myinfo['id']],
            ['o.del','=',0]
        ];
        try {
            $list = Db::table('mp_order')->alias('o')
                ->join("mp_order_detail d","o.id=d.order_id","left")
                ->join("mp_goods g","d.goods_id=g.id","left")
                ->where($where)
                ->field("o.id,o.pay_order_sn,o.pay_price,o.total_price,o.carriage,o.receiver,o.tel,o.address,o.create_time,o.refund_apply,o.status,d.order_id,d.num,d.unit_price,d.goods_name,d.attr,g.pics")->select();
            if(!$list) {
                return ajax('invalid order_id',4);
            }

            $data = [];
            $child = [];
            foreach ($list as $li) {
                $data['pay_order_sn'] = $li['pay_order_sn'];
                $data['receiver'] = $li['receiver'];
                $data['tel'] = $li['tel'];
                $data['address'] = $li['address'];
                $data['total_price'] = $li['total_price'];
                $data['carriage'] = $li['carriage'];
                $data['amount'] = $li['total_price'] - $data['carriage'];
                $data['create_time'] = date('Y-m-d H:i',$li['create_time']);
                $data['refund_apply'] = $li['refund_apply'];
                $data['status'] = $li['status'];
                $data_child['cover'] = unserialize($li['pics'])[0];
                $data_child['goods_name'] = $li['goods_name'];
                $data_child['num'] = $li['num'];
                $data_child['unit_price'] = $li['unit_price'];
                $data_child['total_price'] = sprintf ( "%1\$.2f",($li['unit_price'] * $li['num']));
                $data_child['attr'] = $li['attr'];
                $data_child['cover'] = unserialize($li['pics'])[0];
                $child[] = $data_child;
            }
            $data['child'] = $child;
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }

        return ajax($data);

    }
    //申请退款
    public function refundApply() {
        $val['order_id'] = input('post.order_id');
        $val['reason'] = input('post.reason');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']],
                ['status','in',[1,2,3]],
                ['del','=',0]
            ];
            $exist = Db::table('mp_order')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid order_id',4);
            }
            $update_data = [
                'refund_apply' => 1,
                'reason' => $val['reason']
            ];
            Db::table('mp_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //确认收货
    public function orderConfirm() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']],
                ['status','=',2],
                ['del','=',0]
            ];
            $exist = Db::table('mp_order')->alias('o')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid order_id',44);
            }
            $update_data = [
                'status' => 3,
                'finish_time' => time()
            ];
            Db::table('mp_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //取消订单
    public function orderCancel() {
        $val['order_id'] = input('post.order_id');
        checkPost($val);
        try {
            $where = [
                ['id','=',$val['order_id']],
                ['uid','=',$this->myinfo['id']],
                ['status','=',0],
                ['del','=',0]
            ];
            $exist = Db::table('mp_order')->alias('o')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid order_id',44);
            }
            $update_data = [
                'del' => 1
            ];
            Db::table('mp_order')->where($where)->update($update_data);
            $detail_list = Db::table('mp_order_detail')->where('order_id','=',$exist['id'])->select();
            foreach ($detail_list as $v) {
                if($v['use_attr'] == 1) {
                    Db::table('mp_goods_attr')->where('id','=',$v['attr_id'])->setInc('stock',$v['num']);
                }
                Db::table('mp_goods')->where('id','=',$v['goods_id'])->setInc('stock',$v['num']);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();

    }
    /*------ 商品订单结束 END------*/


    /*------收货地址管理 START------*/
    //我的地址列表
    public function addressList() {
        $uid = $this->myinfo['id'];
        try {
            $where = [
                ['uid','=',$uid]
            ];
            $list = Db::table('mp_address')->where($where)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }
    //添加收货地址
    public function addressAdd() {
        $val['uid'] = $this->myinfo['id'];
        $val['provincename'] = input('post.provincename');
        $val['cityname'] = input('post.cityname');
        $val['countyname'] = input('post.countyname');
        $val['detail'] = input('post.detail');
        $val['postalcode'] = input('post.postalcode');
        $val['tel'] = input('post.tel');
        $val['username'] = input('post.username');
        $val['default'] = input('post.default',0);
        checkPost($val);
        if(!is_tel($val['tel'])) {
            return ajax('',6);
        }
        try {
            $id = Db::table('mp_address')->insertGetId($val);
            if($val['default']) {
                $whereDefault = [
                    ['id','<>',$id],
                    ['uid','=',$val['uid']]
                ];
                Db::table('mp_address')->where($whereDefault)->update(['default'=>0]);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //收货地址详情
    public function addressDetail() {
        $val['id'] = input('post.id');
        checkPost($val);
        $uid = $this->myinfo['id'];
        $where = [
            ['id','=',$val['id']],
            ['uid','=',$uid]
        ];
        try {
            $info = Db::table('mp_address')->where($where)->find();
            if(!$info) {
                return ajax('',-4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }
    //修改收货地址
    public function addressMod() {
        $val['id'] = input('post.id');
        $uid = $this->myinfo['id'];
        $val['provincename'] = input('post.provincename');
        $val['cityname'] = input('post.cityname');
        $val['countyname'] = input('post.countyname');
        $val['detail'] = input('post.detail');
        $val['postalcode'] = input('post.postalcode');
        $val['tel'] = input('post.tel');
        $val['username'] = input('post.username');
        $val['default'] = input('post.default',0);
        checkPost($val);
        if(!is_tel($val['tel'])) {
            return ajax('',6);
        }
        $where = [
            ['id','=',$val['id']],
            ['uid','=',$uid]
        ];
        try {
            $info = Db::table('mp_address')->where($where)->find();
            if(!$info) {
                return ajax('',-4);
            }
            Db::table('mp_address')->where($where)->update($val);
            if($val['default']) {
                $whereDefault = [
                    ['id','<>',$val['id']],
                    ['uid','=',$uid]
                ];
                Db::table('mp_address')->where($whereDefault)->update(['default'=>0]);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //删除收货地址
    public function addressDel() {
        $val['id'] = input('post.id');
        checkPost($val);
        try {
            $uid = $this->myinfo['id'];
            $where = [
                ['id','=',$val['id']],
                ['uid','=',$uid]
            ];
            $info = Db::table('mp_address')->where($where)->find();
            if(!$info) {
                return ajax('',-4);
            }
            Db::table('mp_address')->where($where)->delete();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //获取我的默认收货地址
    public function getDefaultAddress() {
        $uid = $this->myinfo['id'];
        $where = [
            ['default','=',1],
            ['uid','=',$uid]
        ];
        try {
            $info = Db::table('mp_address')->where($where)->find();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($info);
    }
    /*------收货地址管理 END------*/


    /*------ 申请角色 START ------*/
    //获取申请审核状态
    public function applyStatus() {
        $uid = $this->myinfo['id'];
        try {
            $info = Db::table('mp_user')->where('id',$uid)->field('role_check')->find();
            if($info['role_check'] == 3) {
                $info['reason'] = Db::table('mp_user_role')->where('uid','=',$uid)->value('reason');
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info);
    }
    //获取申请信息
    public function applyInfo() {
        $uid = $this->myinfo['id'];
        try {
            $info = Db::table('mp_user_role')->where('uid',$uid)->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($info) {
            $info['works'] = unserialize($info['works']);
            return ajax($info);
        }else {
            return ajax([]);
        }
    }
    //申请角色发送手机短信
    public function sendSms() {
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
            $exist = Db::table('mp_verify')->where('tel',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($sms_data);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($insert_data);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($sms_data);
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
    //申请角色
    public function apply() {
        $val['role'] = input('post.role');
        $val['name'] = input('post.name');
        $val['identity'] = input('post.identity');
        $val['tel'] = input('post.tel');
        $val['code'] = input('post.code');
        $val['uid'] = $this->myinfo['id'];
        checkPost($val);
        $val['desc'] = input('post.desc','');
        $val['org'] = input('post.org');
        $val['address'] = input('post.address');
        $val['busine'] = input('post.busine');
        $val['weixin'] = input('post.weixin');
        $tmp['cover'] = input('post.cover');
        $tmp['id_front'] = input('post.id_front');
        $tmp['id_back'] = input('post.id_back');
        $tmp['license'] = input('post.license');
        $works = input('post.works', []);

        if(!in_array($val['role'],[1,2,3])) {
            return ajax($val['role'],-4);
        }
        if (!isCreditNo_simple($val['identity'])) {
            return ajax('无效的身份证号', 13);
        }
        if (!is_tel($val['tel'])) {
            return ajax('无效的手机号', 6);
        }
        if(!$tmp['cover']) {
            return ajax('请上传封面',33);
        }
        if(!$tmp['id_front'] || !$tmp['id_back']) {
            return ajax('上传身份证正反面',18);
        }
        if(!$tmp['license']) {
            return ajax('请上传资质',55);
        }
        if(!is_array($works)) {
            return ajax('works',-4);
        }
        if($this->myinfo['role_check'] == 1 || $this->myinfo['role_check'] == 2) {
            return ajax('当前状态无法提交审核',62);
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
            $role_exist = Db::table('mp_user_role')->where('uid',$val['uid'])->find();
            if($role_exist) {
                $old_works = unserialize($role_exist['works']);
            }

            //验证图片是否存在
            foreach ($tmp as $k=>$v) {
                $qiniu_exist = $this->qiniuFileExist($v);
                if($qiniu_exist !== true) {
                    return ajax($qiniu_exist['msg'] . ' :'.$k,5);
                }
            }
            //设计师必须传入作品
            if($val['role'] == 2) {
                if (!empty($works)) {
                    if (count($works) > 6) {
                        return ajax('最多上传6张作品', 15);
                    }
                    //验证图片是否存在
                    foreach ($works as $v) {
                        $qiniu_exist = $this->qiniuFileExist($v);
                        if($qiniu_exist !== true) {
                            return ajax($qiniu_exist['msg'] . ' :works:'.$v,5);
                        }
                    }
                } else {
                    return ajax('请传入作品', 14);
                }
            }else {
            //非设计师必须传入参数
                if(!$val['org']) {
                    return ajax('org不能为空',23);
                }
                if(!$val['address']) {
                    return ajax('address不能为空',53);
                }
                if($val['role'] == 3) {
                    if(!$val['busine']) {
                        return ajax('busine不能为空',54);
                    }
                }
            }
            //转移七牛云图片
            foreach ($tmp as $k=>$v) {
                $qiniu_move = $this->moveFile($v,'upload/role/');
                if($qiniu_move['code'] == 0) {
                    $val[$k] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'] .' :' . $k . '',-1);
                }
            }
            $works_array = [];
            if($val['role'] == 2) {
                foreach ($works as $v) {
                    $qiniu_move = $this->moveFile($v,'upload/role/');
                    if($qiniu_move['code'] == 0) {
                        $works_array[] = $qiniu_move['path'];
                    }else {
                        return ajax($qiniu_move['msg'] . ' :works:'.$v,-1);
                    }
                }
            }
            $val['works'] = serialize($works_array);
            unset($val['code']);
            if($role_exist) {
                Db::table('mp_user_role')->where('uid',$val['uid'])->update($val);
            }else {
                $val['create_time'] = time();
                Db::table('mp_user_role')->insert($val);
            }
            Db::table('mp_user')->where('id',$val['uid'])->update([
                'role' => $val['role'],
                'role_check' => 1,
                'org' => $val['org']
            ]);
            Db::table('mp_verify')->where($whereCode)->delete();
        }catch (\Exception $e) {//异常删图
            if($role_exist) {
                foreach ($tmp as $k=>$v) {
                    if(isset($val[$k]) && $role_exist[$k] != $val[$k]) {
                        $this->rs_delete($val[$k]);
                    }
                }
                if($val['role'] == 2) {
                    foreach ($works_array as $v) {
                        if(!in_array($v,$old_works)) {
                            $this->rs_delete($v);
                        }
                    }
                }

            }else {
                foreach ($tmp as $k=>$v) {
                    if(isset($val[$k])) {
                        $this->rs_delete($val[$k]);
                    }
                }
                if($val['role'] == 2) {
                    foreach ($works_array as $v) {
                        $this->rs_delete($v);
                    }
                }
            }
            return ajax($e->getMessage(),-1);
        }
        if($role_exist) {//正常删图
            foreach ($tmp as $k=>$v) {
                if($val[$k] != $role_exist[$k]) {
                    $this->rs_delete($role_exist[$k]);
                }
            }
            if($val['role'] == 2) {
                foreach ($works_array as $v) {
                    if(!in_array($v,$old_works)) {
                        $this->rs_delete($v);
                    }
                }
            }
        }
        return ajax();
    }
    /*------ 申请角色 END ------*/


    //获取快递信息
    public function getKdTrace() {
        $data['order_id'] = input('post.order_id');
        checkPost($data);
        try {
            $whereOrder = [
                ['status','=',2],
                ['id','=',$data['order_id']]
            ];
            $order_exist = Db::table('mp_order')->where($whereOrder)->find();
            if(!$order_exist) {
                return ajax('订单不存在或状态已改变',4);
            }
            $whereTracking = [
                ['name','=',$order_exist['tracking_name']]
            ];
            $tracking_exist = Db::table('mp_tracking')->where($whereTracking)->find();
            if(!$tracking_exist) {
                return ajax('物流不存在',-4);
            }
            $tracking_code = $tracking_exist['code'];
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $kuaidi = new Kuaidiniao();
        $result = $kuaidi->getOrderTracesByJson($tracking_code,$order_exist['tracking_num']);
        $result['tracking_name'] = $order_exist['tracking_name'];
        return ajax($result);
    }









}