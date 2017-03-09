<?php

/**
 * 统计 服务端
 *
 */
namespace Bootstrap;

use \Config\Config;
use Config\Redis;
use Core\Log;
use Core\RedisClient;
use \Task;
use swoole_process;

class Worker {

	public static $instance;

	/**
	 * redis 资源链接
	 * @var resource
	 */
	private $redis;

	/**
	 * server实例
	 */
	private $serv;

	/**
	 *  最大日志buffer，大于这个值就写磁盘
	 * @var integer
	 */
	private $max_log_buffer_size = 1024000;

	/**
	 * 多长时间写一次数据到磁盘 (ms)
	 * @var integer
	 */
	private $write_period_length = 60000;

	/**
	 * 多长时间清理一次老的磁盘数据
	 * @var integer
	 */
	private $clear_period_length = 86400000;

	/**
	 * 数据多长时间过期,过期删除统计数据
	 * @var integer
	 */
	private $expired_time = 31536000;	//86400*365 一年

	/**
	 * 日志的redis buffer key
	 * @var string
	 */
	protected $logBufferKey = 'logBuffer';

	/**
	 * 存放统计数据的目录
	 * @var string
	 */
	protected $statisticDir = 'statistic/statistic/';

	/**
	 * 存放统计日志的目录
	 * @var string
	 */
	protected $logDir = 'statistic/log/';

    /**
     * 存放统计日志的目录
     * @var string
     */
    protected $businessDir = 'statistic/business/';
    const STATS_BUSINESS_KEY = "statistic:business";

	/**
	 * master pid path
	 * @var string
	 */
	protected $masterPidPath = '/pid/master.pid';

	/**
	 * @var string 统计数据key
	 */
	public static $order = "statistics:order";

	/**
	 * redis统计数据 key
	 * @var string
	 */
	protected $statisticDataKey = 'statisticData';
	protected $handleWorkerPort = 55656;
	protected $handleProviderPort = 55858;
	protected $udpFinderport = 55859;
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

	protected  $childProcess ;

	public function __construct()
	{
		$initData = \Config\Worker::getInitData();
		foreach ($initData as $key=>$val) {
			$this->$key = $val;
		}

		if (isset(\Config\Worker::$masterPidPath)) {
			$this->masterPidPath = \Config\Worker::$masterPidPath;
		}

		if (isset(\Config\Config::$ProviderPort)) {
			$this->handleProviderPort = \Config\Config::$ProviderPort;
		}

		if (isset(\Config\Config::$findProviderPort)) {
			$this->udpFinderport = \Config\Config::$findProviderPort;
		}
	}

	public function run($ip="0.0.0.0", $port= 55656, $mode = SWOOLE_PROCESS, $type=SWOOLE_SOCK_UDP)
	{
		if (empty($port)) {
			$port = $this->handleWorkerPort;
		} else {
			$this->handleWorkerPort = $port;
		}
		$serv = new \swoole_server($ip, $port, $mode, $type);	//处理客户端发送的数据
        $serv->addlistener('0.0.0.0', $this->handleProviderPort, SWOOLE_SOCK_UDP); //处理统计页面请求的数据
        $serv->addlistener('0.0.0.0', $this->handleProviderPort, SWOOLE_SOCK_TCP); //处理统计页面请求的数据
		$serv->addlistener('0.0.0.0', $this->udpFinderport, SWOOLE_SOCK_UDP); //recv udp broadcast
		$serv->config = \Config\Server::getServerConfig();
		$serv->set($serv->config);
		$serv->on('Start', array($this, 'onStart'));
		$serv->on('WorkerStart', array($this, 'onWorkerStart'));
		$serv->on('Connect', array($this, 'onConnect'));
		$serv->on('Receive', array($this, 'onReceive'));
		$serv->on('Task', array($this, 'onTask'));
		$serv->on('Finish', array($this, 'onFinish'));
		$serv->on('WorkerError', array($this, 'onWorkerError'));
		$serv->on('Close', array($this, 'onClose'));
		$serv->on('WorkerStop', array($this, 'onWorkerStop'));
		$serv->on('Shutdown', array($this, 'onShutdown'));
		$serv->on('ManagerStart', function ($serv) {
			global $argv;
			swoole_set_process_name("php {$argv[0]}: manager");
		});
		$serv->start();
	}

