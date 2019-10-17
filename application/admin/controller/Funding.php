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
                    ->where($whereWork)->where('factory_id','>',0)->find();
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
            $yet = [];
            $works_ids_yet = Db::table('mp_funding')->where($yet)->column('work_id');
            $whereWork = [
                ['factory_id','>',0]
            ];
            if(!empty($works_ids_yet)) {
                $whereWork[] = ['id','NOT IN',$works_ids_yet];
            }
            $worklist = Db::table('mp_req_works')->where($whereWork)->field('id,title')->select();
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
                ->field('f.*,w.title AS work_title')
                ->where($where)->find();
            if(!$info) { die('非法操作');}
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }
    //编辑众筹
    public function fundingMod() {
        if(request()->isPost()) {
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
                $whereFunding = [
                    ['id','=',$val['id']],
                    ['del','=',0]
                ];
                $funding_exist = Db::table('mp_funding')->where($whereFunding)->find();
                if(!$funding_exist) {
                    return ajax('非法参数id',-1);
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
                Db::table('mp_funding')->update($val);
            } catch (\Exception $e) {
                if($val['cover'] != $funding_exist['cover']) {
                    $this->rs_delete($val['cover']);
                }
                return ajax($e->getMessage(), -1);
            }
            if($val['cover'] != $funding_exist['cover']) {
                $this->rs_delete($funding_exist['cover']);
            }
            return ajax();
        }
    }
    //隐藏众筹
    public function fundingHide() {
        $id = input('post.id','0');
        $map = [
            ['id','=',$id],
            ['status','=',1]
        ];
        try {
            $exist = Db::table('mp_funding')->where($map)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_funding')->where($map)->update(['status'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }
    //显示
    public function fundingShow() {
        $id = input('post.id','0');
        $map = [
            ['id','=',$id],
            ['status','=',0]
        ];
        try {
            $exist = Db::table('mp_funding')->where($map)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_funding')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }
    //删除众筹
    public function fundingDel() {
        $id = input('post.id','0');
        try {
            $whereFunding = [['id','=',$id]];
            $exist = Db::table('mp_funding')->where($whereFunding)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_funding')->where($whereFunding)->update(['del'=>1]);

            $whereGoods = [['funding_id','=',$id]];
            Db::table('mp_funding_goods')->where($whereGoods)->update(['del'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }
    //置顶,取消置顶
    public function recommend() {

    }



    public function goodsList() {
        $param['search'] = input('param.search');
        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);
        $where = [
            ['g.del','=',0]
        ];
        if($param['search']) {
            $where[] = ['g.name','like',"%{$param['search']}%"];
        }
        $count = Db::table('mp_funding_goods')->alias('g')->where($where)->count();

        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        try {
            $list = Db::table('mp_funding_goods')->alias('g')
                ->join('mp_funding f','g.funding_id=f.id','left')
                ->field('g.*,f.title')
                ->where($where)
                ->limit(($curr_page - 1)*$perpage,$perpage)
                ->order(['id'=>'DESC'])
                ->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }

    public function goodsAdd() {
        if(request()->isPost()) {
            $val['funding_id'] = input('post.funding_id');
            $val['name'] = input('post.name');
            $val['price'] = input('post.price');
            $val['send_date'] = input('post.send_date');
            $val['desc'] = input('post.desc');
            $val['create_time'] = time();
            checkInput($val);
            $val['status'] = 0;
            $image = input('post.pic_url',[]);

            try {
                $image_array = [];
                $limit = 6;
                if(is_array($image) && !empty($image)) {
                    if(count($image) > $limit) {
                        return ajax('最多上传'.$limit.'张图片',-1);
                    }
                    foreach ($image as $v) {
                        $qiniu_exist = $this->qiniuFileExist($v);
                        if($qiniu_exist !== true) {
                            return ajax('图片已失效请重新上传',-1);
                        }
                    }
                }else {
                    return ajax('请上传商品图片',-1);
                }
                foreach ($image as $v) {
                    $qiniu_move = $this->moveFile($v,'upload/funding_goods/');
                    if($qiniu_move['code'] == 0) {
                        $image_array[] = $qiniu_move['path'];
                    }else {
                        return ajax($qiniu_move['msg'],-2);
                    }
                }
                $val['pics'] = serialize($image_array);
                Db::table('mp_funding_goods')->insert($val);
            }catch (\Exception $e) {
                foreach ($image_array as $v) {
                    $this->rs_delete($v);
                }
                return ajax($e->getMessage(),-1);
            }
            return ajax([],1);
        }
        try {
            $where = [
                ['del','=',0],
                ['status','=',1]
            ];
            $list = Db::table('mp_funding')->where($where)->field('id,title')->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function goodsDetail() {
        $id = input('param.id');
        try {
            $where = [['g.id','=',$id]];
            $info = Db::table('mp_funding_goods')->alias('g')
                ->join('mp_funding f','g.funding_id=f.id','left')
                ->field('g.*,f.title')
                ->where($where)->find();
            if(!$info) {
                die('非法参数');
            }
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    public function goodsMod() {
        if(request()->isPost()) {
            $val['name'] = input('post.name');
            $val['price'] = input('post.price');
            $val['send_date'] = input('post.send_date');
            $val['desc'] = input('post.desc');
            $val['id'] = input('post.id');
            checkInput($val);
            $image = input('post.pic_url',[]);

            try {
                $whereGoods = [['id','=',$val['id']]];
                $goods_exist = Db::table('mp_funding_goods')->where($whereGoods)->find();
                if(!$goods_exist) {
                    return ajax('非法参数',-1);
                }
                $old_pics = unserialize($goods_exist['pics']);
                $image_array = [];
                $limit = 6;
                if(is_array($image) && !empty($image)) {
                    if(count($image) > $limit) {
                        return ajax('最多上传'.$limit.'张图片',-1);
                    }
                    foreach ($image as $v) {
                        $qiniu_exist = $this->qiniuFileExist($v);
                        if($qiniu_exist !== true) {
                            return ajax('图片已失效请重新上传',-1);
                        }
                    }
                }else {
                    return ajax('请上传商品图片',-1);
                }

                foreach ($image as $v) {
                    $qiniu_move = $this->moveFile($v,'upload/funding_goods/');
                    if($qiniu_move['code'] == 0) {
                        $image_array[] = $qiniu_move['path'];
                    }else {
                        return ajax($qiniu_move['msg'],-2);
                    }
                }
                $val['pics'] = serialize($image_array);
                Db::table('mp_funding_goods')->update($val);
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
            return ajax([],1);
        }
    }

    public function goodsDel() {
        $id = input('post.id','0');
        $map = [
            ['id','=',$id]
        ];
        try {
            $exist = Db::table('mp_funding_goods')->where($map)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_funding_goods')->where($map)->update(['del'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    //下架
    public function goodsHide() {
        $id = input('post.id','0');
        $map = [
            ['id','=',$id],
            ['status','=',1]
        ];
        try {
            Db::table('mp_funding_goods')->where($map)->update(['status'=>0]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }
    //上架
    public function goodsShow() {
        $id = input('post.id','0');
        $map = [
            ['id','=',$id],
            ['status','=',0]
        ];
        try {
            Db::table('mp_funding_goods')->where($map)->update(['status'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }


    public function orderList() {
        $param['search'] = input('param.search','');
        $param['status'] = input('param.status','');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $param['refund_apply'] = input('param.refund_apply','');
        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['o.del','=',0]
        ];
        $order = ['o.id'=>'DESC'];
        if($param['status'] !== '') {
            $where[] = ['o.status','=',$param['status']];
            $where[] = ['o.refund_apply','=',0];
        }
        if($param['refund_apply']) {
            $where[] = ['o.refund_apply','=',$param['refund_apply']];
        }
        if($param['datemin']) {
            $where[] = ['o.create_time','>=',strtotime($param['datemin'])];
        }
        if($param['datemax']) {
            $where[] = ['o.create_time','<',strtotime($param['datemax'])];
        }
        if($param['search']) {
            $where[] = ['o.pay_order_sn|o.tel','LIKE',"%{$param['search']}%"];
        }
        try {
            $count = Db::table('mp_funding_order')->alias('o')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_funding_order')->alias('o')
                ->join('mp_funding_goods g','o.goods_id=g.id','left')
                ->join('mp_funding f','o.funding_id=f.id','left')
                ->where($where)
                ->field('o.*,g.name AS goods_name,g.pics,f.title AS funding_title')
                ->order($order)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        $this->assign('param',$param);
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
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
            $exist = Db::table('mp_funding_order')->where($where)->find();
            if(!$exist) {
                return ajax('订单不存在或状态已改变',-1);
            }
            $update_data = [
                'status' => 2,
                'send_time' => time(),
                'tracking_name' => $val['tracking_name'],
                'tracking_num' => $val['tracking_num']
            ];
            Db::table('mp_funding_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
//订单详情
    public function orderDetail() {
        die('还没写');
    }
//退款
    public function orderRefund() {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $whereOrder = [
                ['id','=',$val['id']],
                ['status','in',[1,2,3]]
            ];
            $order_exist = Db::table('mp_funding_order')->where($whereOrder)->find();
            if(!$order_exist) {
                return ajax('订单不存在或状态已改变',-1);
            }
            $whereFunding = [
                ['id','=',$order_exist['funding_id']]
            ];
            $funding_exist = Db::table('mp_funding')->where($whereFunding)->find();
            if(!$funding_exist) {
                return ajax('未找到此项众筹',-1);
            }
            $whereGoods = [
                ['id','=',$order_exist['goods_id']]
            ];
            $pay_order_sn = $order_exist['pay_order_sn'];

            $order_exist['pay_price'] = 0.01;
            $arr = [
                'appid' => $this->config['app_id'],
                'mch_id'=> $this->config['mch_id'],
                'nonce_str'=>randomkeys(32),
                'sign_type'=>'MD5',
                'transaction_id'=> $order_exist['trans_id'],
                'out_trade_no'=> $pay_order_sn,
                'out_refund_no'=> 'r' . $pay_order_sn,
                'total_fee'=> floatval($order_exist['pay_price'])*100,
                'refund_fee'=> floatval($order_exist['pay_price'])*100,
                'refund_fee_type'=> 'CNY',
                'refund_desc'=> '订单退款',
                'notify_url'=> $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].'/wxRefundNotify',
                'refund_account' => 'REFUND_SOURCE_UNSETTLED_FUNDS'
            ];

            $arr['sign'] = getSign($arr);
            $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
            $res = curl_post_data($url,array2xml($arr),true);

            $result = xml2array($res);
//            $this->refundLog($this->cmd,var_export($result,true));
            if($result && $result['return_code'] == 'SUCCESS') {
                if($result['result_code'] == 'SUCCESS') {
                    $update_data = [
                        'refund_apply' => 2,
                        'refund_time' => time()
                    ];
                    Db::table('mp_funding_order')->where($whereOrder)->update($update_data);
                    if($order_exist['type'] == 1) {
                        $funding_data = [
                            'curr_money' => $funding_exist['curr_money'] - $order_exist['total_price'],
                            'paid_money' => $funding_exist['paid_money'] - $order_exist['total_price'],
                            'order_num' => $funding_exist['order_num'] - 1
                        ];
                        Db::table('mp_funding_goods')->where($whereGoods)->setDec('sales',$order_exist['num']);
                    }else {
                        $funding_data = [
                            'curr_money' => $funding_exist['curr_money'] - $order_exist['total_price'],
                            'free_money' => $funding_exist['free_money'] - $order_exist['total_price'],
                            'order_num' => $funding_exist['order_num'] - 1
                        ];
                    }
                    Db::table('mp_funding')->where($whereFunding)->update($funding_data);
                }else {
                    return ajax($result['err_code_des'],-1);
                }
            }else {
                return ajax('退款通知失败',-1);
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
//删除订单
    public function orderDel() {

    }

    public function modAdress() {
        $val['address'] = input('post.address');
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']],
                ['status','=',0]
            ];
            $exist = Db::table('mp_funding_order')->where($where)->find();
            if(!$exist) {
                return ajax('订单不存在或状态已改变',-1);
            }
            $update_data = [
                'address' => $val['address']
            ];
            Db::table('mp_funding_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    public function modPrice() {
        $val['pay_price'] = input('post.pay_price');
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']],
                ['status','=',0]
            ];
            $exist = Db::table('mp_funding_order')->where($where)->find();
            if(!$exist) {
                return ajax('订单不存在或状态已改变',-1);
            }
            $update_data = [
                'pay_price' => $val['pay_price']
            ];
            Db::table('mp_funding_order')->where($where)->update($update_data);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }



}