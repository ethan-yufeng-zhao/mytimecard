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