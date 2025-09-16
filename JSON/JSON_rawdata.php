<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

if(isset($_GET['uid']) && strlen($_GET['uid']) > 0) {
    $user_id     = $_GET['uid'];
} else {
    $user_id     = $_GET['user_id'] ?? '';
}

if (empty($user_id)) {
    $arr['error'] = 'Need user id';
    header('Content-Type: application/json');
    echo(json_encode($arr));
    return;
}

$start_time = $_GET['start'] ?? date('Y-m-01', strtotime('first day of last month'));
$end_time   = $_GET['end']   ?? date('Y-m-d', strtotime('last day of last month'));
$query_end_time = date("Y-m-d", strtotime($end_time . " +1 day"));
$query_time = $query_end_time . " 23:59:59";
$team = $_GET['team'] ?? '';

$workdaysList = getWorkdays($start_time, $end_time);
$workDays     = count($workdaysList);

$configs = [
    'strict' => [
        'FACTOR_SPLIT' => 0,    // only count real badge pairs
        'ASSUMED_GAP'  => 0     // no assumed extension
    ],
    'balanced' => [
        'FACTOR_SPLIT' => 50,   // give half-credit for missing ranges
        'ASSUMED_GAP'  => 1800  // 30 minutes
    ],
    'generous' => [
        'FACTOR_SPLIT' => 100,  // assume full credit
        'ASSUMED_GAP'  => 3600  // 1 hour
    ]
];

// Read mode from query string, default to balanced
$mode = $_GET['mode'] ?? 'balanced';

// Fallback if mode is invalid
if (!isset($configs[$mode])) {
    $mode = 'balanced';
}

// Apply settings
$FACTOR_SPLIT = $configs[$mode]['FACTOR_SPLIT'];
define('ASSUMED_GAP', $configs[$mode]['ASSUMED_GAP']);

const TIMECONVERTER = 3600;

const CUTOFF_DAYS    = "04:00:00"; // cutoff for day shift carryover
const CUTOFF_NIGHTS  = "09:00:00"; // cutoff for night shift carryover
const LATE_THRESHOLD = "18:00:00"; // last OUT time threshold for day shift
const ASSUMED_1SEC = 1;

$user_list = [];

$db_pdo = db_connect();

$querystring_current_user = "SELECT * FROM hr.employee WHERE samaccountname = '".$user_id."'";
$db_arr_current_user = db_query($db_pdo, $querystring_current_user);
if ($db_arr_current_user) {
    foreach ($db_arr_current_user as $data) {
        $arr[$user_id]['meta']['employeetype2'] = $data['employeetype'] ?? '';
        $arr[$user_id]['meta']['employeeid'] = $data['employeeid'] ?? '';
        $arr[$user_id]['meta']['givenname'] = $data['givenname'] ?? '';
        $arr[$user_id]['meta']['sn'] = $data['sn'] ?? '';
        $arr[$user_id]['meta']['mail'] = $data['mail'] ?? '';
        $arr[$user_id]['meta']['department'] = $data['department'] ?? '';
        $arr[$user_id]['meta']['departmentnumber'] = $data['departmentnumber'] ?? '';
        $arr[$user_id]['meta']['ipphone'] = $data['ipphone'] ?? '';
        $arr[$user_id]['meta']['telephonenumber'] = $data['telephonenumber'] ?? '';
        $arr[$user_id]['meta']['manager'] = $data['manager_samaccountname'] ?? '';
    }
    $user_list[] = $user_id;
} else {
    $arr['error'] = 'Cannot find the user: ' . $user_id;
    header('Content-Type: application/json');
    echo(json_encode($arr));
    return;
}

$json_role = json_decode(file_get_contents(request_json_api('/JSON/JSON_user_role.php?uid='.$user_id), false, getContextCookies()), true);
if ($json_role) {
    $arr[$user_id]['meta']['role'] = $json_role[$user_id] ?? '';
} else {
    $arr[$user_id]['meta']['role'] = '';
}

