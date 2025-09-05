<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

$user_id    = $_GET['user_id'] ?? '';
$start_time = $_GET['start_time'] ?? date('Y-m-01', strtotime('first day of last month'));
$end_time   = $_GET['end_time']   ?? date('Y-m-d', strtotime('last day of last month'));
$query_end_time = date("Y-m-d", strtotime($end_time . " +1 day"));

$workdaysList = getWorkdays($start_time, $end_time);
$workDays     = count($workdaysList);

// ---- Configurable constants ----
const FACTOR_SPLIT   = 50;        // 0 = ignore orphan INs, 100 = take all as IN, 50 = split evenly
const CUTOFF_DAYS    = "03:00:00"; // cutoff for day shift carryover
const CUTOFF_NIGHTS  = "09:00:00"; // cutoff for night shift carryover
const LATE_THRESHOLD = "18:00:00"; // last OUT time threshold for day shift

$db_pdo = db_connect();

$querystring = "
    SELECT id, extsysid, identitytype, identitydivision, 
           sourcename, sourcealtname, trx_timestamp
    FROM hr.acm_rpt_alltrx 
    WHERE trx_timestamp >= '".$start_time."' 
      AND trx_timestamp < '".$query_end_time."'
";
if ($user_id) {
    $querystring .= " AND extsysid = '".$user_id."'";
}
$querystring .= " ORDER BY trx_timestamp;";

$db_arr = db_query($db_pdo, $querystring);

function assign_shift_day($ts, $shifttype, $cutoff_day = CUTOFF_DAYS, $cutoff_night = CUTOFF_NIGHTS) {
    global $start_time, $end_time;  // reporting window

    $date = date("Y-m-d", $ts);
    $time = date("H:i:s", $ts);

    // --- ignore events before start_time ---
    if ($date === $start_time) {
        if ($shifttype === "Days" && $time < $cutoff_day) {
            return null; // ignore, belongs to previous day
        }
        if ($shifttype === "Nights" && $time < $cutoff_night) {
            return null; // ignore, belongs to previous day
        }
    }

    if ($shifttype === "Days") {
        if ($time < $cutoff_day) {
            $shift_day = date("Y-m-d", strtotime($date . " -1 day"));
            if ($shift_day < $start_time) return $date;
            return $shift_day;
        }
        return $date;
    }

    if ($shifttype === "Nights") {
        if ($time < $cutoff_night) {
            $shift_day = date("Y-m-d", strtotime($date . " -1 day"));
            if ($shift_day < $start_time) return $date;
            return $shift_day;
        }
        return $date;
    }

    return $date;
}

