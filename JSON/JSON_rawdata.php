<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

$user_id    = $_GET['user_id'] ?? '';
$start_time = $_GET['start_time'] ?? date('Y-m-01', strtotime('first day of last month'));
$end_time   = $_GET['end_time']   ?? date('Y-m-d', strtotime('last day of last month'));
$query_end_time = date("Y-m-d", strtotime($end_time . " +1 day"));
$query_time = $query_end_time . " 23:59:59";

$workdaysList = getWorkdays($start_time, $end_time);
$workDays     = count($workdaysList);

// ---- Configurable constants ----
$configs = [
    'strict'   => 0,   // only count real badge pairs
    'balanced' => 50,  // give half-credit for missing ranges
    'generous' => 100, // assume full credit up to cutoff
];

$FACTOR_SPLIT = $configs['balanced']; // example selection

const CUTOFF_DAYS    = "04:00:00"; // cutoff for day shift carryover
const CUTOFF_NIGHTS  = "09:00:00"; // cutoff for night shift carryover
const LATE_THRESHOLD = "18:00:00"; // last OUT time threshold for day shift

$db_pdo = db_connect();

$querystring = "
    SELECT id, extsysid, identitytype, identitydivision, 
           sourcename, sourcealtname, trx_timestamp
    FROM hr.acm_rpt_alltrx 
    WHERE trx_timestamp >= '".$start_time."' 
      AND trx_timestamp < '".$query_time."'
";
if ($user_id) {
    $querystring .= " AND extsysid = '".$user_id."'";
}
$querystring .= " ORDER BY trx_timestamp;";

$db_arr = db_query($db_pdo, $querystring);

function assign_shift_day($ts, $shifttype, $cutoff_day = CUTOFF_DAYS, $cutoff_night = CUTOFF_NIGHTS) {
    global $start_time, $query_end_time;

    $date = date("Y-m-d", $ts);
    $time = date("H:i:s", $ts);

    // --- ignore events before start_time ---
    if ($date === $start_time) {
        if ($shifttype === "Days" && $time < $cutoff_day) return null;
        if ($shifttype === "Nights" && $time < $cutoff_night) return null;
    }
    if ($date === $query_end_time) {
        if ($shifttype === "Days" && $time > $cutoff_day) return null;
        if ($shifttype === "Nights" && $time > $cutoff_night) return null;
    }

    // --- assign shift day normally ---
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

foreach ($db_arr as $key => $data) {
    if (!isset($arr[$data['extsysid']])) {
        $arr[$data['extsysid']] = [];
    }
    if (!isset($arr[$data['extsysid']]['meta']['employeetype']) || $arr[$data['extsysid']]['meta']['employeetype'] === '') {
        $arr[$data['extsysid']]['meta']['employeetype'] = $data['identitytype'] ?? 'Employee';
    }
    if (!isset($arr[$data['extsysid']]['meta']['shifttype']) || $arr[$data['extsysid']]['meta']['shifttype'] === '') {
        $arr[$data['extsysid']]['meta']['shifttype'] = $data['identitydivision'] ?? 'Days';
    }

    $shifttype = $arr[$data['extsysid']]['meta']['shifttype'];
    $ts = strtotime($data['trx_timestamp']);
    $dateOnly = assign_shift_day($ts, $shifttype);

    if ($dateOnly) {
        $temp_arr = [
            'sourcename'     => trim($data['sourcename']),
            'sourcealtname'  => trim($data['sourcealtname']),
            'normalizedname' => normalizeSourceName(trim($data['sourcename'])),
            'trx_timestamp'  => $data['trx_timestamp'],
            'assumed'        => false  // mark real records
        ];

        $arr[$data['extsysid']]['rawdata'][$dateOnly][] = $temp_arr;
        unset($temp_arr);
    }
}

// After loading all real records, walk through each $arr[…]['rawdata'][day]
// and insert assumed "In" or "Out" where missing
foreach ($arr as $extsysid => &$person) {
    if (!isset($person['rawdata'])) continue;

    foreach ($person['rawdata'] as $day => &$events) {
        usort($events, fn($a, $b) => strtotime($a['trx_timestamp']) <=> strtotime($b['trx_timestamp']));

        $fixed = [];
        $lastIn = null;
        foreach ($events as $e) {
            if (str_ends_with($e['normalizedname'], 'In')) {
                if ($lastIn !== null) {
                    // previous In had no Out → insert assumed Out before this In
                    $fixed[] = [
                        'sourcename'     => "Assumed Out",
                        'sourcealtname'  => "Assumed Out",
                        'normalizedname' => preg_replace('/In$/', 'Out', $lastIn['normalizedname']),
                        'trx_timestamp'  => $e['trx_timestamp'],
                        'assumed'        => true
                    ];
                }
                $lastIn = $e;
            } elseif (str_ends_with($e['normalizedname'], 'Out')) {
                if ($lastIn === null) {
                    // Out without In → insert assumed In a bit before
                    $fixed[] = [
                        'sourcename'     => "Assumed In",
                        'sourcealtname'  => "Assumed In",
                        'normalizedname' => preg_replace('/Out$/', 'In', $e['normalizedname']),
                        'trx_timestamp'  => date('Y-m-d H:i:sO', strtotime($e['trx_timestamp']) - 600),
                        'assumed'        => true
                    ];
                }
                $lastIn = null; // matched
            }
            $fixed[] = $e;
        }

        // If still inside at end of day → insert cutoff Out
        // If still inside at end of day → insert assumed Out
        if ($lastIn !== null) {
            $lastInTs = strtotime($lastIn['trx_timestamp']);
            $lateThresholdTs = strtotime($day . ' ' . LATE_THRESHOLD);

            if ($lastInTs <= $lateThresholdTs) {
                // case: entered before cutoff, assume they left at cutoff
                $cutoff = date('Y-m-d H:i:sO', $lateThresholdTs);
            } else {
                // case: entered after cutoff, assume they left 30 mins later
                $cutoff = date('Y-m-d H:i:sO', $lastInTs + 1800);
            }

            $fixed[] = [
                'sourcename'     => "Assumed Out",
                'sourcealtname'  => "Assumed Out",
                'normalizedname' => preg_replace('/In$/', 'Out', $lastIn['normalizedname']),
                'trx_timestamp'  => $cutoff,
                'assumed'        => true
            ];
        }

        $events = $fixed;
    }
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
                        $duration = $lastonsite - $tsIn;
                    } else {
                        $cutoff_ts = strtotime($day." ".CUTOFF_DAYS." +1 day");
                        $duration = $cutoff_ts - $tsIn;
                    }
                } else {
                    $cutoff_ts = strtotime($day." ".CUTOFF_NIGHTS." +1 day");
                    $duration = $cutoff_ts - $tsIn;
                }

                // --- Apply factor split ---
                $duration = $duration * $FACTOR_SPLIT / 100;

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
