<?php
    // parse_xlsx/JSON_xlsx_parse_certs.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('./PHPExcel/Classes/PHPExcel.php');

    // $json = json_decode(file_get_contents(request_ldap_api("/JSON_list_jfab_users.php")), true);
    // $email_to_sam_arr = array();
    // foreach($json['items'] as $value){
    //     if(isset($value['mail']) && strlen(trim($value['mail'])) > 0){
    //         $email_to_sam_arr[trim($value['mail'])] = trim($value['samaccountname']);
    //     }
    // }

    $objReader = new PHPExcel_Reader_Excel2007();
    $objReader->setReadDataOnly(true);
    try {
        $objPHPExcel = $objReader->load('certs_102113_1700.xlsx');
    } catch (Exception $e){
        echo($e->getMessage());
    }

    $objPHPExcel->setActiveSheetIndex(0);

    $running_row = 2;

    while(strlen(trim($objPHPExcel->getActiveSheet()->getCell('B'.$running_row))) > 0) { //Row must be populated
        $temp_arr = array();
        $temp_arr['cert_description'] = trim($objPHPExcel->getActiveSheet()->getCell('A'.$running_row));
        $temp_arr['cert_name'] = trim($objPHPExcel->getActiveSheet()->getCell('B'.$running_row));
        $temp_arr['cert_days_active'] = trim($objPHPExcel->getActiveSheet()->getCell('C'.$running_row));
        $temp_arr['category'] = trim($objPHPExcel->getActiveSheet()->getCell('D'.$running_row));
        $xlsx['row'][] = $temp_arr;
        unset($temp_arr);
        $running_row++;
    }

    // header('Content-Type: application/json');
    // echo(json_encode($xlsx));
    // exit();

    $arr = array();
    $arr['certs_check_problem_list'] = array();
    $arr['certs_check_problem_list']['cert_days_active'] = array();
    $arr['certs_check_problem_list']['category'] = array();
    $arr['certs'] = array();
    foreach ($xlsx['row'] as $value) {
        $temp_arr = array();
        $temp_arr['cert_name'] = $value['cert_name'];
        $temp_arr['cert_description'] = $value['cert_description'];
        if(!is_numeric($value['cert_days_active'])) {
            $arr['certs_check_problem_list']['cert_days_active'][] = $temp_arr['cert_name'] . ' Has an unusual cert_days_active';
        }
        $temp_arr['cert_days_active'] = $value['cert_days_active'];
        if ($temp_arr['cert_days_active'] >= 18250) {
            $temp_arr['cert_never_expires'] = 'on';
        } else {
            $temp_arr['cert_never_expires'] = '';
        }

        // $temp_arr['category'] = $value['category'];
        $temp_arr['cert_is_iso'] = '';
        $temp_arr['cert_is_safety'] = '';
        switch ($value['category']) {
            case 'ISO':
                $temp_arr['cert_is_iso'] = 'on';
                break;
            case 'Regulatory':
                $temp_arr['cert_is_safety'] = 'on';
                break;
            default:
                if (strlen($value['category']) > 0) {
                    $arr['certs_check_problem_list']['category'][] = $temp_arr['cert_name'] . ' Has an unusual category';
                }
                break;
        }

        if ($temp_arr['cert_name'] == 'EnvrAwareISO') {
            $temp_arr['cert_is_iso'] = 'on';
            $temp_arr['cert_is_safety'] = 'on';
        }

        $arr['certs'][$temp_arr['cert_name']] = $temp_arr;
        unset($temp_arr);
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));