foreach ($db_arr as $key => $data ) {
    if (!isset($arr[$data['extsysid']])) {
        $arr[$data['extsysid']] = array();
    }
    if (!isset($arr[$data['extsysid']]['meta']['employeetype']) || $arr[$data['extsysid']]['meta']['employeetype']==='') {
        $arr[$data['extsysid']]['meta']['employeetype']=$data['identitytype'] ?? 'Employee';
    }
    if (!isset($arr[$data['extsysid']]['meta']['shifttype']) || $arr[$data['extsysid']]['meta']['shifttype']==='') {
        $arr[$data['extsysid']]['meta']['shifttype']=$data['identitydivision'] ?? 'Days';
    }

    $shifttype = $arr[$data['extsysid']]['meta']['shifttype'];
    $ts = strtotime($data['trx_timestamp']);
    $dateOnly = assign_shift_day($ts, $shifttype);

    $temp_arr = array();
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

    $workedDays = [];
    $weekendDays = [];
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

        $dayTif = 0;
        $dayTisf = 0;
        $dayTifac = 0;

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

            $parts = explode(' ', strtolower(trim($event['normalizedname'])));
            $category  = $parts[0] ?? null;
            $direction = $parts[1] ?? null;

            if (!$category || !$direction) continue;

            if ($direction === 'in') {
                foreach ($inTime as $cat => $tsIn) {
                    if ($tsIn !== null && $cat !== $category) {
                        $duration = $ts - $tsIn;
                        switch ($cat) {
                            case 'building': $dayTib += $duration; break;
                            case 'mainfab': $dayTif += $duration; $dayTib += $duration; break;
                            case 'subfab': $dayTisf += $duration; $dayTib += $duration; break;
                            case 'facility': $dayTifac += $duration; $dayTib += $duration; break;
                        }
                        $inTime[$cat] = null;
                    }
                }

                if ($inTime[$category] !== null) {
                    $duration = $ts - $inTime[$category];
                    switch ($category) {
                        case 'building': $dayTib += $duration; break;
                        case 'mainfab': $dayTif += $duration; $dayTib += $duration; break;
                        case 'subfab': $dayTisf += $duration; $dayTib += $duration; break;
                        case 'facility': $dayTifac += $duration; $dayTib += $duration; break;
                    }
                }
                $inTime[$category] = $ts;
            }
            elseif ($direction === 'out' && $inTime[$category] !== null) {
                $duration = $ts - $inTime[$category];
                switch ($category) {
                    case 'building': $dayTib += $duration; break;
                    case 'mainfab': $dayTif += $duration; $dayTib += $duration; break;
                    case 'subfab': $dayTisf += $duration; $dayTib += $duration; break;
                    case 'facility': $dayTifac += $duration; $dayTib += $duration; break;
                }
                $inTime[$category] = null;
            }
        }

        // close any open INs -> capped at shift cutoff
        $cutoff_ts = strtotime($day." ".($shifttype === "Days" ? CUTOFF_DAYS : CUTOFF_NIGHTS)." +1 day");

        foreach ($inTime as $cat => $tsIn) {
            if ($tsIn !== null) {
                // --- New end-of-day logic ---
                if ($shifttype === "Days") {
                    $late_threshold_ts = strtotime($day." ".LATE_THRESHOLD);
                    if ($lastonsite >= $late_threshold_ts) {
                        // stayed late → close at actual last badge time
                        $duration = $lastonsite - $tsIn;
                    } else {
                        // did not stay late → close at cutoff (3am next day)
                        $duration = $cutoff_ts - $tsIn;
                    }
                } else {
                    // Night shift: always close at cutoff (9am next day)
                    $duration = $cutoff_ts - $tsIn;
                }

                if ($duration > 0) {
                    switch ($cat) {
                        case 'building': $dayTib += $duration; break;
                        case 'mainfab':  $dayTif += $duration; $dayTib += $duration; break;
                        case 'subfab':   $dayTisf += $duration; $dayTib += $duration; break;
                        case 'facility': $dayTifac += $duration; $dayTib += $duration; break;
                    }
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

            $dow = date('N', strtotime($day));
            if ($dow >= 6) {
                $weekendDays[] = $day;
            }
        }
    }

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

    $arr[$user]['summary']['total_tos']       = round($totalTos, 2);
    $arr[$user]['summary']['total_tib']       = round($totalTib, 2);
    $arr[$user]['summary']['total_tob']       = round($totalTob, 2);
    $arr[$user]['summary']['total_tif']       = round($totalTif, 2);
    $arr[$user]['summary']['total_tisf']      = round($totalTisf, 2);
    $arr[$user]['summary']['total_tifac']     = round($totalTifac, 2);

    $arr[$user]['summary']['avg_tos']         = $workDays > 0 ? round($totalTos / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tib']         = $workDays > 0 ? round($totalTib / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tob']         = $workDays > 0 ? round($totalTob / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tif']         = $workDays > 0 ? round($totalTif / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tisf']        = $workDays > 0 ? round($totalTisf / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_tifac']       = $workDays > 0 ? round($totalTifac / $workDays, 2) : 0;

    $arr[$user]['summary']['total_vacation']  = $totalVacation;
    $arr[$user]['summary']['avg_vacation']    = $workDays > 0 ? round($totalVacation / $workDays, 2) : 0;

    $arr[$user]['summary']['total_hours'] = round($total_hours, 2);
    $averageHours = $workDays > 0 ? round($total_hours / $workDays, 2) : 0;
    $arr[$user]['summary']['avg_hours'] = $averageHours;
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
