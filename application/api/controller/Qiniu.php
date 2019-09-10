<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/25
 * Time: 21:29
 */
namespace app\api\controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
class Qiniu extends Common {
    // 生成上传Token
    public function getUpToken() {
        $auth = new Auth(config('qiniu_ak'), config('qiniu_sk'));
        $suffix = input('post.suffix');
        $fkey = create_unique_number('');
        $filename = 'tmp/' . $fkey . $suffix;
        $callbackBody = [
            'fname' => $filename,
            'fkey' => $fkey,
            'desc' => '文件描述'
        ];
        $policy = [
            'callbackUrl' => $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].'/qiniu_callback.php',
            'callbackBody' => json_encode($callbackBody),
//            'mimeLimit' => 'image/*'
        ];
        $token = $auth->uploadToken(config('qiniu_bucket'),null,3600,$policy);
        $data = [
            'token' => $token,
            'domain' => config('qiniu_domain'),
            'filename' => $filename
        ];
        return ajax($data);
    }

    public function index() {
        return $this->fetch();
    }


    public function test() {
        $val['file_path'] = input('post.file_path','');
        try {
            if($val['file_path']) {
                $val['file_path'] = $this->moveFile($val['file_path']);
            }
        } catch(\Exception $e) {

        }
//            $this->rs_delete($order_exist['file_path']);
    }


}