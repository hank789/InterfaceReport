<?php
/**
 * redis 配置文件
 *
 */

namespace Config;
class Redis
{
    public static $success_data = "statistics_success_data";
    public static function getConfig() {
    	$config = array(
    		'host' => '127.0.0.1',
    		'port' => '6379',
    	);
    	return $config;
    }
}

