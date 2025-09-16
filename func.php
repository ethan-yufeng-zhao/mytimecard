<?php

function convertTime($current_time=null, $nodate=false, $iso=false, $millis=0, $tz='short'){
    if ($current_time === null) {
        $current_time = new DateTime();
    }
    if ($nodate) {
        $result = $current_time->format('H:i:s');
    } else {
        $result = $current_time->format('Y-m-d');
        if ($iso) {
            $result .= 'T';
        } else {
            $result .= ' ';
        }
        $result .= $current_time->format('H:i:s');
    }
    if ($millis == 6) {
        $result .= '.' . $current_time->format('u');
    } elseif ($millis == 3) {
        $result .= '.' . sprintf('%03d', (int)($current_time->format('u') / 1000));
    } else {
        $result .= '';
    }
    if ($tz === 'long') {
        $result .= '[' . $current_time->format('P') . ']';
    } elseif ($tz === 'short') {
        $result .= '[' . (int)$current_time->format('P') . ']';
    } else {
        $result .= '';
    }

    return $result;
}

function convertUnixTime($unixTimestamp) {
    $date = new DateTime();
    $date->setTimestamp($unixTimestamp);
    $date->setTimezone(new DateTimeZone(date_default_timezone_get()));

    // Format the date with milliseconds and timezone offset
    $formattedDate = convertTime($date);

    return $formattedDate;
}

function extractSubdomainOrIP($fullDomain) {
    // Check if the input is a valid IP address
    if (filter_var($fullDomain, FILTER_VALIDATE_IP)) {
        return $fullDomain; // Return the IP address as is
    }

    // Split the domain by '.'
    $parts = explode('.', $fullDomain);

    // Get the first part (subdomain)
    return $parts[0]; // Return the subdomain
}

//Make this support adding query stuff to base_url already containing query stuff
function build_url($base_url, $query_arr = array()) {
    $base = true;
    if (preg_match('/\?/',$base_url)) {
        $base = false;
    }
    $return_val = $base_url;
    //echo nl2br("base_url=$base_url ".print_r($query_arr,true)."  \r\n");
    if (count((array) $query_arr) > 0) {
        $return_val .= ($base ? '?' : '&') . http_build_query($query_arr);
    }
    return $return_val;
}

function get_json($base_url, $query_arr = array(), $context_options = null) {
    $start_time = time();
    $json_url = (!empty($query_arr)) ? build_url($base_url, $query_arr) : $base_url;
    logit(nl2br("url=$json_url  \r\n"));

    // Ensure context options have default settings
    if ($context_options === null) {
        $context = null;
    } else {
        if (!isset($context_options['http']['method'])) {
            $context_options['http']['method'] = 'POST';
        }
//        if (!isset($context_options['http']['timeout'])) {
//            $context_options['http']['timeout'] = POST_TIMEOUT; // 120s=2min let server decide
//        }
        $context = stream_context_create($context_options);
    }

    try {
        logit(nl2br("start_time=$start_time json_url=$json_url  \r\n"));

        $raw_file_data = @file_get_contents($json_url, false, $context); // @ to suspend warnings if timeout
        if ($raw_file_data === false) {
            $error = error_get_last();
            logit(nl2br("Failed to fetch JSON data. Error: " . ($error['message'] ?? 'Unknown error') . "\r\n"));
            logit(nl2br('POST = ' . print_r($context_options, true) . "\r\n"));
            return [];
        }

//        if (DEBUG) {
        logit(print_r($raw_file_data, true));
//        }

        // Handle JSONP format
        if (substr($raw_file_data, 0, 6) === 'jsonp(') {
            $raw_file_data = ltrim($raw_file_data, "jsonp(");
            $raw_file_data = rtrim($raw_file_data, ");");
        }

        $time_json_request_took = time() - $start_time;
        logit(nl2br($time_json_request_took . 's - ' . $json_url . "  \r\n"));

        $decoded_data = json_decode($raw_file_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logit(nl2br("JSON decode error: " . json_last_error_msg() . "\r\n"));
            return [];
        }

        return $decoded_data;
    } catch (Exception $e) {
        logit(nl2br('Exception occurred while fetching JSON data from URL: ' . $json_url . "\r\n" . 'Exception: ' . $e->getMessage() . "\r\n"));
    }

    return [];
}

// Define holidays for multiple years
const HOLIDAYS = [
    2023 => [
        '2023-01-02', // New Year's Day (observed)
        '2023-01-16', // MLK Day
        '2023-02-20', // Presidents' Day
        '2023-05-29', // Memorial Day
        '2023-06-19', // Juneteenth
        '2023-07-04', // Independence Day
        '2023-09-04', // Labor Day
        '2023-10-09', // Columbus Day
        '2023-11-10', // Veterans Day (observed)
        '2023-11-23', // Thanksgiving
        '2023-12-25', // Christmas
    ],
    2024 => [
        '2024-01-01',
        '2024-01-15',
        '2024-02-19',
        '2024-05-27',
        '2024-06-19',
        '2024-07-04',
        '2024-09-02',
        '2024-10-14',
        '2024-11-11',
        '2024-11-28',
        '2024-12-25',
    ],
    2025 => [
        '2025-01-01',
        '2025-01-20',
        '2025-02-17',
        '2025-05-26',
        '2025-06-19',
        '2025-07-04',
        '2025-09-01',
        '2025-10-13',
        '2025-11-11',
        '2025-11-27',
        '2025-12-25',
    ],
    2026 => [
        '2026-01-01',
        '2026-01-19',
        '2026-02-16',
        '2026-05-25',
        '2026-06-19',
        '2026-07-03', // Independence Day observed
        '2026-09-07',
        '2026-10-12',
        '2026-11-11',
        '2026-11-26',
        '2026-12-25',
    ],
];

