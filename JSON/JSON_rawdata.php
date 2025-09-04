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
//$vacationHours = $_GET['vacation_hours'] ?? 0;

$querystring='';
$db_pdo = db_connect();

$querystring = "SELECT id, extsysid, identitytype, identitydivision, sourcename, sourcealtname, trx_timestamp";
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
    $temp_arr['sourcename'] = trim($data['sourcename']);
    $temp_arr['sourcealtname'] = trim($data['sourcealtname']);
    $temp_arr['normalizedname'] = normalizeSourceName(trim($data['sourcename']));
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
    $totalTif = 0;
    $totalTisf = 0;
    $totalTifac = 0;
    $totalVacation = 0;
    $total_hours = 0;

    $workedDays = [];   // track which days had records
    $weekendDays = [];  // track weekends with hours
    $noShowDays = [];

    $querystring2 = "SELECT day_of_month, vacation FROM hr.vacation WHERE ad_account = '".$user."' order by modified_time asc";
    $db_arr2 = db_query($db_pdo, $querystring2);
    foreach ($db_arr2 as $key => $data ) {
        $arr[$user]['vacation'][$data['day_of_month']] = $data['vacation'];
    }

    foreach($value['rawdata'] as $day => $events) {
        $dayTos = 0;
        $dayTib = 0;
        $dayTob = 0;

        $dayTif = 0;   // Time in main fab
        $dayTisf = 0;  // Time in subfab
        $dayTifac = 0; // Time in facility

        $inTime = [
            'building' => null,
            'mainfab'  => null,
            'subfab'   => null,
            'facility' => null,
        ];

        $firstonsite = 0;
        $lastonsite = 0;

        foreach ($events as $event) {
            if ($firstonsite === 0) $firstonsite = strtotime($event['trx_timestamp']);
            $lastonsite = strtotime($event['trx_timestamp']);
            $ts = strtotime($event['trx_timestamp']);

            // normalized format: "<category> <in|out>"
            $parts = explode(' ', strtolower(trim($event['normalizedname'])));
            $category  = $parts[0] ?? null;   // building / mainfab / subfab / facility
            $direction = $parts[1] ?? null;   // in / out

            if (!$category || !$direction) continue;

            // Handle IN
            if ($direction === 'in') {
                // If previous IN wasn’t closed, close it here
                if ($inTime[$category] !== null) {
                    $duration = $ts - $inTime[$category];
                    switch ($category) {
                        case 'building': $dayTib += $duration; break;
                        case 'mainfab':  $dayTif += $duration; break;
                        case 'subfab':   $dayTisf += $duration; break;
                        case 'facility': $dayTifac += $duration; break;
                    }
                }
                // Start new IN
                $inTime[$category] = $ts;
            }  elseif ($direction === 'out' && $inTime[$category] !== null) {
                $duration = $ts - $inTime[$category];
                switch ($category) {
                    case 'building': $dayTib += $duration; break;
                    case 'mainfab':  $dayTif += $duration; break;
                    case 'subfab':   $dayTisf += $duration; break;
                    case 'facility': $dayTifac += $duration; break;
                }
                $inTime[$category] = null; // reset
            }
        }

        foreach ($inTime as $cat => $tsIn) {
            if ($tsIn !== null) {
                $duration = $lastonsite - $tsIn;
                switch ($cat) {
                    case 'building': $dayTib += $duration; break;
                    case 'mainfab':  $dayTif += $duration; break;
                    case 'subfab':   $dayTisf += $duration; break;
                    case 'facility': $dayTifac += $duration; break;
                }
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

            $dayTif = round($dayTif / 3600, 2);
            $arr[$user]['data'][$day]['tif'] = $dayTif;
            $totalTif += $dayTif;

            $dayTisf = round($dayTisf / 3600, 2);
            $arr[$user]['data'][$day]['tisf'] = $dayTisf;
            $totalTisf += $dayTisf;

            $dayTifac = round($dayTifac / 3600, 2);
            $arr[$user]['data'][$day]['tifac'] = $dayTifac;
            $totalTifac += $dayTifac;

            $dayVacation = $arr[$user]['vacation'][$day] ?? 0;
            $arr[$user]['data'][$day]['vacation'] = $dayVacation;
            $totalVacation += $dayVacation;

            $dayHours = round(($dayTib + $dayVacation), 2);
            $arr[$user]['data'][$day]['subtotal'] = $dayHours;
            $total_hours += $dayHours;

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
            $arr[$user]['NoShow'][$wday]['tos'] = 'No Show';
            $arr[$user]['NoShow'][$wday]['tib'] = 'No Show';
            $arr[$user]['NoShow'][$wday]['tob'] = 'No Show';
            $arr[$user]['NoShow'][$wday]['tif'] = 'No Show';
            $arr[$user]['NoShow'][$wday]['tisf'] = 'No Show';
            $arr[$user]['NoShow'][$wday]['tifac'] = 'No Show';
            $arr[$user]['NoShow'][$wday]['vacation'] = $arr[$user]['vacation'][$wday] ?? 0;
            $totalVacation += $arr[$user]['NoShow'][$wday]['vacation'];
            $arr[$user]['NoShow'][$wday]['subtotal'] = $arr[$user]['NoShow'][$wday]['vacation'];
            $total_hours += $arr[$user]['NoShow'][$wday]['subtotal'];
        }
    }
    $arr[$user]['summary']['workdaysList'] = $workdaysList;
    $arr[$user]['summary']['workdays'] = $workDays;
    $arr[$user]['summary']['actual_workdays'] = count($workedDays);
    $arr[$user]['summary']['no_show_days'] = $noShowDays;
    $arr[$user]['summary']['weekend_days'] = $weekendDays;
//    $arr[$user]['summary']['vacation_hours'] = $dayVacation;

    $arr[$user]['summary']['total_tos']       = round($totalTos, 2);
    $arr[$user]['summary']['total_tib']       = round($totalTib, 2);
    $arr[$user]['summary']['total_tob']       = round($totalTob, 2);
    $arr[$user]['summary']['total_tif']       = 0;
    $arr[$user]['summary']['total_tisf']       = 0;
    $arr[$user]['summary']['total_tifac']       = 0;

    $arr[$user]['summary']['avg_tos']         = $workDays > 0 ? round($totalTos / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tib']         = $workDays > 0 ? round($totalTib / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tob']         = $workDays > 0 ? round($totalTob / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tif']         = 0;
    $arr[$user]['summary']['avg_tisf']        = 0;
    $arr[$user]['summary']['avg_tifac']       = 0;

    $arr[$user]['summary']['total_vacation']  = $totalVacation;
    $arr[$user]['summary']['avg_vacation']    = $workDays > 0 ? round($totalVacation / $workDays, 2) : 0;

    $arr[$user]['summary']['total_hours'] = round($total_hours, 2);
    $averageHours = $workDays > 0 ? round($total_hours / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_hours'] = $averageHours;
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
