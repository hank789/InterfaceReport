<?php

/**
 * config配置文件
 *
 */
namespace Config;

class Config
{
    // 数据源端口，会向这个端口发送udp广播获取ip，然后从这个端口以tcp协议获取统计信息
    public static $ProviderPort = 55857;
    public static $findProviderPort = 55859;

    public static $dataPath = '';

    public static $adminName = 'admin'; // 管理员用户名，用户名密码都为空字符串时说明不用验证
    public static $adminPassword = 'admin'; // 管理员密码，用户名密码都为空字符串时说明不用验证

    /**
     * 存放统计数据的目录
     * @var string
     */
    public static $statisticDir = 'statistic/statistic/';

    /**
     * 存放统计日志的目录
     * @var string
     */
    public static $logDir = 'statistic/log/';

    /**
     * 日志的redis buffer key
     * @var string
     */
    public static $logBufferKey = 'logBuffer';

    /**
     *  最大日志buffer，大于这个值就写磁盘
     * @var integer
     */
    public static $max_log_buffer_size = 1024000;

    /**
     * 多长时间写一次数据到磁盘 (ms)
     * @var integer
     */
    public static $write_period_length = 60000;

    /**
     * 数据多长时间过期,过期删除统计数据
     * @var integer
     */
    public static $expired_time = 31536000;	//86400*365 一年

    /**
     * 多长时间清理一次老的磁盘数据
     * @var integer
     */
    public static $clear_period_length = 86400000;

    public static $mainRefreshSeconds = [300, 5, 10, 20, 30];    // 概述首页自动刷新时间选项,单位秒

    public static $checkSucTimer = 300; // 单位秒 , 5分钟检测一次成功耗时

    public static $logMq = [
        /*'queue' => [
            'host' => '10.144.50.78',
            'port' => '5672',
            'login' => 'monitor',
            'password' => 'qazwsx1234',
            'vhost' => '/',
        ],
        'channel_name' => 'monitor_hopp_log_worker'*/
    ];

    public static $test = 'test';
}

Config::$dataPath = __DIR__ . '/../data/';