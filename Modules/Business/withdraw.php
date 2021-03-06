<?php
namespace Statistics\Modules\Business;

use \Config\Config;

// 新方式: 从自己记录的数据源里读
function withdraw ($module, $interface, $date, $start_time, $offset, $count) {
    $err_msg = $notice_msg = $date_btn_str = $table_data = '';

    // date btn
    $date_btn_str = '';
    for ($i = 13; $i >= 0; $i --) {
        $the_time = strtotime("-$i day");
        $the_date = date('Y-m-d', $the_time);
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?md=business&fn=withdraw&date=' . "$the_date" .  '" class="btn"  type="button">' . $html_the_date . '</a>';
        if ($i == 7) {
            $date_btn_str .= '</br>';
        }
    }

    // table data
    $all_withdraw_suc_times = $all_withdraw_fail_times = 0;
    $all_withdraw_suc_money = $all_withdraw_fail_money = 0;
    $all_series_data = $success_series_data = $fail_series_data = $all_money_series_data = $success_money_series_data = $fail_money_series_data = [];
    $data = get_all_data($date);
    foreach($data as $time_point => $row) {
        $all_count = $success_count = $failed_count = 0;
        $all_money = $success_money = $failed_money = 0;
        $time = $row['time'];
        $success_count = $row['suc_count'];
        $failed_count = $row['fail_count'];
        $success_money = $row['suc_money'];
        $failed_money = $row['fail_money'];
        $html_class = '';
        if ($success_count > 0) {
            $html_class = ' class="success" ';
        }
        if ($failed_count > $success_count) {
            $html_class = ' class="danger" ';
        }
        $all_count = $success_count + $failed_count;
        $all_money = $success_money + $failed_money;
        $table_data .= "<tr {$html_class}>";
        $table_data .= "<td>{$time}</td>";
        $table_data .= "<td>{$all_count}</td>";
        $table_data .= "<td>{$success_count}</td>";
        $table_data .= "<td>{$failed_count}</td>";
        $table_data .= "<td>{$success_money}</td>";
        $table_data .= "<td>{$failed_money}</td>";
        $table_data .= "</tr>";

        $all_series_data[] = "[" . ($time_point * 1000) . ",{$all_count}]";
        $success_series_data[] = "[" . ($time_point * 1000) . ",{$success_count}]";
        $fail_series_data[] = "[" . ($time_point * 1000) . ",{$failed_count}]";


        $all_money_series_data[] = "[" . ($time_point * 1000) . ",{$all_money}]";
        $success_money_series_data[] = "[" . ($time_point * 1000) . ",{$success_money}]";
        $fail_money_series_data[] = "[" . ($time_point * 1000) . ",{$failed_money}]";

        $all_withdraw_suc_times += $success_count;
        $all_withdraw_fail_times += $failed_count;
        $all_withdraw_suc_money += $success_money;
        $all_withdraw_fail_money += $failed_money;
    }

    $all_series_data = $all_series_data ? implode(',', $all_series_data) : '';
    $success_series_data = $success_series_data ? implode(',', $success_series_data) : '';
    $fail_series_data = $fail_series_data ? implode(',', $fail_series_data) : '';

    $all_money_series_data = $all_money_series_data ? implode(',', $all_money_series_data) : '';
    $success_money_series_data = $success_money_series_data ? implode(',', $success_money_series_data) : '';
    $fail_money_series_data = $fail_money_series_data ? implode(',', $fail_money_series_data) : '';

    $all_withdraw_times = $all_withdraw_suc_times + $all_withdraw_fail_times;
    $all_withdraw_money = $all_withdraw_suc_money + $all_withdraw_fail_money;
    $all_withdraw_money_str = $all_withdraw_money ? number_format($all_withdraw_money, 2) : 0;

    $refreshSeconds = Config::$mainRefreshSeconds;

    include ST_ROOT . '/Views/Includes/header.tpl.php';
    include ST_ROOT . '/Views/Business/withdraw.tpl.php';
    include ST_ROOT . '/Views/Includes/footer.tpl.php';
}

function format_st($st_explode, $date, $stepSeconds = 300)
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
        $suc_money = $line_data[3];
        $fail_money = $line_data[4];
        if (!isset($st_data[$time_line])) {
            $st_data[$time_line] = array(
                'time' => date('Y-m-d H:i:s', $time_line),
                'suc_count' => 0,
                'fail_count' => 0,
                'suc_money' => 0,
                'fail_money' => 0
            );
        }
        $st_data[$time_line]['suc_count'] += $suc_count;
        $st_data[$time_line]['fail_count'] += $fail_count;
        $st_data[$time_line]['suc_money'] += $suc_money;
        $st_data[$time_line]['fail_money'] += $fail_money;
    }

    $time_point = strtotime($date);
    $sum = ceil(86400 / $stepSeconds);
    $time = time();
    $result = array();
    for ($i = 0; $i < $sum; $i++) {
        $result[$time_point] = isset($st_data[$time_point]) ? $st_data[$time_point] : array(
            'time' => date('Y-m-d H:i:s', $time_point),
            'suc_count' => 0,
            'fail_count' => 0,
            'suc_money' => 0,
            'fail_money' => 0
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
function get_all_data($date, $stepSeconds = 300)
{
    $data = [];
    $file = Config::$dataPath . Config::STAT_BUSINESS_WITHDRAW_DIR . $date;
    if(is_readable($file)) {
        $data = file($file);
    }

    return format_st($data, $date, $stepSeconds);
}