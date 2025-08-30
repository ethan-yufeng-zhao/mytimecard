<?php
    //error reporting
    error_reporting(E_ALL|E_STRICT);
    ini_set("display_errors", "on");

    date_default_timezone_set('America/Los_Angeles');

    // parse_xlsx/JSON_xlsx_check_parse_certs_names.php




    $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php')), true);
    $cert_arr = array();
    foreach ($json['items'] as $cert_id => $value) {
        // $cert_arr[$cert_id] = $value['cert_name'];
        $cert_arr[$value['cert_name']] = $cert_id;
    }
    unset($json);

    $bad_certs = array();

    $json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parse_grants.php')), true);
    foreach ($json['row'] as $value) {
        if(!array_key_exists($value['cert_name'], $cert_arr)) {
            // var_dump($value);
            $bad_certs[] = $value['cert_name'];
            // echo($value['cert_name']);
            // echo('<br>');
        }
    }

    $bad_certs = array_unique($bad_certs);
    sort($bad_certs);

    header('Content-Type: application/json');
    echo(json_encode($bad_certs));











    // $arr = array();
    // $arr['certs_check_problem_list'] = array();
    // $arr['certs_check_problem_list']['cert_days_active'] = array();
    // $arr['certs_check_problem_list']['category'] = array();
    // $arr['certs'] = array();
    // foreach ($xlsx['row'] as $value) {
    //     $temp_arr = array();
    //     $temp_arr['cert_name'] = $value['cert_name'];
    //     $temp_arr['cert_description'] = $value['cert_description'];
    //     if(!is_numeric($value['cert_days_active'])) {
    //         $arr['certs_check_problem_list']['cert_days_active'][] = $temp_arr['cert_name'] . ' Has an unusual cert_days_active';
    //     }
    //     $temp_arr['cert_days_active'] = $value['cert_days_active'];
    //     if ($temp_arr['cert_days_active'] >= 18250) {
    //         $temp_arr['cert_never_expires'] = 'on';
    //     } else {
    //         $temp_arr['cert_never_expires'] = '';
    //     }




    //     // $temp_arr['category'] = $value['category'];
    //     $temp_arr['cert_is_iso'] = '';
    //     $temp_arr['cert_is_safety'] = '';
    //     switch ($value['category']) {
    //         case 'ISO':
    //             $temp_arr['cert_is_iso'] = 'on';
    //             break;
    //         case 'Regulatory':
    //             $temp_arr['cert_is_safety'] = 'on';
    //             break;
    //         default:
    //             if (strlen($value['category']) > 0) {
    //                 $arr['certs_check_problem_list']['category'][] = $temp_arr['cert_name'] . ' Has an unusual category';
    //             }
    //             break;
    //     }

    //     if ($temp_arr['cert_name'] == 'EnvrAwareISO') {
    //         $temp_arr['cert_is_iso'] = 'on';
    //         $temp_arr['cert_is_safety'] = 'on';
    //     }


    //     $arr['certs'][$temp_arr['cert_name']] = $temp_arr;
    //     unset($temp_arr);
    // }

    // header('Content-Type: application/json');
    // echo(json_encode($arr));