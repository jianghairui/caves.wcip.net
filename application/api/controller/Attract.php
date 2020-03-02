<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/12/30
 * Time: 10:07
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use my\Sendsms;
class Attract extends Controller {


    public function index() {
        return $this->fetch();
    }

    public function hezuo() {
        $val['province'] = input('post.province');//
        $val['city'] = input('post.city');//
        $val['region'] = input('post.region');
        $val['name'] = input('post.name');
        $val['tel'] = input('post.tel');
        $val['email'] = input('post.email');
        $val['company'] = input('post.company');
        $val['desc'] = input('post.desc');
        checkPost($val);
        try {
            if(!is_tel($val['tel'])) {
                return ajax('invalid tel',6);
            }
            if(!is_email($val['email'])) {
                return ajax('invalid email',7);
            }
            Db::table('mp_attract')->insert($val);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }





















}