<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2018/9/25
 * Time: 16:09
 */
namespace app\admin\controller;
use think\Db;
use think\Exception;
use EasyWeChat\Factory;
use think\exception\HttpResponseException;

class User extends Base {

    //会员列表
    public function userList() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];

        if(!is_null($param['status']) && $param['status'] !== '') {
            $where[] = ['status','=',$param['status']];
        }
        if($param['logmin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }

        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }

        if($param['search']) {
            $where[] = ['nickname|tel','like',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_user')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_user')->where($where)->order(['id'=>'DESC'])->limit(($curr_page - 1)*$perpage,$perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('status',$param['status']);
        return $this->fetch();
    }

    //用户详情
    public function userDetail() {
        $id = input('param.id');
        $where = [
            ['id','=',$id]
        ];
        try {
            $info = Db::table('mp_user')->alias('u')
                ->join('mp_user_role r','u.id=r.uid','LEFT')
                ->field('u.*,r.name,r.identity,r.id_front,r.id_back,r.tel as role_tel,r.weixin,r.works,r.license')
                ->where($where)
                ->find();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('info',$info);
        return $this->fetch();
    }
    //申请角色-通过审核
    public function rolePass() {
        $map = [
            ['role_check','=',1],
            ['id','=',input('post.id',0)]
        ];
        try {
            $exist = Db::table('mp_user')->where($map)->field('id')->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_user')->where($map)->update(['role_check'=>2]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }
    //申请角色-拒绝审核
    public function roleReject() {
        $map = [
            ['role_check','=',1],
            ['id','=',input('post.id',0)]
        ];
        $reason = input('post.reason','');
        try {
            $exist = Db::table('mp_user')->where($map)->field('id')->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_user')->where($map)->update(['role_check'=>3]);
            Db::table('mp_user_role')->where('uid','=',$exist['id'])->update(['reason'=>$reason]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //充值类目列表
    public function vipList() {
        $where = [];
        $where[] = ['status','=',1];
        try {
            $list = Db::table('mp_vip')->where($where)->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        $this->assign('list',$list);
        return $this->fetch();
    }
    //添加充值类目
    public function vipAdd() {
        return $this->fetch();
    }
    //添加充值类目POST  todo qiniu
    public function vipAddPost() {
        $val['title'] = input('post.title');
        $val['price'] = input('post.price');
        $val['detail'] = input('post.detail');
        $val['days'] = input('post.days');
        checkInput($val);
        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }else {
            return ajax('请上传图片',-1);
        }

        try {
            Db::table('mp_vip')->insert($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);

    }
    //充值类目详情
    public function vipDetail() {
        $id = input('param.id');
        try {
            $info = Db::table('mp_vip')->where('id',$id)->find();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        return $this->fetch();
    }
    //充值类目编辑    todo qiniu
    public function vipModPost() {
        $val['title'] = input('post.title');
        $val['price'] = input('post.price');
        $val['detail'] = input('post.detail');
        $val['days'] = input('post.days');
        $val['id'] = input('post.id');
        checkInput($val);
        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }
        try {
            $where = [
                ['id','=',$val['id']]
            ];
            $exist = Db::table('mp_vip')->where($where)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_vip')->where($where)->update($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        if(isset($val['pic'])) {
            @unlink($exist['pic']);
        }
        return ajax([],1);

    }
    //删除会员
    public function vipDel() {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $exist = Db::table('mp_vip')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            $model = model('vip');
            $model::destroy($val['id']);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    //拉黑用户
    public function userStop() {
        $id = input('post.id');
        $map = [
            ['status','=',1],
            ['id','=',$id]
        ];
        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>2]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax('拉黑失败',-1);
        }
    }
    //恢复用户
    public function userGetback() {
        $id = input('post.id');
        $map = [
            ['status','=',2],
            ['id','=',$id]
        ];
        try {
            $res = Db::table('mp_user')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        if($res) {
            return ajax([],1);
        }else {
            return ajax('恢复失败',-1);
        }
    }

    public function rechargeList() {
        $param['status'] = input('param.status','');
        $param['logmin'] = input('param.logmin');
        $param['logmax'] = input('param.logmax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        if($param['logmin']) {
            $where[] = ['o.create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['logmin'])))];
        }
        if($param['logmax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['logmax'])))];
        }
        if($param['search']) {
            $where[] = ['nickname|tel','like',"%{$param['search']}%"];
        }

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];
        try {
            $count = Db::table('mp_vip_order')->alias('o')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_vip_order')->alias('o')
                ->join("mp_user u","o.uid=u.id","left")
                ->join("mp_vip v","o.vip_id=v.id","left")
                ->order(['o.create_time'=>'DESC'])
                ->field("o.*,u.nickname,u.avatar,v.title")
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    public function rechargeDetail() {
        $id = input('param.id');
        try {
            $where = [
                ['o.id','=',$id]
            ];
            $info = Db::table('mp_vip_order')->alias('o')
                ->join("mp_vip v","o.vip_id=v.id","left")
                ->where($where)
                ->field("o.*,v.title,v.detail,v.pic")
                ->find();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    //订单发货
    public function orderSend() {
        $id = input('param.id');
        try {
            $where = [
                ['del','=',0]
            ];
            $list = Db::table('mp_tracking')->where($where)->select();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('id',$id);
        return $this->fetch();
    }

    //确认发货
    public function deliver() {
        $val['tracking_name'] = input('post.tracking_name');
        $val['tracking_num'] = input('post.tracking_num');
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']],
                ['status','=',1]
            ];
            $exist = Db::table('mp_vip_order')->where($where)->find();
            if(!$exist) {
                return ajax('订单不存在或状态已改变',-1);
            }
            $update_data = [
                'status' => 2,
                'send_time' => time(),
                'tracking_name' => $val['tracking_name'],
                'tracking_num' => $val['tracking_num']
            ];
            Db::table('mp_vip_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }


}