<?php
/**
 * Created by PhpStorm.
 * User: jw
 * Date: 16-4-18
 * Time: 下午5:45
 */
namespace Task;

use Config\Config;
use Config\Redis;
use Core\Log;

Class CheckTime extends BaseTask
{
    protected static $time_out = 100; // 单位:毫秒 , test: 0.0001

    public static function preProcess (){}

    public static  function doProcess ($module='AllData', $interface = 'Statistics') {
        $seconds = Config::$checkSucTimer;
        $key = floor( time() / $seconds ) * $seconds ;
        $timer = date('Y-m-d', $key);
        $redis = self::getRedis();
        $data = $redis->hget(Redis::$success_data, $key);
        if (!$data) return false;

        $data = json_decode($data, true);
        $suc_avg_time = $data['total_time']*1000 / $data['total_count'];

        if ($suc_avg_time > self::$time_out) { // 成功平均时间大于100ms && 报警
            Log::getInstance()->error(0, "statistics_time_out", "[{$timer}] 接口成功调用的平均时间超过" . self::$time_out . "毫秒");
        }
    }

    public static function afterProcess() {}
}