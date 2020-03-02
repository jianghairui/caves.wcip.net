<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2020/1/2
 * Time: 13:25
 */
namespace app\admin\controller;
use think\Db;
class Attract extends Base {

    public function userList() {

    }

    public function recordList() {
        $param['search'] = input('param.search','');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];
        if($param['search']) {
            $where[] = ['name|company|tel','LIKE',"%{$param['search']}%"];
        }
        if($param['datemin']) {
            $where[] = ['create_time','>=',date('Y-m-d 00:00:00',strtotime($param['datemin']))];
        }
        if($param['datemax']) {
            $where[] = ['create_time','<=',date('Y-m-d 23:59:59',strtotime($param['datemax']))];
        }
        try {
            $count = Db::table('mp_attract')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_attract')
                ->where($where)
                ->limit(($curr_page-1)*$perpage,$perpage)
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        $this->assign('param',$param);
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }






}