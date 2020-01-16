<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2020/1/2
 * Time: 13:25
 */
namespace app\admin\controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Db;
class Yu extends Base {

    public function userList() {

    }

    public function recordList() {
        $param['search'] = input('param.search','');
        $param['status'] = input('param.status','');
        $param['send'] = input('param.send','');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];
        if($param['search']) {
            $where[] = ['y.card_no|y.tel|y.receiver','LIKE',"%{$param['search']}%"];
        }
        if($param['status'] !== '') {
            $where[] = ['y.status','=',$param['status']];
        }
        if($param['send'] !== '') {
            $where[] = ['y.send','=',$param['send']];
        }
        if($param['datemin']) {
            $where[] = ['y.take_time','>=',date('Y-m-d 00:00:00',strtotime($param['datemin']))];
        }
        if($param['datemax']) {
            $where[] = ['y.take_time','<=',date('Y-m-d 23:59:59',strtotime($param['datemax']))];

        }
        try {
            $count = Db::table('mp_yu')->alias('y')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_yu')->alias('y')
                ->join('mp_yu_user u','y.uid=u.id','left')
                ->where($where)
                ->field('y.*,u.avatar,u.nickname')
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
        $val['tracking_no'] = input('post.tracking_no');
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $where = [
                ['id','=',$val['id']],
                ['status','=',1],
                ['send','=',0]
            ];
            $exist = Db::table('mp_yu')->where($where)->find();
            if(!$exist) {
                return ajax('单号不存在或状态已改变',-1);
            }
            $val['send'] = 1;
            $val['send_time'] = date('Y-m-d H:i:s');
            Db::table('mp_yu')->where($where)->update($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    public function toExcel() {
        $param['search'] = input('param.search','');
        $param['status'] = input('param.status','');
        $param['send'] = input('param.send','');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $page['query'] = http_build_query(input('param.'));

        $where = [];
        if($param['search']) {
            $where[] = ['y.card_no|y.tel','LIKE',"%{$param['search']}%"];
        }
        if($param['status'] !== '') {
            $where[] = ['y.status','=',$param['status']];
        }
        if($param['send'] !== '') {
            $where[] = ['y.send','=',$param['send']];
        }
        if($param['datemin']) {
            $where[] = ['y.take_time','>=',date('Y-m-d 00:00:00',strtotime($param['datemin']))];
        }
        if($param['datemax']) {
            $where[] = ['y.take_time','<=',date('Y-m-d 23:59:59',strtotime($param['datemax']))];
        }
        try {
            $list = Db::table('mp_yu')->alias('y')
                ->join('mp_yu_user u','y.uid=u.id','left')
                ->where($where)
                ->field('y.*,u.avatar,u.nickname')
                ->select();
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('纸巾机统计');

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(50);
        $sheet->getColumnDimension('K')->setWidth(30);

        $sheet->getStyle('E')->getNumberFormat()->setFormatCode( \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $sheet->getStyle('A:K')->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->setCellValue('A1', '序列号');
        $sheet->setCellValue('B1', '是否使用');
        $sheet->setCellValue('C1', '使用时间');
        $sheet->setCellValue('D1', '收货人');
        $sheet->setCellValue('E1', '手机号');
        $sheet->setCellValue('F1', '省');
        $sheet->setCellValue('G1', '市');
        $sheet->setCellValue('H1', '区');
        $sheet->setCellValue('I1', '详细地址');
        $sheet->setCellValue('J1', '是否发货');
        $sheet->setCellValue('K1', '发货时间');

        $index = 2;
        foreach ($list as $v) {
            $status = $v['status'] ? '已使用' : '未使用';
            $send = $v['send'] ? '已发货' : '未发货';

            $sheet->setCellValue('A'.$index, $v['card_no']);
            $sheet->setCellValue('B'.$index, $status);
            $sheet->setCellValue('C'.$index, $v['take_time']);
            $sheet->setCellValue('D'.$index, $v['receiver']);
            $sheet->setCellValue('E'.$index, $v['tel']);
            $sheet->setCellValue('F'.$index, $v['province']);
            $sheet->setCellValue('G'.$index, $v['city']);
            $sheet->setCellValue('H'.$index, $v['region']);
            $sheet->setCellValue('I'.$index, $v['address']);
            $sheet->setCellValue('J'.$index, $send);
            $sheet->setCellValue('K'.$index, $v['send_time']);
            $index++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
//header(‘Content-Type:application/vnd.ms-excel‘);//告诉浏览器将要输出Excel03版本文件
        header('Content-Disposition: attachment;filename="nanhuyu'.date('Ymd').'.xlsx"');//告诉浏览器输出浏览器名称
        header('Cache-Control: max-age=0');//禁止缓存
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

    }




}