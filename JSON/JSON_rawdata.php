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

// ------------------------------------------------------------
// Insert assumed records (respecting rules for Building/MainFab/SubFab/Facility)
// ------------------------------------------------------------
foreach ($arr as $extsysid => &$person) {
    if (!isset($person['rawdata'])) continue;

    foreach ($person['rawdata'] as $day => &$events) {
        usort($events, fn($a, $b) => strtotime($a['trx_timestamp']) <=> strtotime($b['trx_timestamp']));

        $fixed = [];
        $lastIn = [
            'building' => null,
            'mainfab'  => null,
            'subfab'   => null,
            'facility' => null
        ];

        foreach ($events as $e) {
            $name = strtolower($e['normalizedname']);
            $parts = explode(' ', $name);
            $category = $parts[0] ?? null;
            $direction = $parts[1] ?? null;

            if (!$category) {
                $fixed[] = $e;
                continue;
            }

            if (strtolower($e['sourcename']) === 'parking lot muster') {
                if ($direction === 'out') {
                    if (strtolower($fixed[count($fixed) - 1]['normalizedname']) === 'building out') {
                        // already had a building out → ignore this event
                        $e['normalizedname'] = '';
                        $fixed[] = $e;
                        continue;
                    }
                }
            }

            if ($direction === 'in') {
                // if same category already inside → insert assumed Out
                if ($lastIn[$category] !== null) {
                    $fixed[] = [
                        'sourcename'=>"Assumed Out",
                        'sourcealtname'=>"Assumed Out",
                        'normalizedname'=>ucfirst($category)." Out",
                        'trx_timestamp'=>date('Y-m-d H:i:sO', strtotime($e['trx_timestamp']) - 1),
                        'assumed'=>true
                    ];
                }

                // Special: facility check for overlapping locations
                if ($category === 'facility') {
                    foreach (['building','mainfab','subfab'] as $cat) {
                        if ($lastIn[$cat] !== null) {
                            $fixed[] = [
                                'sourcename'=>"Assumed Out",
                                'sourcealtname'=>"Assumed Out",
                                'normalizedname'=>ucfirst($cat)." Out",
                                'trx_timestamp'=>date('Y-m-d H:i:sO', strtotime($e['trx_timestamp']) - 1),
                                'assumed'=>true
                            ];
                            $lastIn[$cat] = null;
                        }
                    }
                }

                $lastIn[$category] = $e;
            } else { // direction = out
                if ($lastIn[$category] === null) {
                    // insert assumed In slightly earlier
                    $fixed[] = [
                        'sourcename'=>"Assumed In",
                        'sourcealtname'=>"Assumed In",
                        'normalizedname'=>ucfirst($category)." In",
                        'trx_timestamp'=>date('Y-m-d H:i:sO', strtotime($e['trx_timestamp']) - 600),
                        'assumed'=>true
                    ];
                }
                $lastIn[$category] = null;
            }
            $fixed[] = $e;
        }

        // End-of-day: insert assumed Out for any remaining In
        $lateThresholdTs = strtotime($day . ' ' . LATE_THRESHOLD);
        foreach ($lastIn as $category => $inEvent) {
            if ($inEvent !== null) {
                $lastInTs = strtotime($inEvent['trx_timestamp']);
                $cutoff = ($lastInTs <= $lateThresholdTs) ? $lateThresholdTs : $lastInTs + 1800;

                $fixed[] = [
                    'sourcename'     => "Assumed Out",
                    'sourcealtname'  => "Assumed Out",
                    'normalizedname' => ucfirst($category) . " Out",
                    'trx_timestamp'  => date('Y-m-d H:i:sO', $cutoff),
                    'assumed'        => true
                ];
            }
        }

        $events = $fixed;
    }
}

