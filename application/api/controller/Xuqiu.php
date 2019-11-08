<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/11/5
 * Time: 14:55
 */
namespace app\api\controller;

use think\Db;
class Xuqiu extends Common {

    public function xuqiuList() {
        $search = input('post.search','');
        $page = input('page',1);
        $perpage = input('perpage',10);
        $where = [
            ['x.status','=',1],
            ['x.del','=',0],
        ];
        if($search) {
            $where[] = ['x.title','like',"%{$search}%"];
        }
        try {
            $list = Db::table('mp_xuqiu')->alias('x')
                ->join('mp_user u','x.uid=u.id','left')
                ->where($where)
                ->field('x.id,x.title,x.pics,x.content,x.create_time,u.nickname,u.avatar,u.role,u.role_check')
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


    public function homeXuqiuList() {
        $where = [
            ['x.status','=',1],
            ['x.del','=',0],
            ['x.recommend','=',1]
        ];
        try {
            $list = Db::table('mp_xuqiu')->alias('x')
                ->join('mp_user u','x.uid=u.id','left')
                ->where($where)
                ->field('x.id,x.title,x.pics,x.content,x.create_time,u.nickname,u.avatar,u.role,u.role_check')
                ->order(['x.create_time'=>'DESC'])
                ->limit(0,4)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        foreach ($list as &$v) {
            $v['pics'] = unserialize($v['pics']);
        }
        return ajax($list);
    }


    public function xuqiuAdd() {
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
        $val['create_time'] = time();
        $image = input('post.pics',[]);

        if($this->myinfo['role_check'] != 2) {
            return ajax('只有认证用户才能操作',92);
        }
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
        }
        $image_array = [];
        //转移七牛云图片
        foreach ($image as $v) {
            $qiniu_move = $this->moveFile($v,'upload/xuqiu/');
            if($qiniu_move['code'] == 0) {
                $image_array[] = $qiniu_move['path'];
            }else {
                return ajax($qiniu_move['msg'] .' :' . $v . '',-1);
            }
        }
        $val['pics'] = serialize($image_array);
        try {
            Db::table('mp_xuqiu')->insert($val);
        }catch (\Exception $e) {
            foreach ($image_array as $v) {
                $this->rs_delete($v);
            }
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function xuqiuDetail() {
        $val['xuqiu_id'] = input('post.xuqiu_id');
        checkPost($val);
        try {
            $whereXuqiu = [
                ['x.id','=',$val['xuqiu_id']]
            ];
            $info = Db::table('mp_xuqiu')->alias('x')
                ->join('mp_user u','x.uid=u.id','left')
                ->where($whereXuqiu)
                ->field('x.id,x.uid,x.title,x.content,x.pics,x.status,x.reason,x.create_time,u.nickname,u.avatar,u.role,u.role_check')
                ->find();
            if(!$info) {
                return ajax('invalid xuqiu_id',-4);
            }
            $whereComment = [
                ['c.to_cid','=',0],
                ['c.xuqiu_id','=',$val['xuqiu_id']]
            ];
            $info['comment_count'] =
                Db::table('mp_xuqiu_comment')->alias('c')->where($whereComment)->count();
            $info['comment_list'] = Db::table('mp_xuqiu_comment')->alias('c')
                ->join('mp_user u','c.uid=u.id','left')
                ->where($whereComment)
                ->field('c.*,u.nickname,u.avatar,u.role,u.role_check')
                ->limit(0,2)->select();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }

        $info['pics'] = unserialize($info['pics']);
        return ajax($info);
    }

    public function xuqiuMod() {
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
        }

        try {
            $whereXuqiu = [
                ['id','=',$val['id']],
                ['uid','=',$this->myinfo['id']]
            ];
            $exist = Db::table('mp_xuqiu')->where($whereXuqiu)->find();
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
                $qiniu_move = $this->moveFile($v,'upload/xuqiu/');
                if($qiniu_move['code'] == 0) {
                    $image_array[] = $qiniu_move['path'];
                }else {
                    return ajax($qiniu_move['msg'] .' :' . $v . '',-1);
                }
            }
            $val['pics'] = serialize($image_array);
            $val['status'] = 0;
            Db::table('mp_xuqiu')->where($whereXuqiu)->update($val);
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
    //发表评论
    public function commentAdd() {
        $val['xuqiu_id'] = input('post.xuqiu_id');
        $val['content'] = input('post.content');
        checkPost($val);
        $val['uid'] = $this->myinfo['id'];
        $val['to_cid'] = input('post.to_cid');

        if($this->myinfo['role_check'] != 2) {
            return ajax('只有认证用户才能操作',92);
        }

        if(!$this->msgSecCheck($val['content'])) {
            return ajax('评论内容包含敏感词',65);
        }
        try {
            $exist = Db::table('mp_xuqiu')->where('id',$val['xuqiu_id'])->find();
            if(!$exist) {
                return ajax('invalid xuqiu_id',-4);
            }
            if($val['to_cid']) {
                $map = [
                    ['id','=',$val['to_cid']],
                    ['xuqiu_id','=',$val['xuqiu_id']]
                ];
                $comment_exist = Db::table('mp_xuqiu_comment')->where($map)->find();
                if($comment_exist) {
                    $val['to_uid'] = $comment_exist['uid'];
                    if($comment_exist['to_cid'] == 0) {
                        $val['root_cid'] = $comment_exist['id'];
                    }else {
                        $val['root_cid'] = $comment_exist['root_cid'];
                    }
                }else {
                    return ajax('invalid to_cid',-4);
                }
            }else {
                $val['to_cid'] = 0;
                $val['to_uid'] = 0;
                $val['root_cid'] = 0;
            }
            $val['create_time'] = time();
            Db::table('mp_xuqiu_comment')->insert($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    //获取评论列表
    public function commentList() {
        $val['xuqiu_id'] = input('post.xuqiu_id');
        checkPost($val);
        try {
            $exist = Db::table('mp_xuqiu')->where('id','=',$val['xuqiu_id'])->find();
            if(!$exist) {
                return ajax('invalid xuqiu_id',-4);
            }
            $list = DB::query("SELECT c.id,c.xuqiu_id,c.uid,c.to_cid,c.to_uid,c.content,c.root_cid,c.create_time,u.avatar,u.nickname,IFNULL(u2.nickname,'') AS to_nickname 
FROM mp_xuqiu_comment c 
LEFT JOIN mp_user u ON c.uid=u.id 
LEFT JOIN mp_user u2 ON c.to_uid=u2.id 
WHERE c.xuqiu_id=?",[$val['xuqiu_id']]);
            $list = $this->recursion($list);
            return ajax($list);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

    private function sortMerge($node,$pid=0)
    {
        $arr = array();
        foreach($node as $key=>$v)
        {
            if($v['pid'] == $pid)
            {
                $v['child'] = $this->sortMerge($node,$v['id']);
                $arr[] = $v;
            }
        }
        return $arr;
    }

    private function recursion($array,$to_cid=0) {
        $to_array = [];
        foreach ($array as $v) {
            if($v['root_cid'] == $to_cid) {
                $v['child'] = $this->recursion($array,$v['id']);
                $to_array[] = $v;
            }
        }
        return $to_array;
    }


}