<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

// xdebug
//$_GET['user_id'] = 'oliver.li';


$user_id = $_GET['user_id'] ?? '';
$start_time = $_GET['start_time'] ?? date('Y-m-01', strtotime('first day of last month')); //date('Y-m-01');
$end_time = $_GET['end_time'] ?? date('Y-m-01'); //date('Y-m-t');
$workdaysList = getWorkdays($start_time, $end_time);
$workDays = count($workdaysList);
$vacationHours = $_GET['vacation_hours'] ?? 0;

$querystring='';
$db_pdo = db_connect();

$querystring = "SELECT id, extsysid, identitytype, identitydivision, sourcename, trx_timestamp";
$querystring .= " FROM hr.acm_rpt_alltrx WHERE trx_timestamp >= '".$start_time."' and trx_timestamp < '".$end_time."'";
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

    $totalTos = 0;
    $totalTib = 0;
    $totalTob = 0;

    $workedDays = [];   // track which days had records
    $weekendDays = [];  // track weekends with hours
    $noShowDays = [];

    foreach($value['rawdata'] as $day => $events) {
        $dayTos = 0;
        $dayTib = 0;
        $dayTob = 0;

        $inTime = null;
        $firstonsite = 0;
        $lastonsite = 0;

        foreach ($events as $event) {
            if ($firstonsite===0) $firstonsite = strtotime($event['trx_timestamp']);
            $lastonsite = strtotime($event['trx_timestamp']);
            $ts = strtotime($event['trx_timestamp']);
            $source = strtolower($event['sourcename']);

            if (strpos($source, 'in') !== false) {
                $inTime = $ts;
            } elseif (strpos($source, 'out') !== false && $inTime !== null) {
                $dayTib += ($ts - $inTime);
                $inTime = null; // reset
            }
        }
        if ($dayTib > 0) {
            $dayTos = round(($lastonsite - $firstonsite) / 3600, 2);
            $arr[$user]['data'][$day]['tos'] = $dayTos;
            $totalTos += $dayTos;

            $dayTib = round($dayTib / 3600, 2);
            $arr[$user]['data'][$day]['tib'] = $dayTib;
            $totalTib += $dayTib;

            $dayTob = round($dayTos - $dayTib, 2);
            $arr[$user]['data'][$day]['tob'] = $dayTob;
            $totalTob += $dayTob;

            $workedDays[$day] = true;

            // check if weekend
            $dow = date('N', strtotime($day)); // 1=Mon .. 7=Sun
            if ($dow >= 6) {
                $weekendDays[] = $day;
            }
        }
    }
    // mark no-shows
    foreach ($workdaysList as $wday) {
        if (!isset($workedDays[$wday])) {
            $noShowDays[] = $wday;
        }
    }

    $arr[$user]['summary']['workdays'] = $workDays;
    $arr[$user]['summary']['actual_workdays'] = count($workedDays);
    $arr[$user]['summary']['no_show_days'] = $noShowDays;
    $arr[$user]['summary']['weekend_days'] = $weekendDays;
    $arr[$user]['summary']['vacation_hours'] = $vacationHours;

    $arr[$user]['summary']['total_tos']       = round($totalTos, 2);
    $arr[$user]['summary']['total_tib']       = round($totalTib, 2);
    $arr[$user]['summary']['total_tob']       = round($totalTob, 2);
    $arr[$user]['summary']['avg_tos']         = $workDays > 0 ? round($totalTos / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tib']         = $workDays > 0 ? round($totalTib / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tob']         = $workDays > 0 ? round($totalTob / $workDays, 2) : 0;

    $total_hours = $arr[$user]['summary']['total_tib'] + $vacationHours;
    $arr[$user]['summary']['total_hours'] = $total_hours;
    $averageHours = $workDays > 0 ? round($total_hours / $workDays, 2) : 0;
    $arr[$user]['summary']['average_hours'] = $averageHours;
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