	/**
	 * swoole_start 回调函数
	 * @param \swoole_server $serv
	 */
	public function onStart(\swoole_server $serv)
	{
		//设置主进程名称
		global $argv;
		swoole_set_process_name("php {$argv[0]}: statistics_master");

		//保存进程master_pid文件比较好操作
		file_put_contents(BASEDIR.$this->masterPidPath, $serv->master_pid);

		echo "\033[1A\n\033[K-----------------------\033[47;30m SWOOLE \033[0m-----------------------------\n\033[0m";
		echo 'swoole version:' . swoole_version() . "          PHP version:".PHP_VERSION."\n";
		echo "------------------------\033[47;30m WORKERS \033[0m---------------------------\n";
		echo "\033[47;30mMasterPid\033[0m", str_pad('', self::$_maxMasterPidLength + 2 - strlen('MasterPid')), "\033[47;30mManagerPid\033[0m", str_pad('', self::$_maxManagerPidLength + 2 - strlen('ManagerPid')), "\033[47;30mWorkerId\033[0m", str_pad('', self::$_maxWorkerIdLength + 2 - strlen('WorkerId')),  "\033[47;30mWorkerPid\033[0m\n";
	}

	/**
	 * 返回redis链接资源
	 * @return unknown
	 */
	function getRedis()
	{
		if (empty($this->redis) || !$this->redis->info()) {
            $redisConfig = \Config\Redis::getConfig();
            $redisClient = new RedisClient($redisConfig);
			$this->redis = $redisClient->connection();
			if (empty($this->redis)) {
				echo "connect Redis failed!\n";
			}
		}
		return $this->redis;
	}

	/**
	 * 日志
	 * @param unknown $msg
	 */
	public function log($msg)
	{
		echo "#" . $msg . PHP_EOL;
	}

	/**
	 * 解包
	 * @param unknown $buffer
	 * @return mixed
	 */
	public static function decode($buffer)
	{
		$length = unpack('N', $buffer)[1];
		$string = substr($buffer, -$length);
		$data = json_decode($string, true);
		return $data;
	}

	/**
	 * 进程启动
	 * @param unknown $serv
	 * @param unknown $worker_id
	 */
	public function onWorkerStart($serv, $worker_id)
	{
		$this->processRename($serv, $worker_id);
		// 初始化目录
		umask(0);
		$statistic_dir = Config::$dataPath . $this->statisticDir;
		if(!is_dir($statistic_dir)) {
			mkdir($statistic_dir, 0777, true);
		}
		$log_dir = Config::$dataPath . $this->logDir;
		if(!is_dir($log_dir)) {
			mkdir($log_dir, 0777, true);
		}

		$this->process();	// 子进程
	}

	/**
	 * 修改进程名
	 * @param unknown $serv
	 * @param unknown $worker_id
	 */
	public function processRename($serv, $worker_id)
	{
		global $argv;
		$worker_num = isset($serv->setting['worker_num']) ? $serv->setting['worker_num'] : 1;
		$task_worker_num = isset($serv->setting['task_worker_num']) ? $serv->setting['task_worker_num'] : 0;

		if ($worker_id >= $worker_num) {
			swoole_set_process_name("php {$argv[0]}: task");
			// worker进程不能调用定时器
		} else {
			swoole_set_process_name("php {$argv[0]}: worker");
			// 定时保存统计数据
			if ($worker_id==0) {
				$that = &$this;
				$serv->tick($this->write_period_length, function($id) use($that) {
// 					echo 'tick one'.PHP_EOL;
					$that->writeStatisticsToDisk();
					$that->writeLogToDisk();
					$that->writeOrderToDisk();
                    $that->writeBusinessToDisk();
				});

				$datapath = Config::$dataPath;
				$expireTime = $this->expired_time;
				$serv->tick($this->clear_period_length, function($id) use($datapath, $expireTime, $that) {
// 					echo 'tick two'.PHP_EOL;
					$that->clearDisk($datapath . $this->statisticDir, $expireTime);
					$that->clearDisk($datapath . $this->logDir, $expireTime);
				});

				// 耗时检测
				$serv->tick(Config::$checkSucTimer*1000, function() use($that, $serv) {
					$serv->task(['CheckTime', 'AllData']);
				});
			}
		}
		usleep($worker_id);//保证顺序输出格式
		echo str_pad($serv->master_pid, self::$_maxMasterPidLength+2),str_pad($serv->manager_pid, self::$_maxManagerPidLength+2),str_pad($serv->worker_id, self::$_maxWorkerIdLength+2), str_pad($serv->worker_pid, self::$_maxWorkerIdLength), "\n";;
	}

