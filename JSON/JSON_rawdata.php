<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

// xdebug
//$_GET['user_id'] = 'oliver.li';


$user_id = $_GET['user_id'] ?? '';
$start_time = $_GET['start_time'] ?? date('Y-m-01');
$end_time = $_GET['end_time'] ?? date('Y-m-t');
$workDays = getWorkdays($start_time, $end_time);

$querystring='';
$db_pdo = db_connect();

$querystring = "SELECT id, extsysid, identitytype, identitydivision, sourcename, trx_timestamp";
$querystring .= " FROM hr.acm_rpt_alltrx WHERE trx_timestamp >= '".$start_time."' and trx_timestamp <= '".$end_time."'";
if ($user_id) {
    $querystring .= " and extsysid = '".$user_id."'";
}
$querystring .= " order by trx_timestamp;";

$db_arr = db_query($db_pdo, $querystring);

foreach ($db_arr as $key => $data ) {
    if (!isset($arr[$data['extsysid']])) {
        $arr[$data['extsysid']] = array();
    }
    if (!isset($arr[$data['extsysid']]['employeetype']) || $arr[$data['extsysid']]['employeetype']==='') {
        $arr[$data['extsysid']]['meta']['employeetype']=$data['identitytype'] ?? 'Employee';
    }
    if (!isset($arr[$data['extsysid']]['shifttype']) || $arr[$data['extsysid']]['shifttype']==='') {
        $arr[$data['extsysid']]['meta']['shifttype']=$data['identitydivision'] ?? 'Days';
    }

    $dateOnly = date('Y-m-d', strtotime($data['trx_timestamp']));

    $temp_arr = array();
//    $temp_arr['id'] = $data['id'];
    $temp_arr['sourcename'] = $data['sourcename'];
    $temp_arr['trx_timestamp'] = $data['trx_timestamp'];

    $arr[$data['extsysid']]['rawdata'][$dateOnly][] = $temp_arr;
    unset($temp_arr);
}

// data / summary
foreach ($arr as $user => $value ) {
    $employeetype = $value['meta']['employeetype'];
    $shifttype = $value['meta']['shifttype'];

    $grandTotalSeconds = 0;
    $workedDays = [];   // track which days had records
    $weekendDays = [];  // track weekends with hours

    foreach($value['rawdata'] as $day => $events) {
        $totalSeconds = 0;
        $inTime = null;

        foreach ($events as $event) {
            $ts = strtotime($event['trx_timestamp']);
            $source = strtolower($event['sourcename']);

            if (strpos($source, 'in') !== false) {
                $inTime = $ts;
            } elseif (strpos($source, 'out') !== false && $inTime !== null) {
                $totalSeconds += ($ts - $inTime);
                $inTime = null; // reset
            }
        }
        if ($totalSeconds > 0) {
            $hours = round($totalSeconds / 3600, 2);
            $arr[$user]['data'][$day] = $hours;

            $grandTotalSeconds += $totalSeconds;

            // mark this day as worked
            $workedDays[$day] = true;

            // check if weekend
            $dow = date('N', strtotime($day)); // 1=Mon .. 7=Sun
            if ($dow >= 6) {
                $weekendDays[] = $day;
            }
        }
    }
    $grandTotalHours = round($grandTotalSeconds / 3600, 2);
    $actualWorkdays = count($workedDays);
    $noShowDays     = max($workDays - $actualWorkdays, 0);
    $averageHours = $workDays > 0 ? round($grandTotalHours / $workDays, 2) : 0;

    $arr[$user]['summary']['total_hours'] = $grandTotalHours;
    $arr[$user]['summary']['workdays'] = $workDays;
    $arr[$user]['summary']['actual_workdays'] = $actualWorkdays;
    $arr[$user]['summary']['no_show_days'] = $noShowDays;
    $arr[$user]['summary']['weekend_days'] = $weekendDays;
    $arr[$user]['summary']['average_hours'] = $averageHours;
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
