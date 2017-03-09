<?php

/**
 * examples
 *
 */

class User {
    public static function getInfo()
    {
        $res = array('name'=>'xmc','password'=>'123456');
        return rand(0, 10) > 2 ? $res : false;
    }

    public static function addInfo()
    {
        $name = substr(str_shuffle('abcdefghijklumnopqrstuvwxyz'), 0, 4);
        $password = substr(str_shuffle('1234567890'), 0, 6);
        $res = array('name' => $name,'password' => $password);
        return rand(0, 10) > 3 ? $res : false;
    }

    public static function getErrCode($errcode = 10001)
    {
        return $errcode;
    }

    public static function getErrMsg($errmsg = '添加用户失败')
    {
        return $errmsg;
    }
}

include 'StatisticClient.php';

$start = date('Y-m-d H:i:s', time());
echo "start at {$start} ...\n";
$rand = rand(0, 10);
if ($rand > 3) {
    // 统计开始
    StatisticClient::tick("User", 'getInfo');
// 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
// 假如有个User::getInfo方法要监控
    $user_info = User::getInfo();
    if(!$user_info){
        // 标记失败
        $success = false;
        // 获取错误码，假如getErrCode()获得
        $code = User::getErrCode(10006);
        // 获取错误日志，假如getErrMsg()获得
        $msg = User::getErrMsg('获取用户失败');
    }
// 上报结果
    $res = StatisticClient::report('User', 'getInfo', $success, $code, $msg);
} else {
    // 统计开始
    StatisticClient::tick("User", 'addInfo');
// 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
// 假如有个User::getInfo方法要监控
    $user_info = User::addInfo();
    if(!$user_info){
        // 标记失败
        $success = false;
        // 获取错误码，假如getErrCode()获得
        $code = User::getErrCode();
        // 获取错误日志，假如getErrMsg()获得
        $msg = User::getErrMsg();
    }
// 上报结果
    $res = StatisticClient::report('User', 'addInfo', $success, $code, $msg);
}

$end = date('Y-m-d H:i:s', time());
echo "done over on {$end} ...\n";
var_dump($user_info,$res);