	/**
	 * 建立链接
	 * @param \swoole_server $serv
	 * @param unknown $fd
	 * @param unknown $from_id
	 */
	public function onConnect(\swoole_server $serv, $fd, $from_id)
	{
		echo "Worker#{$serv->worker_pid} Client[$fd@$from_id]: Connect.\n";
	}

	/**
	 * 接收数据
	 * @param \swoole_server $serv
	 * @param unknown $fd
	 * @param unknown $from_id
	 * @param unknown $data
	 */
	public function onReceive(\swoole_server $serv, $fd, $from_id, $data)
	{
		$this->childProcess->push($data);
		$data = self::decode($data);
        file_put_contents("/tmp/stats.txt", json_encode($data) . "\r\n", FILE_APPEND);
		$connInfo = $serv->connection_info($fd, $from_id);
		if ($connInfo['server_port'] == $this->handleWorkerPort) {
			$module = $data['module'];
			$interface = $data['interface'];
			$cost_time = $data['cost_time'];
			$success = $data['success'];
			$time = $data['time'];
			$code = $data['code'];
			$msg = str_replace("\n", "<br>", $data['msg']);
			$ip = $serv->connection_info($fd)['remote_ip'];
            $keywords = !empty($data['keywords']) ? $data['keywords'] : [];
			// 模块接口统计
			$this->collectStatistics($module, $interface, $cost_time, $success, $ip, $code, $msg);
			// 全局统计
			$this->collectStatistics('AllData', 'Statistics', $cost_time, $success, $ip, $code, $msg);
			// 统计数据
			$this->collectOrder($module, $interface, $cost_time, $success, $ip);
            // 业务统计
            $this->collectKeywords($keywords);
			// 失败记录日志
			$redis = $this->getRedis();
			if (! $success) {
				$logBuffer = $redis->get($this->logBufferKey);
				$logBuffer .= date('Y-m-d H:i:s', $time) . "\t$ip\t$module::$interface\tcode:$code\tmsg:$msg\n";
				$redis->set($this->logBufferKey, $logBuffer);
				if (strlen($logBuffer) >= $this->max_log_buffer_size) {
					$this->writeLogToDisk();
				}
			}else {
				// 成功记录信息
				$seconds = Config::$checkSucTimer;
				$unix_time = ceil( time() / $seconds ) * $seconds ;
				$oldData =  $redis->hget(Redis::$success_data, $unix_time);
				$oldData = $oldData ? json_decode($oldData, true) : [
					'total_count' => 0,
					'total_time' => 0
				];
				$newData = [
					'total_count' => ++$oldData['total_count'],
					'total_time' => $oldData['total_time'] + $cost_time
				];
				$redis->hSet(Redis::$success_data, $unix_time, json_encode($newData));
				$redis->expire(Redis::$success_data, 86400);
			}
		} else if($connInfo['server_port'] == $this->handleProviderPort) {
				$provider = \Bootstrap\Provider::getInstance();
				$provider->message($serv, $fd, $from_id, $data);
		} else if($connInfo['server_port'] == $this->udpFinderport) {
				if (empty($data)) {
					return false;
				}
				// 无法解析的包
				if (empty($data['cmd']) || $data['cmd'] != 'REPORT_IP') {
					return false;
				}
				return $serv->send($fd, json_encode(array('result' => 'ok')));
		}else {
			echo '端口错误'.PHP_EOL;
		}
	}

