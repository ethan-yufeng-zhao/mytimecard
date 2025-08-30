<?php
// parse_xlsx/JSON_ACTION_import_grants.php

//error reporting
error_reporting(E_ALL|E_STRICT);
ini_set("display_errors", "on");

date_default_timezone_set('America/Los_Angeles');

$current_time = time();


$midnight = date('Y-m-d', $current_time);


$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php')), true);
$cert_arr = array();
foreach ($json['items'] as $cert_id => $value) {
    // $cert_arr[$cert_id] = $value['cert_name'];
    $cert_arr[$value['cert_name']] = $cert_id;
}
unset($json);


$user_json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users.php'), true);


$arr = array();

$json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parse_grants.php'), true);
foreach ($json['row'] as $row_value) {
    $get_fields = array();
    $found_user = false;

    foreach ($user_json as $user_id => $user_value) {
        if(strtolower($user_value['user_firstname']) == strtolower($row_value['first_name'])
            && strtolower($user_value['user_lastname']) == strtolower($row_value['last_name'])
        ) {
            $found_user = true;
            $get_fields['user_id'] = $user_value['user_id'];
            // echo('made it');
        }
    }
    if(!$found_user) {
        echo('ERROR: unable to find user: ' . $row_value['first_name'] . ' ' . $row_value['last_name']);
    }

    $found_cert = false;

    if(array_key_exists($row_value['cert_name'], $cert_arr)) {
        $found_cert = true;
        $get_fields['cert_id'] = $cert_arr[$row_value['cert_name']];
    } else {
        echo('ERROR: Unable to find cert: ' . $row_value['cert_name']);
    }

    if ($found_cert && $found_user) { // do import
        if (isset($row_value['grant_date']) && strlen($row_value['grant_date']) > 0) {
            $get_fields['user_cert_date_granted'] = $row_value['grant_date'];
        } else {
            $get_fields['user_cert_date_granted'] = $midnight;
        }

        $get_fields['user_cert_date_set'] = $current_time;
        $get_fields['user_cert_date_modified'] = $current_time;
        $get_fields['user_cert_last_user'] = 'auto';


        $json_add_user_cert = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user_cert.php?'.http_build_query($get_fields)), true);
        if (isset($json_add_user_cert['success']) && $json_add_user_cert['success'] == true) {
            echo('<div class="alert alert-success">');
            echo('<strong>Success:</strong> User Cert has been added.');
            echo('</div>');
        } else {
            echo('<div class="alert alert-danger">');
            echo('<p><strong>Error:</strong> User Cert was unable to be added.</p>');
            if(isset($json_add_user_cert['error'])) {
                echo('<p style="margin-left:2em;">'.$json_add_user_cert['error'].'</p>');
            } else {
                echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
            }
            echo('</div>');
        }
        unset($json_add_user_cert);
        unset($get_fields);



    }
}