if ($team) {
    if ($team === 'all') {
        $querystring_team_users = "SELECT * FROM hr.employee order by samaccountname ASC";
    } else if($team === 'team') {
        $querystring_team_users = "SELECT * FROM hr.employee WHERE manager_samaccountname = '".$user_id."' order by samaccountname ASC";
    } else {
        $arr['error'] = 'Wrong team type: ' . $team;
        header('Content-Type: application/json');
        echo(json_encode($arr));
        return;
    }
    $db_arr_team_users = db_query($db_pdo, $querystring_team_users);
    if ($db_arr_team_users) {
        foreach ($db_arr_team_users as $data) {
            $team_user = $data['samaccountname'] ?? '';
            if ($team_user) {
                $arr[$team_user]['meta']['employeetype2'] = $data['employeetype'] ?? '';
                $arr[$team_user]['meta']['employeeid'] = $data['employeeid'] ?? '';
                $arr[$team_user]['meta']['givenname'] = $data['givenname'] ?? '';
                $arr[$team_user]['meta']['sn'] = $data['sn'] ?? '';
                $arr[$team_user]['meta']['mail'] = $data['mail'] ?? '';
                $arr[$team_user]['meta']['department'] = $data['department'] ?? '';
                $arr[$team_user]['meta']['departmentnumber'] = $data['departmentnumber'] ?? '';
                $arr[$team_user]['meta']['ipphone'] = $data['ipphone'] ?? '';
                $arr[$team_user]['meta']['telephonenumber'] = $data['telephonenumber'] ?? '';
            }
            $user_list[] = $team_user;
        }
    }
}

// load vacation
$querystring_vacation = "SELECT ad_account, day_of_month, vacation FROM hr.vacation ";
$querystring_vacation .= " WHERE day_of_month >= '".$start_time."' AND day_of_month <= '".$end_time."' ";
if ($arr[$user_id]['meta']['role'] === 'admin') {
    $querystring_vacation .= " ORDER BY modified_time ASC";
} else {
    $querystring_vacation .= " AND ad_account in ".arrayToPgInList($user_list)." ORDER BY modified_time ASC";
}
$db_arr_vacation = db_query($db_pdo, $querystring_vacation);
if ($db_arr_vacation) {
    foreach ($db_arr_vacation as $data) {
        $arr[$data['ad_account']]['vacation'][$data['day_of_month']] = $data['vacation'];
    }
}

$querystring = "
    SELECT id, extsysid, identitytype, identitydivision, 
           sourcename, sourcealtname, trx_timestamp
    FROM hr.acm_rpt_alltrx 
    WHERE trx_timestamp >= '".$start_time."' 
      AND trx_timestamp < '".$query_time."'
";
if ($user_list) {
    $querystring .= " AND extsysid in ".arrayToPgInList($user_list)." ";
}
$querystring .= " ORDER BY trx_timestamp ASC, serialnumber ASC;";

