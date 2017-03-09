<?php namespace Core;

use Redis;
/**
 * @author: wanghui
 * @date: 16/5/27 下午5:26
 * @email: wanghui@yonglibao.com
 */

class RedisClient
{

    /**
     * @var Redis
     */
    protected $client;

    public function __construct(array $config = array())
    {
        $this->client = $this->openConnection($config);
    }

    public static function getInstance(array $servers = array()){
        static $instance;
        if(!isset($instance)){
            $instance = new self($servers);
        }
        if(!$instance->checkConnection()){
            $instance = new self($servers);
        }
        return $instance;
    }

    public function connection()
    {
        return $this->client;
    }

    public function checkConnection(){
        return $this->client->info();
    }

    public function command($method, array $parameters = array())
    {
        return call_user_func_array(array($this->client, $method), $parameters);
    }

    public function __call($method, $parameters)
    {
        return $this->command($method, $parameters);
    }

    private function openConnection($server)
    {
        $redis = new Redis();
        $redis->connect($server['host'], $server['port'], 5);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        if (!empty($server['password'])) {
            $redis->auth($server['password']);
        }
        if(!empty($server['database'])){
            $redis->select($server['database']);
        }
        return $redis;
    }
}