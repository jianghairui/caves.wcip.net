<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/6
 * Time: 17:01
 */
namespace app\admin\controller;

class Test extends Base {

    public function index() {
        try {
            $str = [];
            $a = substr($str,0,4);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -999);
        }
        echo $a;
    }


}