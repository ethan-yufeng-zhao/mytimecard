<?php
    // parse_xlsx/JSON_xlsx_parse_normal_templates.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('./PHPExcel/Classes/PHPExcel.php');

    $objReader = new PHPExcel_Reader_Excel2007();
    $objReader->setReadDataOnly(true);
    try{
        $objPHPExcel = $objReader->load('tcs_template_20131028.xlsx');
    } catch (Exception $e){
        echo($e->getMessage());
    }

    $objPHPExcel->setActiveSheetIndex(1);

    $cert_arr = array();
    $json_all_certs = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php'), false, getContextCookies()), true);
    foreach ($json_all_certs['items'] as $cert_id => $value) {
        $cert_arr[$value['cert_name']] = $cert_id;
    }

    $dept_arr = array();
    $json_all_templates = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_templates.php'), false, getContextCookies()), true);
    foreach ($json_all_templates as $template_id => $value) {
        if($value['template_is_default_for_department'] == 0) {
            $temp_name_arr[$value['template_name']] = $template_id;
        }
    }

    $arr = array();
    $arr['template_check_problem_list'] = array();
    $arr['template_check_problem_list']['cert'] = array();
    $arr['template_check_problem_list']['template_name'] = array();

    $running_row = 2;

    while(strlen(trim($objPHPExcel->getActiveSheet()->getCell('C'.$running_row))) > 0) { //Row must be populated
        $temp_arr = array();
        $temp_arr['cert_name'] = trim($objPHPExcel->getActiveSheet()->getCell('C'.$running_row));
        $temp_arr['template_name'] = trim($objPHPExcel->getActiveSheet()->getCell('A'.$running_row));

        if (array_key_exists($temp_arr['cert_name'], $cert_arr)) {
            $temp_arr['cert_id'] = $cert_arr[$temp_arr['cert_name']];
        } else {
            $arr['template_check_problem_list']['cert'][] = 'Cert ' . $temp_arr['cert_name'] . 'Not Found';
            $temp_arr['cert_id'] = 0;
        }

        if (array_key_exists($temp_arr['template_name'], $temp_name_arr)) {
            $temp_arr['template_id'] = $temp_name_arr[$temp_arr['template_name']];
        } else {
            $arr['template_check_problem_list']['template_name'][] = 'template name ' . $temp_arr['template_name'] . 'Not Found';
            $temp_arr['template_id'] = 0;
        }

        $arr['items'][] = $temp_arr;
        unset($temp_arr);
        $running_row++;
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));
    exit();

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