<?php

/*********************************
 * web server 不依赖php-fpm
 * 处理网页显示
 *
 *********************************/

namespace Bootstrap;
use \Config\Config;
use Core;

class WebServer
{
	public static $instance;
	
	/**
	 * master pid path
	 * @var string
	 */
	protected $webPidPath = '/pid/web.pid';

	/**
	 * MasterPid命令时格式化输出
	 * ManagerPid命令时格式化输出
	 * WorkerId命令时格式化输出
	 * WorkerPid命令时格式化输出
	 * @var int
	 */
	protected static $_maxMasterPidLength = 12;
	protected static $_maxManagerPidLength = 12;
	protected static $_maxWorkerIdLength = 12;
	protected static $_maxWorkerPidLength = 12;
	
	/**
	 * 初始化
	 */
	public function __construct()
	{
		if (isset(\Config\Worker::$webPidPath)) {
			$this->webPidPath = \Config\Worker::$webPidPath;
		}
		register_shutdown_function(array($this, 'handleFatal'));
	}
	
	/**
	 * server start的时候调用
	 * @param unknown $serv
	 */
	public function onStart($serv)
	{
		//设置主进程名称
		global $argv;
		swoole_set_process_name("php {$argv[0]}: statistics_web");
		
		//保存进程master_pid文件比较好操作
		file_put_contents(BASEDIR.$this->webPidPath, $serv->master_pid);
		
		echo "\033[1A\n\033[K-----------------------\033[47;30m SWOOLE \033[0m-----------------------------\n\033[0m";
		echo 'swoole version:' . swoole_version() . "          PHP version:".PHP_VERSION."\n";
		echo "------------------------\033[47;30m WORKERS \033[0m---------------------------\n";
		echo "\033[47;30mMasterPid\033[0m", str_pad('', self::$_maxMasterPidLength + 2 - strlen('MasterPid')), "\033[47;30mManagerPid\033[0m", str_pad('', self::$_maxManagerPidLength + 2 - strlen('ManagerPid')), "\033[47;30mWorkerId\033[0m", str_pad('', self::$_maxWorkerIdLength + 2 - strlen('WorkerId')),  "\033[47;30mWorkerPid\033[0m\n";
	}
	/**
	 * worker start时调用
	 * @param unknown $serv
	 * @param int $worker_id
	 */
	public function onWorkerStart($serv, $worker_id)
	{
		global $argv;
		$worker_num = isset($serv->setting['worker_num']) ? $serv->setting['worker_num'] : 1;
		$task_worker_num = isset($serv->setting['task_worker_num']) ? $serv->setting['task_worker_num'] : 0;
		
		if($worker_id >= $worker_num) {
			swoole_set_process_name("php {$argv[0]}: task");
		} else {
			swoole_set_process_name("php {$argv[0]}: worker");
		}
		usleep($worker_id);
		echo str_pad($serv->master_pid, self::$_maxMasterPidLength+2),str_pad($serv->manager_pid, self::$_maxManagerPidLength+2),str_pad($serv->worker_id, self::$_maxWorkerIdLength+2), str_pad($serv->worker_pid, self::$_maxWorkerIdLength), "\n";;
		define('APPLICATION_PATH', dirname(__DIR__));
	}
	
	/**
	 * 当request时调用
	 * @param unknown $request
	 * @param unknown $response
	 */
	public function onRequest($request, $response)
	{
// 		var_dump($request, $response);
		$_GET = $_POST = $_COOKIE = array();
		$resp = \Core\Response::getInstance($response);
		$resp->setResponse($response);
		if (isset($request->get)) {
			$_GET = $request->get;
		}
		if (isset($request->post)) {
			$_POST = $request->post;
		}
		if (isset($request->cookie)) {
			$_COOKIE = $request->cookie;
		}
		try {
			ob_start();
			include APPLICATION_PATH.'/Web/index.php';
			$result = ob_get_contents();
			ob_end_clean();
			$response->header("Content-Type", "text/html;charset=utf-8");
			$result = empty($result) ? 'No message' : $result;
			$response->end($result);
			unset($result);
		} catch (\Exception $e) {
			var_dump($e);
		}
	}
	
	/**
	 * 致命错误处理
	 */
	public function handleFatal()
	{
		$error = error_get_last();
		if (isset($error['type'])) {
			switch ($error['type']) {
				case E_ERROR : 
					$severity = 'ERROR:Fatal run-time errors. Errors that can not be recovered from. Execution of the script is halted';
					break;
				case E_PARSE : 
					$severity = 'PARSE:Compile-time parse errors. Parse errors should only be generated by the parser';
					break;
				case E_DEPRECATED: 
					$severity = 'DEPRECATED:Run-time notices. Enable this to receive warnings about code that will not work in future versions';
					break;
				case E_CORE_ERROR : 
					$severity = 'CORE_ERROR :Fatal errors at PHP startup. This is like an E_ERROR in the PHP core';
					break;
				case E_COMPILE_ERROR : 
					$severity = 'COMPILE ERROR:Fatal compile-time errors. This is like an E_ERROR generated by the Zend Scripting Engine';
					break;
				default: 
					$severity = 'OTHER ERROR';
					break;
			}
			$message = $error['message'];
			$file = $error['file'];
			$line = $error['line'];
			$log = "$message ($file:$line)\nStack trace:\n";
			$trace = debug_backtrace();
			foreach ($trace as $i => $t) {
				if (!isset($t['file'])) {
					$t['file'] = 'unknown';
				}
				if (!isset($t['line'])) {
					$t['line'] = 0;
				}
				if (!isset($t['function'])) {
					$t['function'] = 'unknown';
				}
				$log .= "#$i {$t['file']}({$t['line']}): ";
				if (isset($t['object']) && is_object($t['object'])) {
					$log .= get_class($t['object']) . '->';
				}
				$log .= "{$t['function']}()\n";
			}
			if (isset($_SERVER['REQUEST_URI'])) {
				$log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
			}
			file_put_contents(ST_ROOT.'/data/web_error.log', $log);
		}
	}
	
	public function run($ip="0.0.0.0", $port=6666)
	{
		$webServer = new \swoole_http_server($ip, $port);
		$webServer->set (\Config\Server::getWebServerConfig());
		$webServer->on('WorkerStart', array($this, 'onWorkerStart'));
		$webServer->on('request', array($this, 'onRequest'));
		$webServer->on('start', array($this, 'onStart'));
		$webServer->on('ManagerStart', function ($serv) {
			global $argv;
			swoole_set_process_name("php {$argv[0]}: manager");
		});
		$webServer->start();
	}
}