<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/11/22
 * Time: 10:43
 */
namespace app\admin\controller;

use think\Db;
class Tmp extends Base {

    public function acDetail() {
        try {
            $where = [
                ['id','=',1]
            ];
            $info = Db::table('mp_tmp_ac')->where($where)->find();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    public function acMod() {
        $val['id'] = input('post.id');
        checkInput($val);
        $val['content'] = input('post.content');
        $val['create_time'] = date('Y-m-d H:i:s');
        $image = input('post.pic_url',[]);
        try {
            $map = [
                ['id','=',$val['id']]
            ];
            $exist = Db::table('mp_tmp_ac')->where($map)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            $old_pics = unserialize($exist['pics']);

            $image_array = [];
            $limit = 9;
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
                $qiniu_move = $this->moveFile($v,'upload/tmpac/');
                if($qiniu_move['code'] == 0) {
                    $image_array[] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'],-2);
                }
            }
            $val['pics'] = serialize($image_array);

            Db::table('mp_tmp_ac')->where($map)->update($val);
        }catch (\Exception $e) {
            foreach ($image_array as $v) {
                if(!in_array($v,$old_pics)) {
                    $this->rs_delete($v);
                }
            }
            return ajax($e->getMessage(),-1111);
        }
        foreach ($old_pics as $v) {
            if(!in_array($v,$image_array)) {
                $this->rs_delete($v);
            }
        }
        return ajax([],1);
    }

    //会员列表
    public function joinList() {
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);
        $where = [];

        if($param['datemin']) {
            $where[] = ['create_time','>=',date('Y-m-d 00:00:00',strtotime($param['datemin']))];
        }
        if($param['datemax']) {
            $where[] = ['create_time','<=',date('Y-m-d 23:59:59',strtotime($param['datemax']))];
        }
        if($param['search']) {
            $where[] = ['name|tel','like',"%{$param['search']}%"];
        }
        $order = ['id'=>'DESC'];
        try {
            $count = Db::table('mp_tmp_sign')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_tmp_sign')->where($where)
                ->order($order)
                ->limit(($curr_page - 1)*$perpage,$perpage)->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        return $this->fetch();
    }




}