<?php
    //error reporting
    error_reporting(E_ALL|E_STRICT);
    ini_set("display_errors", "on");

    date_default_timezone_set('America/Los_Angeles');

    // parse_xlsx/JSON_xlsx_check_parse_user_names.php




    $user_json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users.php'), true);


    $arr = array();

    $json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parse_grants.php'), true);
    foreach ($json['row'] as $row_value) {
        $search_is_dry = true;
        foreach ($user_json as $user_id => $user_value) {
            if(strtolower($user_value['user_firstname']) == strtolower($row_value['first_name'])
                && strtolower($user_value['user_lastname']) == strtolower($row_value['last_name'])
            ) {
                $search_is_dry = false;
            }
        }
        if($search_is_dry) {

            // $arr[] = array('first_name' => $row_value['first_name'], 'last_name' => $row_value['last_name']);

            $arr[] = $row_value['first_name'] . ' ' . $row_value['last_name'];



        }
    }
    $arr = array_unique($arr);
    sort($arr);

    header('Content-Type: application/json');
    echo(json_encode($arr));











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