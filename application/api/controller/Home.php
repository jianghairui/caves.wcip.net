<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/18
 * Time: 16:49
 */
namespace app\api\controller;

use think\Db;
class Home extends Common {

    //他人主页
    public function home() {
        $val['uid'] = input('post.uid');
        checkPost($val);
        try {
            $whereUser = [
                ['id','=',$val['uid']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->field('id,nickname,avatar,role,level,ifocus,focus,idea_num,works_num')->find();
            if(!$user_exist) {
                return ajax('非法参数uid',-4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        if($user_exist['level'] == 1) {
            $user_exist['level_name'] = '洞丁';
        }else {
            $user_exist['level_name'] = '未知称号';
        }
        return ajax($user_exist);
    }

    //他人主页创意
    public function ideaList() {
        $val['uid'] = input('post.uid');
        $val['order'] = input('post.order');
        checkPost($val);
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',20);
        try {
            $whereUser = [
                ['id','=',$val['uid']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if(!$user_exist) {  return ajax('非法参数uid',-4);}
            $whereIdea = [
                ['i.uid','=',$val['uid']]
            ];
            if($val['order'] == 1) {
                $order = ['i.id'=>'DESC'];
            }else {
                $order = ['i.vote'=>'DESC'];
            }
            $list = Db::table('mp_req_idea')->alias('i')
                ->join('mp_req r','i.req_id=r.id','left')
                ->where($whereIdea)
                ->field('i.id,i.req_id,i.title,i.content,i.works_num,i.vote,i.create_time,r.title AS req_title')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }

    //作品列表
    public function worksList() {
        $val['uid'] = input('post.uid');
        $val['order'] = input('post.order');
        checkPost($val);
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',20);
        try {
            $whereUser = [
                ['id','=',$val['uid']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if(!$user_exist) {  return ajax('非法参数uid',-4);}
            if($val['order'] == 1) {
                $order = ['w.id'=>'DESC'];
            }else {
                $order = ['w.vote'=>'DESC'];
            }
            $whereWorks = [
                ['w.uid','=',$val['uid']]
            ];
            $list = Db::table('mp_req_works')->alias('w')
                ->join("mp_req r", "w.req_id=r.id", "left")
                ->join("mp_req_idea i", "w.idea_id=i.id", "left")
                ->where($whereWorks)
                ->field("w.id,w.req_id,w.idea_id,w.title,w.vote,w.pics,w.bid_num,w.desc,w.create_time,r.title AS req_title,i.title AS idea_title")
                ->order($order)
                ->limit(($curr_page - 1) * $perpage, $perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);

    }

    //活动列表
    public function reqList() {
        $val['uid'] = input('post.uid');
        checkPost($val);
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);

        try {
            $whereUser = [
                ['id','=',$val['uid']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->find();
            if(!$user_exist) {  return ajax('非法参数uid',-4);}
            $whereReq = [
                ['uid', '=', $val['uid']],
                ['status', '=', 1],
                ['del', '=', 0]
            ];
            $list = Db::table('mp_req')
                ->where($whereReq)->order(['start_time' => 'ASC'])
                ->field("id,title,works_num,idea_num,cover,org,start_time,end_time")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax($list);
    }

    //商品列表
    public function goodsList() {
        $val['shop_id'] = input('post.shop_id');
        checkPost($val);
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $where = [
            ['shop_id','=',$val['shop_id']],
            ['status','=',1],
            ['check','=',1],
            ['del','=',0]
        ];
        $order = ['sort'=>'ASC','id'=>'DESC'];

        try {
            $list = Db::table('mp_goods')
                ->where($where)
                ->field("id,name,origin_price,price,sales,pics")
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pic'] = unserialize($v['pics'])[0];
            unset($v['pics']);
        }
        return ajax($list);
    }

    //获取竞标列表
    public function biddingList() {
        $val['uid'] = input('post.uid');
        checkPost($val);
        $curr_page = input('post.page', 1);
        $perpage = input('post.perpage', 10);
        try {
            $whereUser = [
                ['id','=',$val['uid']]
            ];
            $user_exist = Db::table('mp_user')->where($whereUser)->field('id,nickname,avatar,role,level,ifocus,focus,idea_num,works_num')->find();
            if(!$user_exist) {
                return ajax('非法参数uid',-4);
            }
            $whereBidding = [
                ['b.uid','=',$val['uid']]
            ];
            $list = Db::table('mp_bidding')->alias('b')
                ->join("mp_req_works w","b.work_id=w.id","left")
                ->join("mp_req r","b.req_id=r.id","left")
                ->field("b.work_id,b.req_id,b.create_time,b.choose,w.title as work_title,w.desc AS work_detail,w.pics,r.title as req_title,r.org")
                ->limit(($curr_page - 1) * $perpage, $perpage)
                ->where($whereBidding)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }


}