$db_arr = db_query($db_pdo, $querystring);
if (!$db_arr) {
    header('Content-Type: application/json');
    echo(json_encode($arr));
    return;
}

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
    $extsysid1 = $data['extsysid'];
    if (!isset($arr[$extsysid1])) {
        $arr[$extsysid1] = [];
    }
    if (!isset($arr[$extsysid1]['meta']['employeetype']) || $arr[$extsysid1]['meta']['employeetype'] === '') {
        $arr[$extsysid1]['meta']['employeetype'] = $data['identitytype'] ?? 'Employee';
    }
    if (!isset($arr[$extsysid1]['meta']['shifttype']) || $arr[$extsysid1]['meta']['shifttype'] === '') {
        $arr[$extsysid1]['meta']['shifttype'] = $data['identitydivision'] ?? 'Days';
    }

    $shifttype = $arr[$extsysid1]['meta']['shifttype'];
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

        $arr[$extsysid1]['rawdata'][$dateOnly][] = $temp_arr;
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
            'mainfab' => null,
            'subfab' => null,
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

            // Special case: parking lot muster → ignore duplicate building out
            if (strtolower($e['sourcename']) === 'parking lot muster') {
                if ($direction === 'out') {
                    if (!empty($fixed) && strtolower(end($fixed)['normalizedname']) === 'building out') {
                        $e['normalizedname'] = '';
                        $fixed[] = $e;
                        continue;
                    }
                }
            }

            $txs = strtotime($e['trx_timestamp']);

            if ($direction === 'in') {
                // If same category already inside → insert assumed Out
                if ($lastIn[$category] !== null) {
                    $prevTs = strtotime($lastIn[$category]['trx_timestamp']);
                    $gap = $txs - $prevTs;
                    $assumedTs = $prevTs + intval($gap * $FACTOR_SPLIT / 100);

                    $fixed[] = [
                        'sourcename'     => "Assumed Out",
                        'sourcealtname'  => "Assumed Out",
                        'normalizedname' => ucfirst($category) . " Out",
                        'trx_timestamp'  => date('Y-m-d H:i:sO', $assumedTs),
                        'assumed'        => true
                    ];
                }

                // Special: Facility In → close building/mainfab/subfab
                if ($category === 'facility') {
                    foreach (['building','mainfab','subfab'] as $cat) {
                        if ($lastIn[$cat] !== null) {
                            $fixed[] = [
                                'sourcename'     => "Assumed Out",
                                'sourcealtname'  => "Assumed Out",
                                'normalizedname' => ucfirst($cat)." Out",
                                'trx_timestamp'  => date('Y-m-d H:i:sO', $txs - ASSUMED_1SEC),
                                'assumed'        => true
                            ];
                            $lastIn[$cat] = null;
                        }
                    }
                }

                // Special: Building/Mainfab/Subfab In → close facility
                if (in_array($category, ['building','mainfab','subfab'])) {
                    if ($lastIn['facility'] !== null) {
                        $fixed[] = [
                            'sourcename'     => "Assumed Out",
                            'sourcealtname'  => "Assumed Out",
                            'normalizedname' => "Facility Out",
                            'trx_timestamp'  => date('Y-m-d H:i:sO', $txs - ASSUMED_1SEC),
                            'assumed'        => true
                        ];
                        $lastIn['facility'] = null;
                    }
                }

                $lastIn[$category] = $e;
            } else { // direction === "out"
                if ($lastIn[$category] === null) {
                    // Insert assumed In before this Out
                    $prevTs = $fixed ? strtotime(end($fixed)['trx_timestamp']) : $txs - ASSUMED_GAP;
                    $gap = $txs - $prevTs;
                    $assumedTs = $prevTs + intval($gap * $FACTOR_SPLIT / 100);

                    $fixed[] = [
                        'sourcename'     => "Assumed In",
                        'sourcealtname'  => "Assumed In",
                        'normalizedname' => ucfirst($category) . " In",
                        'trx_timestamp'  => date('Y-m-d H:i:sO', $assumedTs),
                        'assumed'        => true
                    ];
                }

                // 🔴 Cascading closures
                if ($category === 'building') {
                    foreach (['mainfab','subfab'] as $child) {
                        if ($lastIn[$child] !== null) {
                            $fixed[] = [
                                'sourcename'     => "Assumed Out",
                                'sourcealtname'  => "Assumed Out",
                                'normalizedname' => ucfirst($child) . " Out",
                                'trx_timestamp'  => date('Y-m-d H:i:sO', $txs - ASSUMED_1SEC),
                                'assumed'        => true
                            ];
                            $lastIn[$child] = null;
                        }
                    }
                }

                if ($category === 'mainfab') {
                    if ($lastIn['subfab'] !== null) {
                        $fixed[] = [
                            'sourcename'     => "Assumed Out",
                            'sourcealtname'  => "Assumed Out",
                            'normalizedname' => "Subfab Out",
                            'trx_timestamp'  => date('Y-m-d H:i:sO', $txs - ASSUMED_1SEC),
                            'assumed'        => true
                        ];
                        $lastIn['subfab'] = null;
                    }
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
//                $cutoff = ($lastInTs <= $lateThresholdTs) ? $lateThresholdTs : $lastInTs + ASSUMED_GAP;
                $cutoff = $lastInTs + ASSUMED_GAP;

                $fixed[] = [
                    'sourcename' => "Assumed Out",
                    'sourcealtname' => "Assumed Out",
                    'normalizedname' => ucfirst($category) . " Out",
                    'trx_timestamp' => date('Y-m-d H:i:sO', $cutoff),
                    'assumed' => true
                ];
            }
        }

        $events = $fixed;
    }
}

