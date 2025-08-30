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