	/**
	 * 收集统计数据
	 * @param string $module
	 * @param string $interface
	 * @param float $cost_time
	 * @param int $success
	 * @param string $ip
	 * @param int $code
	 * @param string $msg
	 * @return void
	 */
	protected function collectStatistics($module, $interface, $cost_time, $success, $ip, $code, $msg)
	{
		$redis = $this->getRedis();
		$statisticData = json_decode($redis->get($this->statisticDataKey), true);
		$statisticData = empty($statisticData) ? array() : $statisticData;
		// 统计相关信息
		if (! isset($statisticData[$ip])) {
			$statisticData[$ip] = array();
		}
		if (! isset($statisticData[$ip][$module])) {
			$statisticData[$ip][$module] = array();
		}
		if (! isset($statisticData[$ip][$module][$interface])) {
			$statisticData[$ip][$module][$interface] = array(
				'code' => array(),
				'suc_cost_time' => 0,
				'fail_cost_time' => 0,
				'suc_count' => 0,
				'fail_count' => 0
			);
		}
		if (! isset($statisticData[$ip][$module][$interface]['code'][$code])) {
			$statisticData[$ip][$module][$interface]['code'][$code] = 0;
		}
		$statisticData[$ip][$module][$interface]['code'][$code] ++;
		if ($success) {
			$statisticData[$ip][$module][$interface]['suc_cost_time'] += $cost_time;
			$statisticData[$ip][$module][$interface]['suc_count'] ++;
		} else {
			$statisticData[$ip][$module][$interface]['fail_cost_time'] += $cost_time;
			$statisticData[$ip][$module][$interface]['fail_count'] ++;
		}
		$redis->set($this->statisticDataKey, json_encode($statisticData));
	}

	/**
	 * 将统计数据写入磁盘
	 * @return void
	 */
	public function writeStatisticsToDisk()
	{
		$time = time();
		// 循环将每个ip的统计数据写入磁盘
		$redis = $this->getRedis();
		$statisticData = json_decode($redis->get($this->statisticDataKey), true);
		if (is_array($statisticData)) {
			foreach($statisticData as $ip => $mod_if_data) {
				foreach($mod_if_data as $module=>$items) {
					// 文件夹不存在则创建一个
					$file_dir = Config::$dataPath . $this->statisticDir.$module;
					if(!is_dir($file_dir)) {
						umask(0);
						mkdir($file_dir, 0777, true);
					}
					// 依次写入磁盘
					foreach($items as $interface=>$data) {
						file_put_contents($file_dir. "/{$interface}.".date('Y-m-d'), "$ip\t$time\t{$data['suc_count']}\t{$data['suc_cost_time']}\t{$data['fail_count']}\t{$data['fail_cost_time']}\t".json_encode($data['code'])."\n", FILE_APPEND | LOCK_EX);
					}
				}
			}
			// 清空统计
			$redis->set($this->statisticDataKey, '');
		}
	}

	/**
	 * 将日志数据写入磁盘
	 * @return void
	 */
	public function writeLogToDisk()
	{
		// 没有统计数据则返回
		$redis = $this->getRedis();
		$logBuffer = $redis->get($this->logBufferKey);
		if(empty($logBuffer)) {
			return;
		}
		// 写入磁盘
		file_put_contents(Config::$dataPath . $this->logDir . date('Y-m-d'), $logBuffer, FILE_APPEND | LOCK_EX);
		$redis->set($this->logBufferKey, '');
	}

	/**
	 * task任务
	 * @param \swoole_server $serv
	 * @param unknown $task_id
	 * @param unknown $from_id
	 * @param unknown $data
	 * @return void|multitype:string
	 */
	public function onTask(\swoole_server $serv, $task_id, $from_id, $data)
	{
		list ($func, $params) = $data;
		$class_name = "\\Task\\{$func}";
		$class_name::preProcess();
		$class_name::doProcess();
		$class_name::afterProcess();
	}

	/**
	 * task执行完毕调用
	 * @param \swoole_server $serv
	 * @param unknown $task_id
	 * @param unknown $data
	 */
	public function onFinish(\swoole_server $serv, $task_id, $data)
	{
		//保留回调函数,暂时不用
	}

	/**
	 * worker出现问题调用
	 * @param \swoole_server $serv
	 * @param unknown $worker_id
	 * @param unknown $worker_pid
	 * @param unknown $exit_code
	 */
	public function onWorkerError(\swoole_server $serv, $worker_id, $worker_pid, $exit_code)
	{
		echo "worker abnormal exit. WorkerId=$worker_id|Pid=$worker_pid|ExitCode=$exit_code\n";
	}

	/**
	 * 清除磁盘数据
	 * @param string $file
	 * @param int $exp_time
	 */
	public function clearDisk($file = null, $exp_time = 86400)
	{
		$time_now = time();
		//判断是否是文件
		if(is_file($file)) {
			$mtime = filemtime($file);
			if(!$mtime) {
				$this->notice("filemtime $file fail");
				return;
			}
			if($time_now - $mtime > $exp_time) {
				unlink($file);
			}
			return;
		}
		//遍历该目录下的日志文件,判断是否过期,过期删除
		foreach (glob($file."/*") as $file_name) {
			$this->clearDisk($file_name, $exp_time);
		}
	}

