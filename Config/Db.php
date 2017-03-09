<?php namespace Config;
/**
 * @author: wanghui
 * @date: 16/5/31 下午8:29
 * @email: wanghui@yonglibao.com
 */

class Db {
     public static function getMysqlConfig(){
         return [
             'read_db' => [
                 'host' => 'localhost',
                 'user' => 'root',
                 'password' => '123456',
                 'dbname' => 'new_yonglibao_c'
             ],
             'hlc_db' => [
                 'host' => 'localhost',
                 'user' => 'root',
                 'password' => '123456',
                 'dbname' => 'new_yonglibao_c'
             ]
         ];
     }

    public static function getMongoConfig(){
        return [
            'default' => [
                'host' => 'localhost',
                'port' => '27017',
                'dbname' => 'seal'
            ]
        ];
    }
}

