<?php
/**
 * Created by PhpStorm.
 * User: jw
 * Date: 16-4-18
 * Time: 下午5:53
 */
namespace Task;

use Bootstrap\Worker;
use Core\RedisClient;

require_once __DIR__ . "/../Lib/functions.php";
require_once __DIR__ . "/../Lib/Cache.php";
require_once __DIR__ . "/../Modules/Performance/main.php";

Class BaseTask
{
    public static function preProcess(){}
    public static function doProcess(){}
    public static function afterProcess(){}

    protected static function getRedis() {
        $redisConfig = \Config\Redis::getConfig();
        $redisClient = new RedisClient($redisConfig);
        return $redisClient->connection();
    }
}