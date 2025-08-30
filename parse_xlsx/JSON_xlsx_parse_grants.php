<?php
    //error reporting
    error_reporting(E_ALL|E_STRICT);
    ini_set("display_errors", "on");

    date_default_timezone_set('America/Los_Angeles');

    // parse_xlsx/JSON_xlsx_parse_grants.php

    require_once('./PHPExcel/Classes/PHPExcel.php');


    $objReader = new PHPExcel_Reader_Excel2007();
    $objReader->setReadDataOnly(true);
    $objPHPExcel = $objReader->load('all_jfab_granted_certs_20131024_1700.xlsx');
    $objPHPExcel->setActiveSheetIndex(0);


    $running_row = 2;


    while(strlen(trim($objPHPExcel->getActiveSheet()->getCell('C'.$running_row))) > 0) { //Row must be populated
        $temp_arr = array();
        $temp_arr['first_name'] = trim($objPHPExcel->getActiveSheet()->getCell('B'.$running_row));
        $temp_arr['last_name'] = trim($objPHPExcel->getActiveSheet()->getCell('A'.$running_row));
        $temp_arr['cert_name'] = trim($objPHPExcel->getActiveSheet()->getCell('C'.$running_row));
        $temp_arr['grant_date'] = PHPExcel_Style_NumberFormat::toFormattedString(trim($objPHPExcel->getActiveSheet()->getCell('E'.$running_row)), "YYYY-M-D");
        if(strlen($temp_arr['grant_date']) > 0) {
            $temp_arr['grant_date_timestamp'] = strtotime($temp_arr['grant_date']);
        } else {
            $temp_arr['grant_date_timestamp'] = '';
        }
        $xlsx['row'][] = $temp_arr;
        unset($temp_arr);
        $running_row++;
    }

    header('Content-Type: application/json');
    echo(json_encode($xlsx));






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