	/**
	 * 链接断开
	 * @param unknown $serv
	 * @param unknown $fd
	 * @param unknown $from_id
	 */
	public function onClose($serv, $fd, $from_id)
	{
		$this->log("Worker#{$serv->worker_pid} Client[$fd@$from_id]: fd=$fd is closed");
	}

	/**
	 * 关闭进程
	 * @param unknown $serv
	 * @param unknown $worker_id
	 */
	public function onWorkerStop($serv, $worker_id)
	{
		echo "WorkerStop[$worker_id]|pid=" . $serv->worker_pid . ".\n";
	}

	/**
	 * 关闭服务器
	 * @param unknown $serv
	 */
	public function onShutdown($serv)
	{
		echo "Server: onShutdown\n";
	}

	/**
	 * 记录统计信息
	 *
	 * @param $module
	 * @param $interface
	 * @param $cost_time
	 * @param $success
	 * @param $ip
	 */
	public function collectOrder($module, $interface, $cost_time, $success, $ip) {
		$redis = $this->getRedis();
		$date = date('Y-m-d');
		$data = json_decode($redis->hget(self::$order, $date), true);

		$key = $interface .'#'. $module;
		$old =  (is_array($data) && isset($data[$key])) ? $data[$key] : [0,0,0,0]; // success_count, failed_count, success_time, failed_time
		list($success_count, $failed_count, $success_time, $failed_time) = $old;

		$row = [
			$success ? ++$success_count : $success_count,
			$success ? $failed_count : ++$failed_count,
			$success ? $success_time + $cost_time : $success_time,
			$success ? $failed_time : $failed_time + $cost_time
		];

		$data[$key] = $row;

		$redis->hset(self::$order, $date, json_encode($data));
	}

	/**
	 * 将统计数据写入硬盘
	 */
	public function writeOrderToDisk() {
		$date = date('Y-m-d', strtotime("-1 day"));	// 只写入昨天的数据
		// 循环将每个ip的统计数据写入磁盘
		$redis = $this->getRedis();
		$mod_if_data = $redis->hget(self::$order, $date);
		if (!empty($mod_if_data)) {
			$allData = json_decode($mod_if_data, true);
			foreach($allData as $key=>$items) {
				list($interface, $module) = explode("#", $key);
				// 文件夹不存在则创建一个
				$file_dir = Config::$dataPath . Config::$orderDir;
				if(!is_dir($file_dir)) {
					umask(0);
					mkdir($file_dir, 0777, true);
				}
				// 依次写入磁盘,一次性将redis数据写如前一天的文件中
				list($success_count, $failed_count, $success_time, $failed_time) = $items;
				file_put_contents($file_dir.$date, "$interface\t$module\t$success_count\t$failed_count\t$success_time\t$failed_time\n", FILE_APPEND | LOCK_EX);
			}
			// 清空统计
			$redis->hset(self::$order, $date, "");
		}
	}

	// 监控耗时
//	public function checkTime ($module='AllData', $interface = 'Statistics') {
//
//
//	}

