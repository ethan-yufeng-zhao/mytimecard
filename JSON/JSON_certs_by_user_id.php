<?php
// JSON/JSON_certs_by_user_id.php?user_id=40
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

// xdebug
//$_GET['user_id'] = 'oliver.li';

if(isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 ){
    $user_id = $_GET['user_id'];

    $querystring='';

    $db_pdo = db_connect();
    if ($GLOBALS['DB_TYPE'] == 'pgsql'){
        //SELECT user_cert.user_cert_date, cert.* FROM tcs.user_cert JOIN cert ON (cert.cert_id = user_cert.cert_id) WHERE user_cert.ad_account = 'oliver.li' order by cert.cert_id, user_cert.user_cert_date desc;
        $querystring='SELECT user_cert.user_cert_date, cert.* FROM tcs.user_cert JOIN cert ON (cert.cert_id = user_cert.cert_id) WHERE user_cert.ad_account = \''.$user_id.'\' order by cert.cert_id, user_cert.user_cert_date;';
        $querystring2 = "SELECT cert_id, proficiency FROM tcs.proficiency_his WHERE ad_account = '$user_id' order by modified_time desc;";
        $querystring3 = "SELECT certs, template_department_number FROM tcs.template WHERE template_department_number = (SELECT departmentnumber::INTEGER FROM tcs.user WHERE ad_account = '$user_id');";
    } else { // mysql
        $querystring='SELECT cert.cert_id, user_cert_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user, user_cert_exception, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.user_cert JOIN cert ON (cert.cert_id = user_cert.cert_id) WHERE `user_cert`.`user_id` = '.$user_id.' AND user_cert.is_active = 1 ORDER BY user_cert.cert_id;';
    }
    $db_arr = db_query($db_pdo, $querystring);

    $proficiency_arr = array();
    $db_arr2 = db_query($db_pdo, $querystring2);
    foreach ($db_arr2 as $k => $v){
        if (!isset($proficiency_arr[$v['cert_id']])) {
            $proficiency_arr[$v['cert_id']] = $v['proficiency'];
        }
    }

    $db_arr3 = db_query($db_pdo, $querystring3);
    $depart_number = $db_arr3[0]['template_department_number'] ?? 1;
    $template_certs_json = $db_arr3[0]['certs'] ?? '[]';
    $template_certs = json_decode($template_certs_json, true);

    foreach ($db_arr as $key => $data ) {
        if ($GLOBALS['DB_TYPE'] == 'pgsql'){
            $cert_id = intval($data['cert_id']);

            $temp_arr = array();
            $temp_arr['user_cert_id'] = $cert_id;
            $temp_arr['proficiency'] = $proficiency_arr[$cert_id] ?? 0.0; //floatval($data['proficiency']);
            $temp_arr['depart_number'] = $depart_number;
            $temp_arr['in_template'] = in_array($cert_id, $template_certs) ? 1 : 0;
            $temp_arr['user_cert_date'] = $data['user_cert_date'];
            $temp_arr['user_cert_date_granted'] = strtotime($data['user_cert_date']);
            $temp_arr['user_cert_date_granted_ymd'] = date('Y-m-d', $temp_arr['user_cert_date_granted']);
            $temp_arr['user_cert_date_set'] = strtotime($data['cert_when_set']);
            $temp_arr['user_cert_date_modified'] = strtotime($data['cert_when_modified']);
            $temp_arr['user_cert_last_user'] = $data['cert_last_user'];

            $temp_arr['user_cert_exception'] = 0; //(int)$data['user_cert_exception'];
            $temp_arr['cert_id'] = $cert_id;
            $temp_arr['cert_name'] = $data['cert_name'];
            $temp_arr['cert_description'] = $data['cert_description'];
            $temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
            $temp_arr['cert_notes'] = $data['cert_notes'];
            $temp_arr['cert_never_expires'] = (int)$data['cert_never_expires'];
            $temp_arr['cert_is_ert'] = (int)$data['cert_is_ert'];
            $temp_arr['cert_is_iso'] = (int)$data['cert_is_iso'];
            $temp_arr['cert_is_safety'] = (int)$data['cert_is_safety'];
            $temp_arr['cert_when_set'] = strtotime($data['cert_when_set']);
            $temp_arr['cert_when_modified'] = strtotime($data['cert_when_modified']);
            $temp_arr['cert_last_user'] = $data['cert_last_user'];
            $temp_arr['entities'] = json_decode($data['entities'] ?? '{}', true);
            $temp_arr['is_active'] = (int)$data['is_active'];
            $temp_arr['cert_points'] = (int)$data['cert_points'];

            if($temp_arr['cert_never_expires'] == 0){
                $temp_arr['calculated_expire'] = $temp_arr['user_cert_date_granted'] + ($temp_arr['cert_days_active']*24*60*60);
                $temp_arr['calculated_expire_ymd'] = date('Y-m-d', $temp_arr['calculated_expire']);
                $temp_arr['calculated_days_until_expire'] = floor(($temp_arr['calculated_expire'] - $current_timestamp) / (24*60*60));
            }

            $arr[$cert_id][$temp_arr['user_cert_date_granted']] = $temp_arr;

            unset($temp_arr);
        } else { // mysql
            // $user_id = intval($data['user_id']);
            // if(!array_key_exists($user_id, $arr)){
            // 	$arr[$user_id]['user_samaccountname'] = $data['user_samaccountname'];
            // 	$arr[$user_id]['user_firstname'] = $data['user_firstname'];
            // 	$arr[$user_id]['user_lastname'] = $data['user_lastname'];
            // 	$arr[$user_id]['user_email'] = $data['user_email'];
            // 	$arr[$user_id]['user_is_admin'] = (int)$data['user_is_admin'];
            // }
            $cert_id = intval($data['cert_id']);
            $temp_arr = array();
            $temp_arr['user_cert_id'] = (int)$data['user_cert_id'];
            $temp_arr['user_cert_date_granted'] = (int)$data['user_cert_date_granted'];
            $temp_arr['user_cert_date_granted_ymd'] = date('Y-m-d', $data['user_cert_date_granted']);
            $temp_arr['user_cert_date_set'] = (int)$data['user_cert_date_set'];
            $temp_arr['user_cert_date_modified'] = (int)$data['user_cert_date_modified'];
            $temp_arr['user_cert_last_user'] = $data['user_cert_last_user'];

            $temp_arr['user_cert_exception'] = (int)$data['user_cert_exception'];
            //$temp_arr['user_samaccountname'] = $data['user_samaccountname'];
            //$temp_arr['user_firstname'] = $data['user_firstname'];
            //$temp_arr['user_lastname'] = $data['user_lastname'];
            //$temp_arr['user_email'] = $data['user_email'];
            $temp_arr['cert_id'] = intval($data['cert_id']);
            $temp_arr['cert_name'] = $data['cert_name'];
            $temp_arr['cert_description'] = $data['cert_description'];
            $temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
            $temp_arr['cert_notes'] = $data['cert_notes'];
            $temp_arr['cert_never_expires'] = (int)$data['cert_never_expires'];
            $temp_arr['cert_is_ert'] = (int)$data['cert_is_ert'];
            $temp_arr['cert_is_safety'] = (int)$data['cert_is_safety'];
            $temp_arr['cert_when_set'] = (int)$data['cert_when_set'];
            $temp_arr['cert_when_modified'] = (int)$data['cert_when_modified'];
            $temp_arr['cert_last_user'] = $data['cert_last_user'];
            if($temp_arr['cert_never_expires'] == 0){
                $temp_arr['calculated_expire'] = $temp_arr['user_cert_date_granted'] + ($temp_arr['cert_days_active']*24*60*60);
                $temp_arr['calculated_expire_ymd'] = date('Y-m-d', $temp_arr['calculated_expire']);
                $temp_arr['calculated_days_until_expire'] = floor(($temp_arr['calculated_expire'] - $current_timestamp) / (24*60*60));
            }
            $arr[$cert_id][intval($data['user_cert_date_granted'])] = $temp_arr;
            unset($temp_arr);
        }
    }
    // Close connection to DB
    $db_pdo = null;
}

header('Content-Type: application/json');
echo(json_encode($arr));