function getWorkdays($start_time, $end_time) {
    $start = new DateTime($start_time);
    $end   = new DateTime($end_time);
    $end->setTime(23, 59, 59);

    $workdaysList = [];
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    foreach ($period as $date) {
        $weekday = $date->format('N'); // 1=Mon .. 7=Sun
        $datestr = $date->format('Y-m-d');
        $year    = (int)$date->format('Y');

        $holidays = HOLIDAYS[$year] ?? [];

        if ($weekday < 6 && !in_array($datestr, $holidays)) {
            $workdaysList[] = $datestr;
        }
    }

    return $workdaysList;
}

const SNP = [
//    "Admin Roof Stairwell" => "Admin Roof Stairwell",
//    "Archive Room" => "Archive Room",
//    "Arsenic Door" => "Arsenic Door",
    "BGBM - In" => "MainFab In",
    "BGBM - Out" => "MainFab Out",
//    "Computer Room" => "Computer Room",
    "East Entrance - New Admin (120A)" => "Facility In",
    "East Entry In" => "Building In",
    "East Entry Out" => "Building Out",
    "East Lobby In" => "Building In",
    "East Lobby Out" => "Building Out",
//    "East Lobby - North Hall Door" => "East Lobby North",
//    "East Lobby - West Hall Door" => "East Lobby West",
    "Employee Entrance - In" => "Building In",
    "Employee Entrance - Out" => "Building Out",
    "ERT Muster Out" => "Building Out",
//    "ERT Room" => "ERT Room",
    "Facility Shop - New Admin (113B)" => "Facility In",
//    "FA Lab" => "FA Lab",
    "Fitness Room - New Admin (109)" => "Facility In",
//    "IDF East - New Admin (114A)" => "IDF East",
//    "IDF North - New Admin (115)" => "IDF North",
//    "IDF Room 2.1" => "IDF Room",
//    "IDF Room 2.2" => "IDF Room",
//    "Library" => "Library",
//    "MDF 1.1 Room" => "MDF Room",
//    "New Shipping/Receiving - In" => "Shipping/Receiving In",
    "New Sort Gown Room - In" => "MainFab In",
    "New Sort Gown Room - Out" => "MainFab Out",
    "Parking Lot Muster" => "Building Out",
//    "SEM Room" => "SEM Room",
//    "Temperature Test" => "Temperature Test",
    "Wafer Storage - New Admin (112A)" => "Facility In",
    "West Entrance - New Admin (101A)" => "Facility In",
    "Workshop - New Admin (110A)" => "Facility In",
];

// Function to normalize
function normalizeSourceName($raw) {
    if (empty($raw)) return "";

    // Group "Main Fab ..."
    if (preg_match('/^Main Fab.*In$/i', $raw)) return "MainFab In";
    if (preg_match('/^Main Fab.*Out$/i', $raw)) return "MainFab Out";

    // Group "SubFab ..."
    if (preg_match('/^SubFab.*In$/i', $raw)) return "SubFab In";
    if (preg_match('/^SubFab.*Out$/i', $raw)) return "SubFab Out";

    // Group "IDF Room ... (SubFab)"
    if (preg_match('/^IDF Room .*SubFab/i', $raw)) return "SubFab In";

    // Exact match fallback
    return SNP[$raw] ?? '';
}

if (!function_exists('str_ends_with')) {
    /**
     * Checks if a string ends with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for at the end of $haystack.
     * @return bool True if $haystack ends with $needle, false otherwise.
     */
    function str_ends_with(string $haystack, string $needle): bool
    {
        $needle_len = strlen($needle);

        // An empty needle is always a match
        if ($needle_len === 0) {
            return true;
        }

        // The needle cannot be longer than the haystack
        if (strlen($haystack) < $needle_len) {
            return false;
        }

        return substr($haystack, -$needle_len) === $needle;
    }
}

if (!function_exists('str_starts_with')) {
    /**
     * Checks if a string starts with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for at the beginning of $haystack.
     * @return bool True if $haystack starts with $needle, false otherwise.
     */
    function str_starts_with(string $haystack, string $needle): bool
    {
        $needle_len = strlen($needle);

        // An empty needle is always a match
        if ($needle_len === 0) {
            return true;
        }

        // The needle cannot be longer than the haystack
        if (strlen($haystack) < $needle_len) {
            return false;
        }

        return substr($haystack, 0, $needle_len) === $needle;
    }
}

/**
 * Convert a PHP array into a PostgreSQL IN (...) list.
 *
 * @param array $arr  Array of values (strings or numbers).
 * @return string     A string like: ('alice','bob','charlie') or (1,2,3)
 */
function arrayToPgInList(array $arr): string {
    if (empty($arr)) {
        return "(NULL)"; // safe fallback, will never match
    }

    $escaped = array_map(function($item) {
        if (is_numeric($item)) {
            return $item; // numbers stay unquoted
        }
        return "'" . str_replace("'", "''", $item) . "'"; // escape single quotes
    }, $arr);

    return "(" . implode(",", $escaped) . ")";
}

function buildQueryUrl($baseUrl, $user, $mode, $start, $end, $range = 'custom') {
    $params = [
        'uid'   => $user,
        'mode'  => $mode,
        'start' => $start,
        'end'   => $end,
        'quickRange' => $range,
    ];
    return $baseUrl . '?' . http_build_query($params);
}