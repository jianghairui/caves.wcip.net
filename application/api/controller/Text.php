<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/9/10
 * Time: 9:40
 */
namespace app\api\controller;

use EasyWeChat\Factory;
use think\Db;
class Text extends Common {

    public function audit() {
        $content = input('post.text');
        $result = $this->msgSecCheck($content);
        return ajax($result);
    }




}