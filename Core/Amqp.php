<?php namespace Core;
/**
 * User: wanghui
 * Date: 16/1/7
 * Time: 下午11:57
 */
use AMQPConnection;
use AMQPChannel;
use AMQPExchange;
use AMQPQueue;

class Amqp {

    /**
     * @var AMQPConnection
     */
    protected $conn = '';

    /**
     * @var AMQPChannel
     */
    protected $ch = '';

    protected $ex = '';

    protected $q = '';

    protected $exchangeType = array('direct', 'fanout', 'topic');

    protected $count= 0;

    public function __construct($queue_id, $exchange_id, AMQPConnection $connection)
    {
        $this->conn = $connection;
        $this->conn->connect();

        $this->ch = new AMQPChannel($this->conn);

        //创建exchange
        $this->ex = new AMQPExchange($this->ch);
        $this->ex->setName($exchange_id);//创建名字
        $this->ex->setType(AMQP_EX_TYPE_DIRECT);
        $this->ex->setFlags(AMQP_DURABLE);
        $this->ex->declareExchange();

        //创建队列
        $this->q = new AMQPQueue($this->ch);
        //设置队列名字 如果不存在则添加
        $this->q->setName($queue_id);
        $this->q->setFlags(AMQP_DURABLE);
        $this->q->declareQueue();
        $this->q->bind($exchange_id,'');
    }

    public static function instance($config){
        static $instance;
        if (!isset($instance)) {
            $instance = new self('queue_'.$config['channel_name'],'exchange_'.$config['channel_name'],new AMQPConnection($config['queue']));
        }
        return $instance;
    }


    public function send($msg, $routing_key = '')
    {
        $this->ex->publish(json_encode($msg), $routing_key);
    }

    public function consume(){
        $time_start = microtime_float();
        $this->q->consume(function($envelope, $queue) use ($time_start) {
            ++$this->count;
            $event = json_decode($envelope->getBody(),true);
        },AMQP_AUTOACK);
    }
}