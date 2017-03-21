<?php

/**
 * 统计server与web server配置
 *
 */
namespace Config;

class Server
{

	/**
	 * 获取server配置
	 * @return multitype:number boolean string
	 */
	public static function getServerConfig()
	{
		$config = array(
			'worker_num' => 128,
			// 协议
			'open_length_check' => true,
			'package_length_type' => 'N',
			'package_length_offset' => 0,
			'package_body_start' => 4,
			'package_max_length' => 8192,
			'task_ipc_mode' => 2,
			'task_worker_num' => 2,
			'user' => 'xmc',
			'group' => 'xmc',
			'log_file' => Config::$dataPath.'server.log',
			'heartbeat_check_interval' => 30,
			'heartbeat_idle_time' => 30,
			'daemonize' => true // 守护进程改成true
		);
		return $config;
	}

	/**
	 * 获取web server配置
	 * @return multitype:number string boolean
	 */
	public static function getWebServerConfig()
	{
		$config = array(
			'worker_num' => 4, // worker进程数量
			'max_request' => 4000, // 最大请求次数，当请求大于它时，将会自动重启该worker
			'dispatch_mode' => 1,
			'log_file' => Config::$dataPath.'web.log',
			'daemonize' => true, // 守护进程设置成true
		);
		return $config;
	}
}

