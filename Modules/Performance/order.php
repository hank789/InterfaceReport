<?php
/**
 * Created by PhpStorm.
 * User: jw
 * Date: 16-3-15
 * Time: 下午3:41
 */
namespace Statistics\Modules\Performance;

use \Config\Config;
use Bootstrap\Worker;
use Core\RedisClient;

// 新方式: 从自己记录的数据源里读
function order ($module, $interface, $date, $start_time, $offset, $count) {
    $err_msg = $notice_msg = $date_btn_str = $table_data = '';

    // date btn
    $date_btn_str = '';
    for ($i = 13; $i >= 1; $i --) {
        $the_time = strtotime("-$i day");
        $the_date = date('Y-m-d', $the_time);
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?md=performance&fn=order&date=' . "$the_date" .  '" class="btn"  type="button">' . $html_the_date . '</a>';
        if ($i == 7) {
            $date_btn_str .= '</br>';
        }
    }
    $the_date = date('Y-m-d');
    $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
    $date_btn_str .= '<a href="/?md=performance&fn=order&date=' . "$the_date" .  '"  class="btn" type="button">' . $html_the_date . '</a>';

    // table data
    $data = getOrders($date);
    foreach($data as $row) {
        list($interface, $module, $success_count, $failed_count, $success_time, $failed_time) = explode("\t", $row);
        // 重新计算: 平均耗时,成功率,失败率
        $total_count = $success_count + $failed_count;
        $total_time = $success_time + $failed_time;
        $total_avg_time =  $total_count == 0 ? 0 : round($total_time / $total_count, 9) * 1000;
        $success_avg_time = $success_count == 0 ? 0 : round($success_time / $success_count, 9) * 1000;
        $failed_avg_time = $failed_count == 0 ? 0 : round($failed_time / $failed_count, 9) * 1000;
        $precent =    ($success_count + $failed_count) == 0 ? 0 : round(($success_count * 100 / ($success_count + $failed_count)), 4);

        $html_class = $total_avg_time > 3000 ? 'class="danger"' : '';
        $table_data .= "<tr $html_class>";
        $table_data .= "<td>{$interface}</td>";
        $table_data .= "<td>{$module}</td>";
        $table_data .= "<td>{$total_count}</td>";
        $table_data .= "<td>{$total_avg_time}</td>";
        $table_data .= "<td>{$success_count}</td>";
        $table_data .= "<td>{$success_avg_time}</td>";
        $table_data .= "<td>{$failed_count}</td>";
        $table_data .= "<td>{$failed_avg_time}</td>";
        $table_data .= "<td>{$precent}%</td>";
        $table_data .= "</tr>";
    }

    include ST_ROOT . '/Views/Includes/header.tpl.php';
    include ST_ROOT . '/Views/Performance/order.tpl.php';
    include ST_ROOT . '/Views/Includes/footer.tpl.php';
}


// 旧方式: 整理原有数据
function _order($module, $interface, $date, $start_time, $offset, $count)
{
    $err_msg = $notice_msg = $date_btn_str = $table_data = '';

    // date btn
    $date_btn_str = '';
    for ($i = 13; $i >= 1; $i --) {
        $the_time = strtotime("-$i day");
        $the_date = date('Y-m-d', $the_time);
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?md=performance&fn=order&date=' . "$the_date" .  '" class="btn"  type="button">' . $html_the_date . '</a>';
        if ($i == 7) {
            $date_btn_str .= '</br>';
        }
    }
    $the_date = date('Y-m-d');
    $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
    $date_btn_str .= '<a href="/?md=performance&fn=order&date=' . "$the_date" .  '"  class="btn" type="button">' . $html_the_date . '</a>';

    // table_data

    // 拉取全部接口信息到缓存
    multiRequestStAndModules($module, $interface, $date);   // 缓存所有模块
    foreach (\Statistics\Lib\Cache::$modulesDataCache as $m => $row) {  // 缓存所有接口
        multiRequestStAndModules($m, $row, $date);
    }



    // 遍历接口
    foreach ( \Statistics\Lib\Cache::$modulesDataCache as $m => $row) {
        if ($m == 'AllData' || empty($row)) continue;

        foreach ($row as $action) {
            multiRequestStAndModules($m, $action, $date);   // 缓存接口统计信息

            $all_st_str = '';
            if (is_array(\Statistics\Lib\Cache::$statisticDataCache['statistic'])) {
                foreach (\Statistics\Lib\Cache::$statisticDataCache['statistic'] as $ip => $st_str) {
                    $all_st_str .= $st_str;
                }
            }
            $code_map = array();
            $data = formatSt($all_st_str, $date, $code_map);    // 接口数据
            $store = array(
                'total_count' => 0, 'suc_count' => 0,  'fail_count' => 0,
                'fail_avg_time' => 0,  'suc_cost_time' => 0,  'fail_cost_time' => 0
            );
            foreach($data as $row) {
                $store['total_count'] += $row['total_count'];
                $store['suc_count'] += $row['suc_count'];
                $store['fail_count'] += $row['fail_count'];
                $store['fail_avg_time'] += $row['fail_avg_time'];
                $store['suc_cost_time'] += $row['suc_cost_time'];
                $store['fail_cost_time'] += $row['fail_cost_time'];
            }
            // 重新计算: 平均耗时,成功率,失败率
            $store['total_avg_time'] = $store['suc_count'] + $store['fail_count'] == 0 ? 0 : round(($store['suc_cost_time'] + $store['fail_cost_time']) / ($store['suc_count'] + $store['fail_count']), 9) * 1000; // 平均耗时
            $store['suc_avg_time'] = $store['suc_count'] == 0 ? $store['suc_count'] : round($store['suc_cost_time'] / $store['suc_count'], 9) * 1000;   // 成功平均耗时
            $store['precent'] = $store['suc_count'] + $store['fail_count'] == 0 ? 0 : round(($store['suc_count'] * 100 / ($store['suc_count'] + $store['fail_count'])), 4);  // 成功率

            $html_class = $store['total_avg_time'] > 3000 ? 'class="danger"' : '';
            $table_data .= "<tr $html_class>";
            $table_data .= "<td>{$action}</td>";
            $table_data .= "<td>{$m}</td>";
            $table_data .= "<td>{$store['total_count']}</td>";
            $table_data .= "<td>{$store['total_avg_time']}</td>";
            $table_data .= "<td>{$store['suc_count']}</td>";
            $table_data .= "<td>{$store['suc_avg_time']}</td>";
            $table_data .= "<td>{$store['fail_count']}</td>";
            $table_data .= "<td>{$store['fail_avg_time']}</td>";
            $table_data .= "<td>{$store['precent']}%</td>";
            $table_data .= "</tr>";
        }
    }


    include ST_ROOT . '/Views/Includes/header.tpl.php';
    include ST_ROOT . '/Views/Performance/order.tpl.php';
    include ST_ROOT . '/Views/Includes/footer.tpl.php';
}

function getOrders ($date) {
    if ($date == date('Y-m-d')){
        $redisConfig = \Config\Redis::getConfig();
        $redis = RedisClient::getInstance($redisConfig)->connection();
		$redisData = json_decode($redis->hget(Worker::$order, $date), true);
        $redisData = is_array($redisData) ? $redisData : [];
        $data = [];
        foreach($redisData as $k => $v) {
            $interface_module = explode("#", $k);
            $data[] = implode("\t", array_merge($interface_module, $v) );
        }
    }else {
        $file = Config::$dataPath . Config::$orderDir . $date;
        if(!is_readable($file)) return [];
        $data =  file($file);
    }

    return is_array($data) ? $data : [];
}
