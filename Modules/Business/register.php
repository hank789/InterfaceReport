<?php
namespace Statistics\Modules\Business;

use Config\Common;
use \Config\Config;

// 新方式: 从自己记录的数据源里读
function register($module, $interface, $date, $start_time, $offset, $count) {
    $err_msg = $notice_msg = $date_btn_str = $table_data = '';

    // date btn
    $date_btn_str = '';
    for ($i = 13; $i >= 0; $i --) {
        $the_time = strtotime("-$i day");
        $the_date = date('Y-m-d', $the_time);
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?md=business&fn=register&date=' . "$the_date" .  '" class="btn"  type="button">' . $html_the_date . '</a>';
        if ($i == 7) {
            $date_btn_str .= '</br>';
        }
    }

    // table data

    $all_series_data = $success_series_data = $fail_series_data = [];
    $data = get_business_register_data($date);
    foreach($data as $time_point => $row) {
        $all_count = $success_count = $failed_count = 0;
        $time = $row['time'];
        $success_count = $row['suc_count'];
        $failed_count = $row['fail_count'];
        $html_class = '';
        if ($success_count > 0) {
            $html_class = ' class="success" ';
        }
        if ($failed_count > $success_count) {
            $html_class = ' class="danger" ';
        }
        $all_count = $success_count + $failed_count;
        $table_data .= "<tr {$html_class}>";
        $table_data .= "<td>{$time}</td>";
        $table_data .= "<td>{$all_count}</td>";
        $table_data .= "<td>{$success_count}</td>";
        $table_data .= "<td>{$failed_count}</td>";
        $table_data .= "</tr>";

        $all_series_data[] = "[" . ($time_point * 1000) . ",{$all_count}]";
        $success_series_data[] = "[" . ($time_point * 1000) . ",{$success_count}]";
        $fail_series_data[] = "[" . ($time_point * 1000) . ",{$failed_count}]";
    }

    $all_series_data = $all_series_data ? implode(',', $all_series_data) : '';
    $success_series_data = $success_series_data ? implode(',', $success_series_data) : '';
    $fail_series_data = $fail_series_data ? implode(',', $fail_series_data) : '';

    $refreshSeconds = Config::$mainRefreshSeconds;

    include ST_ROOT . '/Views/Includes/header.tpl.php';
    include ST_ROOT . '/Views/Business/register.tpl.php';
    include ST_ROOT . '/Views/Includes/footer.tpl.php';
}

function format_business_register($st_explode, $date, $stepSeconds = 300)
{
    $st_data = $code_map = array();

    // 汇总计算
    foreach ($st_explode as $line) {
        $line_data = explode("\t", $line);
        if (!$line_data) {
            continue;
        }
        $time_line = $line_data[0];
        $time_line = ceil($time_line / $stepSeconds) * $stepSeconds;
        $suc_count = $line_data[1];
        $fail_count = $line_data[2];
        if (!isset($st_data[$time_line])) {
            $st_data[$time_line] = array(
                'time' => date('Y-m-d H:i:s', $time_line),
                'suc_count' => 0,
                'fail_count' => 0
            );
        }
        $st_data[$time_line]['suc_count'] += $suc_count;
        $st_data[$time_line]['fail_count'] += $fail_count;
    }

    $time_point = strtotime($date);
    $sum = ceil(86400 / $stepSeconds);
    $time = time();
    $result = array();
    for ($i = 0; $i < $sum; $i++) {
        $result[$time_point] = isset($st_data[$time_point]) ? $st_data[$time_point] : array(
            'time' => date('Y-m-d H:i:s', $time_point),
            'suc_count' => 0,
            'fail_count' => 0
        );
        $time_point += $stepSeconds;
        if ($time_point > $time) {
            break;
        }
    }
    ksort($result);
    return $result;
}

// allData
function get_business_register_data($date, $stepSeconds = 300)
{
    $data = [];
    $file = Config::$dataPath . Common::STAT_BUSINESS_REGISTER_DIR. $date;
    if(is_readable($file)) {
        $data = file($file);
    }

    return format_business_register($data, $date, $stepSeconds);
}