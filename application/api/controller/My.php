<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/3/11
 * Time: 16:00
 */
namespace app\api\controller;
use my\Sendsms;
use think\Db;
class My extends Common {
    //获取个人信息
    public function mydetail() {
        $map = [
            ['id','=',$this->myinfo['id']]
        ];
        try {
            $info = Db::table('mp_user')->where($map)->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($info);
    }
    //点击头像编辑个人资料
    public function modMyInfo() {
        $val['nickname'] = input('post.nickname');
        $val['realname'] = input('post.realname');
        $val['sex'] = input('post.sex');
        $val['age'] = input('post.age');
        $val['tel'] = input('post.tel');
        checkPost($val);
        $val['sign'] = input('post.sign','');
        $user = $this->myinfo;
        if(!is_tel($val['tel'])) {
            return ajax('无效的手机号',6);
        }
        if(!$this->msgSecCheck($val['nickname'])) {
            return ajax('昵称包含敏感词',68);
        }
        if(!$this->msgSecCheck($val['sign'])) {
            return ajax('签名包含敏感词',69);
        }
        try {
            $avatar = input('post.avatar');
            if($avatar) {
                if (substr($avatar,0,4) == 'http') {
                    $val['avatar'] = $avatar;
                }else {
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
            Db::table('mp_user')->where('id','=',$this->myinfo['id'])->update($val);
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

        $where = [
            ['id','=',$val['id']],
            ['uid','=',$this->myinfo['id']]
        ];
        try {
            $exist = Db::table('mp_note')->where($where)->find();
            if(!$exist) {
                return ajax($val['id'],-4);
            }
            if($exist['status'] == 0) {
                return ajax('当前状态无法修改',34);
            }
            $old_pics = unserialize($exist['pics']);
            if(is_array($image) && !empty($image)) {
                if(count($image) > 9) {
                    return ajax('最多上传9张图片',8);
                }
                foreach ($image as $v) {
                    if(!file_exists($v)) {
                        return ajax($v,5);
                    }
                }
            }else {
                return ajax('请传入图片',3);
            }
            $image_array = [];
            foreach ($image as $v) {
                $image_array[] = rename_file($v);
            }
            $val['pics'] = serialize($image_array);
            $val['status'] = 0;
            Db::table('mp_note')->where($where)->update($val);
        }catch (\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    @unlink($v);
                }
            }
            return ajax($e->getMessage(),-1);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                @unlink($v);
            }
        }
        return ajax();
    }
    //获取我的收藏笔记列表
    public function getMyCollectedNoteList() {
        $page = input('page',1);
        $perpage = input('perpage',10);
        $where = [
            ['c.uid','=',$this->myinfo['id']]
        ];
        try {
            $ret['count'] = Db::table('mp_note_collect')->alias('c')->where($where)->count();
            $list = Db::table('mp_note_collect')->alias('c')
                ->join('mp_note n','c.note_id=n.id','left')
                ->join('mp_user u','n.uid=u.id','left')
                ->where($where)
                ->field('n.id,n.title,n.pics,n.width,n.height,u.nickname,u.avatar,n.like')
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




    /*------ 博物馆独有接口 START ------*/
    //我发布的需求
    public function myReqList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $uid = $this->myinfo['id'];
        try {
            $user = $this->myinfo;
            if($user['role_check'] != 2 || $user['role'] != 1) {
                return ajax([]);
            }
            $whereReq = [
                ['r.del','=',0],
                ['r.uid','=',$uid]
            ];
            $list = Db::table('mp_req')->alias('r')
                ->join("mp_user u","r.uid=u.id","left")
                ->where($whereReq)->order(['r.id'=>'DESC'])
                ->field("r.id,r.title,r.cover,r.org,r.works_num,r.idea_num,r.status,r.start_time,r.end_time")
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($list);
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
        try {
            $where = [
                ['uid','=',$this->myinfo['id']]
            ];
            $list = Db::table('mp_show_works')
                ->where($where)
                ->field("id,title,desc,pics")
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
        try {
            $where = [
                ['w.uid','=',$this->myinfo['id']]
            ];
            if($status !== '' && !is_null($status)) {
                $where[] = ['w.status','=',$status];
            }
            $list = Db::table('mp_req_works')->alias('w')
                ->join('mp_req r','w.req_id=r.id','left')
                ->where($where)
                ->field("w.id,w.title,w.req_id,w.vote,w.bid_num,w.pics,w.status,w.reason,r.title AS req_title,r.org")
                ->limit(($curr_page-1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
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








    /*------ 商品订单管理 START------*/
    //我的订单列表
    public function orderList() {
        $curr_page = input('post.page',1);
        $perpage = input('post.perpage',10);
        $status = input('post.status','');
        $where = "uid=".$this->myinfo['id'];
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
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
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
        $where = "uid=".$this->myinfo['id'];
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
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
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
        return ajax($newlist);
    }
    //查看订单详情
    public function orderDetail() {
        $val['id'] = input('post.id');
        checkPost($val);
        $where = [
            ['o.id','=',$val['id']],
            ['o.uid','=',$this->myinfo['id']]
        ];
        try {
            $list = Db::table('mp_order')->alias('o')
                ->join("mp_order_detail d","o.id=d.order_id","left")
                ->join("mp_goods g","d.goods_id=g.id","left")
                ->where($where)
                ->field("o.id,o.pay_order_sn,o.pay_price,o.total_price,o.carriage,o.receiver,o.tel,o.address,o.create_time,o.refund_apply,o.status,d.order_id,d.num,d.unit_price,d.goods_name,d.attr,g.pics")->select();
            if(!$list) {
                return ajax($val['id'],-4);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
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
        return ajax($data);

    }
    //申请退款
    public function refundApply() {
        $val['pay_order_sn'] = input('post.pay_order_sn');
        $val['reason'] = input('post.reason');
        checkPost($val);
        try {
            $where = [
                ['pay_order_sn','=',$val['pay_order_sn']],
                ['uid','=',$this->myinfo['id']],
                ['status','in',[1,2,3]]
            ];
            $exist = Db::table('mp_order')->alias('o')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid pay_order_sn',44);
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
        $val['pay_order_sn'] = input('post.pay_order_sn');
        checkPost($val);
        try {
            $where = [
                ['pay_order_sn','=',$val['pay_order_sn']],
                ['uid','=',$this->myinfo['id']],
                ['status','=',2]
            ];
            $exist = Db::table('mp_order')->alias('o')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid pay_order_sn',44);
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
        $val['pay_order_sn'] = input('post.pay_order_sn');
        checkPost($val);
        try {
            $where = [
                ['pay_order_sn','=',$val['pay_order_sn']],
                ['uid','=',$this->myinfo['id']],
                ['status','=',0],
                ['del','=',0]
            ];
            $exist = Db::table('mp_order')->alias('o')->where($where)->find();
            if(!$exist) {
                return ajax( 'invalid pay_order_sn',44);
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
            $param = [
                'tel' => $tel,
                'code' => mt_rand(100000,999999),
                'create_time' => time()
            ];
            $exist = Db::table('mp_verify')->where('tel',$tel)->find();
            if($exist) {
                if((time() - $exist['create_time']) < 60) {
                    return ajax('1分钟内不可重复发送',11);
                }
                $res = $sms->send($param);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->where('tel',$tel)->update($param);
                    return ajax();
                }else {
                    return ajax($res->Message,12);
                }
            }else {
                $res = $sms->send($param);
                if($res->Code === 'OK') {
                    Db::table('mp_verify')->insert($param);
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



}