// data / summary
foreach ($arr as $user => $value) {
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

    // load vacation
    $querystring2 = "SELECT day_of_month, vacation FROM hr.vacation WHERE ad_account = '".$user."' ORDER BY modified_time ASC";
    $db_arr2 = db_query($db_pdo, $querystring2);
    foreach ($db_arr2 as $data) {
        $arr[$user]['vacation'][$data['day_of_month']] = $data['vacation'];
    }

    foreach ($value['rawdata'] as $day => $events) {

        $firstInTs = null;
        $lastOutTs = null;

        $dayTos = 0;
        $dayTib = 0;
        $dayTob = 0;
        $dayTif = 0;
        $dayTisf = 0;
        $dayTifac = 0;

        $buildingInStack = null; // tracks Building IN timestamp (parent container)
        $lastSubOutTs = null;    // last main/sub out timestamp

        foreach ($events as $event) {
            $ts = strtotime($event['trx_timestamp']);
            $name = strtolower($event['normalizedname']);
            $parts = explode(' ', $name);
            $category = $parts[0] ?? null;
            $direction = $parts[1] ?? null;

            if (!$category || !$direction) continue;

            if ($direction === 'in') {
                if ($firstInTs === null) $firstInTs = $ts;

                // Start building container if Building In
                if ($category === 'building') {
                    $buildingInStack = $ts;
                }

                // For sub-locations, if buildingInStack is set, count gap since last sub-location OUT
                if (in_array($category, ['mainfab','subfab']) && $buildingInStack !== null) {
                    if ($lastSubOutTs !== null && $lastSubOutTs < $ts) {
                        // Add building-only time between sub-location gaps
                        $buildingOnlyTime = $ts - $lastSubOutTs;
                        $dayTib += $buildingOnlyTime / 3600;
                    } elseif ($lastSubOutTs === null && $buildingInStack < $ts) {
                        // First sub-location inside building
                        $buildingOnlyTime = $ts - $buildingInStack;
                        $dayTib += $buildingOnlyTime / 3600;
                    }
                }

                // store sub-location IN for duration calculation
                if (in_array($category, ['mainfab','subfab','facility'])) {
                    $inTime[$category] = $ts;
                }

            } elseif ($direction === 'out') {

                $lastOutTs = $ts;

                // Calculate sub-location durations
                if (isset($inTime[$category]) && $inTime[$category] !== null) {
                    $duration = $ts - $inTime[$category];
                    switch ($category) {
                        case 'mainfab':
                            $dayTif += $duration / 3600;
                            $dayTib += $duration / 3600;
                            break;
                        case 'subfab':
                            $dayTisf += $duration / 3600;
                            $dayTib += $duration / 3600;
                            break;
                        case 'facility':
                            $dayTifac += $duration / 3600;
                            break;
                    }
                    $lastSubOutTs = $ts;
                    $inTime[$category] = null;
                }

                // If Building OUT, close the building container
                if ($category === 'building' && $buildingInStack !== null) {
                    // Add remaining building-only time since last sub-location out
                    if ($lastSubOutTs !== null && $lastSubOutTs < $ts) {
                        $dayTib += ($ts - $lastSubOutTs) / 3600;
                    } elseif ($lastSubOutTs === null && $buildingInStack < $ts) {
                        $dayTib += ($ts - $buildingInStack) / 3600;
                    }
                    $buildingInStack = null;
                }
            }
        }

        // Compute totals
        if ($firstInTs !== null && $lastOutTs !== null) {
            $dayTos = round(($lastOutTs - $firstInTs)/3600,2);

            // add facility outside building to TIB
            $dayTib = round($dayTib + $dayTifac,2);

            $dayTif = round($dayTif,2);
            $dayTisf = round($dayTisf,2);
            $dayTifac = round($dayTifac,2);

            $dayTob = round($dayTos - $dayTib,2);

            $dayVacation = $arr[$user]['vacation'][$day] ?? 0;

            $arr[$user]['data'][$day] = [
                'tos' => $dayTos,
                'tib' => $dayTib,
                'tob' => $dayTob,
                'tif' => $dayTif,
                'tisf'=> $dayTisf,
                'tifac'=> $dayTifac,
                'vacation'=> $dayVacation,
                'subtotal'=> $dayTib + $dayVacation
            ];

            // Accumulate totals
            $totalTos += $dayTos;
            $totalTib += $dayTib;
            $totalTob += $dayTob;
            $totalTif += $dayTif;
            $totalTisf += $dayTisf;
            $totalTifac += $dayTifac;
            $totalVacation += $dayVacation;
            $total_hours += $arr[$user]['data'][$day]['subtotal'];

            $workedDays[$day] = true;
            $dow = date('N', strtotime($day));
            if ($dow>=6) $weekendDays[] = $day;
        }
    }

    // Handle no-show days
    foreach ($workdaysList as $wday) {
        if (!isset($workedDays[$wday])) {
            $noShowDays[] = $wday;
            $arr[$user]['NoShow'][$wday] = [
                'tos'=>'No Show','tib'=>'No Show','tob'=>'No Show',
                'tif'=>'No Show','tisf'=>'No Show','tifac'=>'No Show',
                'vacation'=>$arr[$user]['vacation'][$wday] ?? 0,
                'subtotal'=>$arr[$user]['vacation'][$wday] ?? 0
            ];
            $totalVacation += $arr[$user]['NoShow'][$wday]['vacation'];
            $total_hours += $arr[$user]['NoShow'][$wday]['subtotal'];
        }
    }

    // Summary
    $arr[$user]['summary'] = [
        'workdaysList'=>$workdaysList,
        'workdays'=>$workDays,
        'actual_workdays'=>count($workedDays),
        'no_show_days'=>$noShowDays,
        'weekend_days'=>$weekendDays,
        'total_tos'=>round($totalTos,2),
        'total_tib'=>round($totalTib,2),
        'total_tob'=>round($totalTob,2),
        'total_tif'=>round($totalTif,2),
        'total_tisf'=>round($totalTisf,2),
        'total_tifac'=>round($totalTifac,2),
        'avg_tos'=> $workDays>0 ? round($totalTos/$workDays,2):0,
        'avg_tib'=> $workDays>0 ? round($totalTib/$workDays,2):0,
        'avg_tob'=> $workDays>0 ? round($totalTob/$workDays,2):0,
        'avg_tif'=> $workDays>0 ? round($totalTif/$workDays,2):0,
        'avg_tisf'=> $workDays>0 ? round($totalTisf/$workDays,2):0,
        'avg_tifac'=> $workDays>0 ? round($totalTifac/$workDays,2):0,
        'total_vacation'=>$totalVacation,
        'avg_vacation'=>$workDays>0 ? round($totalVacation/$workDays,2):0,
        'total_hours'=>round($total_hours,2),
        'avg_hours'=>$workDays>0 ? round($total_hours/$workDays,2):0
    ];
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