    /**
     * 业务统计
     * @param $keywords
     */
    public function collectKeywords(array $keywords)
    {
        if (!$keywords) return;

        $params = $keywords['event'];
        $result = $keywords['result'];
        $eventKey = $params['_event_key'];
        $redis = $this->getRedis();
        $date = date('Y-m-d');
        $time = time();
        switch ($eventKey) {
            case 100320003: // 申请提现事件
                $success = $result['success'] == true; // 提现成功或失败
                $money = $params['money']; // 提现金额
                $data = json_decode($redis->hget(self::STATS_BUSINESS_KEY, $date), true);

                $key = 'withdraw';
                $old =  (is_array($data) && isset($data[$key])) ? $data[$key] : [$time, 0,0,0,0]; // success_count, failed_count, success_money, failed_money
                list($time, $success_count, $failed_count, $success_money, $failed_money) = $old;

                $row = [
                    $time,
                    $success ? ++$success_count : $success_count,
                    $success ? $failed_count : ++$failed_count,
                    $success ? $success_money + $money : $success_money,
                    $success ? $failed_money : $failed_money + $money
                ];

                $data[$key] = $row;
                $redis->hset(self::STATS_BUSINESS_KEY, $date, json_encode($data));
                break;
            case 100100002: // 注册事件
                $success = $result['success'] == true; // 注册成功或失败
                $data = json_decode($redis->hget(self::STATS_BUSINESS_KEY, $date), true);

                $key = 'register';
                $old =  (is_array($data) && isset($data[$key])) ? $data[$key] : [$time, 0,0,0,0]; // success_count, failed_count
                list($time, $success_count, $failed_count) = $old;

                $row = [
                    $time,
                    $success ? ++$success_count : $success_count,
                    $success ? $failed_count : ++$failed_count
                ];

                $data[$key] = $row;
                $redis->hset(self::STATS_BUSINESS_KEY, $date, json_encode($data));
                break;
            case 100310002: // 充值请求成功事件
                $success = $result['errcode'] == 1000; // 充值成功或失败
                if (!$success || !isset($result['result']) || ($result['result']['status'] != 103)) break;

                list($uid, $user, $cardno, $orderid, $payConfirmValidatecode,
                    $money, $requestid, $bindConfirmValidatecode, $user_ip) = $params['params'];

                $data = json_decode($redis->hget(self::STATS_BUSINESS_KEY, $date), true);

                $key = 'recharge';
                $old =  (is_array($data) && isset($data[$key])) ? $data[$key] : [$time, 0, 0]; // success_count, success_money
                list($time, $success_count, $success_money) = $old;

                $row = [
                    $time,
                    ++$success_count,
                    $success_money + $money
                ];

                $data[$key] = $row;
                $redis->hset(self::STATS_BUSINESS_KEY, $date, json_encode($data));
                break;
            case 100310004: // 充值成功回调事件
                $success = $result['success'] == true; // 充值成功或失败
                $money = ($success && isset($result['result']['money'])) ? $result['result']['money'] : 0; // 充值金额
                if (!$success || ($money <= 0)) break;

                $data = json_decode($redis->hget(self::STATS_BUSINESS_KEY, $date), true);

                $key = 'rechargeCallback';
                $old =  (is_array($data) && isset($data[$key])) ? $data[$key] : [$time, 0, 0]; // success_count, success_money
                list($time, $success_count, $success_money) = $old;

                $row = [
                    $time,
                    ++$success_count,
                    $success_money + $money
                ];

                $data[$key] = $row;
                $redis->hset(self::STATS_BUSINESS_KEY, $date, json_encode($data));
                break;
            default:
                break;
        }

        return;
    }

    /**
     * 业务统计定时写入文件存储
     * @param $keywords
     */
    public function writeBusinessToDisk()
    {
       // $date = date('Y-m-d', strtotime("-1 day"));	// 只写入昨天的数据
        $date = date('Y-m-d');
        // 循环将每个ip的统计数据写入磁盘
        $redis = $this->getRedis();
        $mod_if_data = $redis->hget(self::STATS_BUSINESS_KEY, $date);
        if (!empty($mod_if_data)) {
            $allData = json_decode($mod_if_data, true);
            foreach($allData as $key=>$items) {
                // 文件夹不存在则创建一个
                $file_dir = Config::$dataPath . $this->businessDir . $key;
                if(!is_dir($file_dir)) {
                    umask(0);
                    mkdir($file_dir, 0777, true);
                }
                // 依次写入磁盘,一次性将redis数据写如前一天的文件中
                file_put_contents($file_dir.'/'.$date, implode("\t", $items) . "\n", FILE_APPEND | LOCK_EX);
            }
            // 清空统计
            $redis->hset(self::STATS_BUSINESS_KEY, $date, "");
        }
    }

	protected function process () {
		$process = new swoole_process([$this, 'process_start']);
		$res = $process->useQueue();
		if (!$res) {
			Log::getInstance()->error(0 , 'process_start_error' , ["子进程启动失败" , swoole_strerror(swoole_errno())]);
			return false;
		}
		$pid = $process->start();
		$this->childProcess = $process;
	}

	public function process_start (swoole_process $process) {
		echo "childProcess : PID " . $process->pid . " Start" . PHP_EOL;
		while ($data = $process->pop()) {
			$data = self::decode($data);
			// ...
		}
	}
}