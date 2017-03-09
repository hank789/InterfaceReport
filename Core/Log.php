<?php namespace Core;
use Config\Config;

/**
 * User: wanghui
 * Date: 16/1/7
 * Time: 下午8:11
 */

class Log
{
    public static $logId = '';

    public static function getInstance () {
        static $instance;
        if(!isset($instance)){
            $instance = new self();
        }
        return $instance;
    }

    public function log($uid, $level, $type, $message)
    {
        $log_arr = array(
            'url' => 'cli',
            'logId' => self::$logId,
            'date' => date('Y-m-d H:i:s'),
            'userId' => $uid,
            'type' => $type,
            'info' => $message,
            'host' => php_uname('n'),
            'called_trace' => $this->get_caller_info()
        );
        if ($monitor_config = Config::$logMq) {
            try{
                $pushClient = Amqp::instance($monitor_config);
                $pushClient->send(array($level, $type, $uid, $log_arr));
            }catch (\Exception $e){
                $logpath=strtoupper(substr(PHP_OS,0,3))==='WIN'?"./log":"/tmp";
                file_put_contents($logpath.'/hopp-log-' . date('Y-m-d', time()), $this->LocalFileFormat($level, $uid, $log_arr), FILE_APPEND);
            }
        } else {
            $logpath=strtoupper(substr(PHP_OS,0,3))==='WIN'?"./log":"/tmp";
            file_put_contents($logpath.'/hopp-log-' . date('Y-m-d', time()), $this->LocalFileFormat($level, $uid, $log_arr), FILE_APPEND);
        }
    }

    private function LocalFileFormat($level, $message, array $context = array())
    {
        $vars = array(
            'message' => (string)$message,
            'context' => $context,
            'level' => $level,
            'level_name' => $level,
            'channel' => 'main',
            'datetime' => date('Y-m-d H:i:s'),
            'extra' => array(),
        );
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.' . $var . '%')) {
                $output = str_replace('%extra.' . $var . '%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }
        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%' . $var . '%')) {
                $output = str_replace('%' . $var . '%', $this->stringify($val), $output);
            }
        }
        return $output;
    }

    public function stringify($value)
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }
        if (is_scalar($data)) {
            return (string)$data;
        }
        return str_replace('\\/', '/', @json_encode($data,JSON_UNESCAPED_UNICODE));
    }

    protected function replaceNewlines($str)
    {
        return str_replace(array("\r\n", "\r", "\n"), ' ', $str);
    }

    public function get_caller_info()
    {
        $c = '';
        $file = '';
        $func = '';
        $class = '';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($trace[2])) {
            $file = $trace[1]['file'];
            $func = $trace[2]['function'];
            if ((substr($func, 0, 7) == 'include') || (substr($func, 0, 7) == 'require')) {
                $func = '';
            }
        } else if (isset($trace[1])) {
            $file = $trace[1]['file'];
            $func = '';
        }
        if (isset($trace[3]['class'])) {
            $class = $trace[3]['class'];
            $func = $trace[3]['function'];
            $file = $trace[2]['file'];
        } else if (isset($trace[2]['class'])) {
            $class = $trace[2]['class'];
            $func = $trace[2]['function'];
            $file = $trace[1]['file'];
        }
        if ($file != '') $file = basename($file);
        $c = $file . ": ";
        $c .= ($class != '') ? ":" . $class . "->" : "";
        $c .= ($func != '') ? $func . "(): " : "";
        return ($c);
    }

    public function emergency($uid, $type, $message)
    {
        $this->log($uid, 'emergency', $type, $message);
    }

    public function alert($uid, $type, $message)
    {
        $this->log($uid, 'alert', $type, $message);
    }

    public function critical($uid, $type, $message)
    {
        $this->log($uid, 'critical', $type, $message);
    }

    public function error($uid, $type, $message)
    {
        $this->log($uid, 'error', $type, $message);
    }

    public function warning($uid, $type, $message)
    {
        $this->log($uid, 'warning', $type, $message);
    }

    public function notice($uid, $type, $message)
    {
        $this->log($uid, 'notice', $type, $message);
    }

    public function info($uid, $type, $message)
    {
        $this->log($uid, 'info', $type, $message);
    }

    public function debug($uid, $type, $message)
    {
        $this->log($uid, 'debug', $type, $message);
    }

    public function money ($uid, $type, $message)
    {
        $this->log($uid, 'notice', $type, $message);
    }
}