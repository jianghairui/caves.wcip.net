<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/6/25
 * Time: 21:29
 */
namespace app\user\controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
class Qiniu extends Base {
    protected $accessKey;
    protected $secretKey;
    protected $bucket;
    protected $domain;

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
            'mimeLimit' => 'image/*'
        ];
        $token = $auth->uploadToken(config('qiniu_bucket'),null,3600,$policy);
        $data = [
            'token' => $token,
            'domain' => config('qiniu_domain'),
            'weburl' => config('qiniu_weburl'),
            'filename' => $filename
        ];
        return ajax($data);
    }

    public function index() {
        return $this->fetch();
    }


    public function test() {
        $val['file_path'] = 'upload/slide/156690018059831600333.JPG';

        $qiniu_exist = $this->qiniuFileExist($val['file_path']);

        halt($qiniu_exist);

//        if($qiniu_exist !== true) {
//            return ajax($qiniu_exist['msg'],-1);
//        }

//
//        $qiniu_move = $this->moveFile($val['file_path']);
//        if($qiniu_move['code'] == 0) {
//            $val['file_path'] = $qiniu_move['path'];
//        }else {
//            return ajax($qiniu_move['msg'],-2);
//        }
//        $this->rs_delete($val['file_path']);

    }


}