<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-12-26
 * Time: 11:53
 */
use think\facade\Env;
/*
 * 错误信息
 */
//开发环境
$dev=[
    "add"=>"开发"
];
//线上环境
$oline=[
    "add"=>"线上"
];
$debug=Env::get("app_debug");//是否开启debug模式
if($debug)return $dev;
return $oline;
$msg=Config::get('errmsg.add');
echo $msg;
die;