<?php
/**
 * Created by PhpStorm.
 * User: Jiang
 * Date: 2019/8/26
 * Time: 18:48
 */
define('ROOT_PATH',dirname(__DIR__));
$_body = file_get_contents('php://input');
$body = json_decode($_body, true);
qiniuLog('qiniu_callback.php',var_export($body,true));
header('Content-Type: application/json');
$resp = array('ret' => 'success');
echo json_encode($resp);

function qiniuLog($cmd,$str) {
    $file= ROOT_PATH . '/qiniu_error.log';
    $text='[Time ' . date('Y-m-d H:i:s') ."]\ncmd:" .$cmd. "\n" .$str. "\n---END---" . "\n";
    if(false !== fopen($file,'a+')){
        file_put_contents($file,$text,FILE_APPEND);
    }else{
        qiniuLog('qiniu_callback.php', '创建'.$file.'失败');
    }
}