// data / summary
foreach ($arr as $user => $value) {
    $employeetype = $value['meta']['employeetype'] ?? '';
    $shifttype = $value['meta']['shifttype'] ?? '';
    $rawvalue = $value['rawdata'] ?? null;

    if ($rawvalue===null) {
        continue;
    }

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

    foreach ($rawvalue as $keyday => $eventvalue) {
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

        foreach ($eventvalue as $event) {
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
//                if (in_array($category, ['mainfab','subfab']) && $buildingInStack !== null) {
//                    if ($lastSubOutTs !== null && $lastSubOutTs < $ts) {
//                        // Add building-only time between sub-location gaps
//                        $buildingOnlyTime = $ts - $lastSubOutTs;
//                        $dayTib += $buildingOnlyTime / TIMECONVERTER;
//                    } elseif ($lastSubOutTs === null && $buildingInStack < $ts) {
//                        // First sub-location inside building
//                        $buildingOnlyTime = $ts - $buildingInStack;
//                        $dayTib += $buildingOnlyTime / TIMECONVERTER;
//                    }
//                }

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
                            $dayTif += $duration / TIMECONVERTER;
//                            $dayTib += $duration / TIMECONVERTER;
                            break;
                        case 'subfab':
                            $dayTisf += $duration / TIMECONVERTER;
//                            $dayTib += $duration / TIMECONVERTER;
                            break;
                        case 'facility':
                            $dayTifac += $duration / TIMECONVERTER;
                            break;
                    }
                    $lastSubOutTs = $ts;
                    $inTime[$category] = null;
                }

                // If Building OUT, close the building container
                if ($category === 'building' && $buildingInStack !== null) {
                    // Add only building-only time (exclude facility, since counted separately)
//                    if ($lastSubOutTs !== null && $lastSubOutTs < $ts) {
//                        $dayTib += ($ts - $lastSubOutTs) / TIMECONVERTER;
//                    } elseif ($lastSubOutTs === null && $buildingInStack < $ts) {
                        $dayTib += ($ts - $buildingInStack) / TIMECONVERTER;
//                    }
                    $buildingInStack = null;
                }
            }
        }

        // Compute totals
        if ($firstInTs !== null && $lastOutTs !== null) {
            $dayTos = round(($lastOutTs - $firstInTs)/ TIMECONVERTER,2);

            // add facility outside building to TIB
            $dayTib = round($dayTib + $dayTifac,2);

            $dayTif   = round($dayTif,2);
            $dayTisf  = round($dayTisf,2);
            $dayTifac = round($dayTifac,2);

            $dayTob = round($dayTos - $dayTib,2);

            $dayVacation = $arr[$user]['vacation'][$keyday] ?? 0;

            $arr[$user]['data'][$keyday] = [
                'tos'      => $dayTos,
                'tib'      => $dayTib,
                'tob'      => $dayTob,
                'tif'      => $dayTif,
                'tisf'     => $dayTisf,
                'tifac'    => $dayTifac,
                'vacation' => $dayVacation,
                'subtotal' => $dayTib + $dayVacation
            ];

            // Accumulate totals
            $totalTos      += $dayTos;
            $totalTib      += $dayTib;
            $totalTob      += $dayTob;
            $totalTif      += $dayTif;
            $totalTisf     += $dayTisf;
            $totalTifac    += $dayTifac;
            $totalVacation += $dayVacation;
            $total_hours   += $arr[$user]['data'][$keyday]['subtotal'];

            $workedDays[$keyday] = true;
            $dow = date('N', strtotime($keyday));
            if ($dow >= 6) $weekendDays[] = $keyday;
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
