<?php namespace Config;
/**
 * @author: wanghui
 * @date: 16/6/8 下午4:36
 * @email: wanghui@yonglibao.com
 */

class Permission {

    public static $users = [
        'admin' => [
            'password' => 'admin',
            'role' => 1
        ],
        'ruby' => [
            'password' => '123456',
            'role' => 3
        ]
    ];

    public static $roles = [
        1 => '系统管理员',//系统管理员
        2 => '产品总监',//产品
        3 => '客服总监',//客服
        4 => '运营总监', //运营
        5 => '管理员', //主管，普通管理员
        6 => '渠道专员',//渠道专员
    ];
}