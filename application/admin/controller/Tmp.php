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
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        $val['address'] = input('post.address');
        $val['host'] = input('post.host');
        $val['co'] = input('post.co');
        $val['union_host'] = input('post.union_host');
        $val['id'] = input('post.id');
        $val['start_time'] = input('post.start_time');
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




}