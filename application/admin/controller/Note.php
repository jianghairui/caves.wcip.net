<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/1/9
 * Time: 11:09
 */
namespace  app\admin\controller;
use think\Db;
class Note extends Base {

    public function noteList() {

        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $param['search'] = input('param.search');
        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [
            ['n.del','=',0]
        ];

        if($param['datemin']) {
            $where[] = ['n.create_time','>=',date('Y-m-d 00:00:00',strtotime($param['datemin']))];
        }

        if($param['datemax']) {
            $where[] = ['n.create_time','<=',date('Y-m-d 23:59:59',strtotime($param['datemax']))];
        }

        if($param['search']) {
            $where[] = ['n.title|n.content','like',"%{$param['search']}%"];
        }

        $count = Db::table('mp_note')->alias("n")->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        try {
            $list = Db::table('mp_note')->alias('n')
                ->join("mp_user u","n.uid=u.id","left")
                ->field("n.*,u.nickname")
                ->order(['n.id'=>'DESC'])
                ->where($where)->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    public function notePass() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        try {
            Db::startTrans();

            $exist = Db::table('mp_note')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_note')->where($map)->update(['status'=>1]);
            //审核笔记时
            $whereNote = [
                ['uid','=',$exist['uid']],
                ['status','=',1]
            ];
            $count = Db::table('mp_note')->where($whereNote)->count();
            Db::table('mp_user')->where('id','=',$exist['uid'])->update(['note_num'=>($count+1)]);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function noteReject() {
        $map = [
            ['status','=',0],
            ['id','=',input('post.id',0)]
        ];
        try {
            $reason = input('post.reason');
            $exist = Db::table('mp_note')->where($map)->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            $update_data = [
                'status' => 2,
                'reason' => $reason
            ];
            Db::table('mp_note')->where($map)->update($update_data);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function noteDetail() {
        $id = input('param.id');
        try {
            $info = Db::table('mp_note')->alias("n")
                ->join("mp_user u","n.uid=u.id","left")
                ->field("n.*,u.nickname")
                ->where('n.id','=',$id)->find();
        }catch (\Exception $e) {
            die('参数无效');
        }
        $this->assign('info',$info);
        $this->assign('qiniu_weburl',config('qiniu_weburl'));
        return $this->fetch();
    }

    public function noteDel() {
        $val['id'] = input('post.id',0);
        checkInput($val);
        try {
            $whereNote = [
                ['id','=',$val['id']]
            ];
            $exist = Db::table('mp_note')->where($whereNote)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_note')->where($whereNote)->update(['del'=>1]);
            if($exist['status'] == 1) {
                Db::table('mp_user')->where('id','=',$exist['uid'])->setDec('note_num',1);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([],1);
    }

    public function noteModPost() {
        $val['title'] = input('post.title');
        $val['content'] = input('post.content');
        $val['id'] = input('post.id');
        try {
            $where = [
                ['id','=',$val['id']]
            ];
            Db::table('mp_note')->where($where)->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function recommend() {
        $id = input('post.id');
        try {
            $where = [
                ['id','=',$id]
            ];
            $exist = Db::table('mp_note')->where($where)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            if($exist['recommend'] == 1) {
                Db::table('mp_note')->where($where)->update(['recommend'=>0]);
                return ajax(false);
            }else {
                Db::table('mp_note')->where($where)->update(['recommend'=>1]);
                return ajax(true);
            }
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
    }

}