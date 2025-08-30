<?php
// JSON/JSON_user_notifications.php?user_id=48
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$arr = array();
$arr['count'] = 0;

if(($GLOBALS['DB_TYPE'] == 'mysql') && isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 && is_numeric($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $querystring = '';
    $db_arr = array();

    $db_pdo = db_connect();

    $querystring='SELECT notification_id, notification.cert_id, user_cert.user_cert_id, notification_sent_date, cert_name, cert_description, cert_days_active FROM tcs.notification JOIN cert ON (cert.cert_id = notification.cert_id AND cert.is_active = 1) JOIN user_cert ON (user_cert.user_cert_id = notification.user_cert_id AND user_cert.is_active = 1) WHERE user_cert.user_id = '.$user_id.';';
    $db_arr = db_query($db_pdo, $querystring);

    $mycount = 0;

    foreach ($db_arr as $key => $data ) {
        $temp_arr = array();
        $temp_arr['notification_id'] = (int)$data['notification_id'];
        $temp_arr['cert_id'] = (int)$data['cert_id'];
        $temp_arr['user_cert_id'] = (int)$data['user_cert_id'];
        $temp_arr['notification_sent_date'] = (int)$data['notification_sent_date'];
        $temp_arr['notification_sent_date_YMD'] = date('Y-m-d H:i:s', $data['notification_sent_date']);
        $temp_arr['cert_name'] = $data['cert_name'];
        $temp_arr['cert_description'] = $data['cert_description'];
        $temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
        $arr['items'][$temp_arr['notification_id']] = $temp_arr;
        unset($temp_arr);
        $arr['count']++;
    }
    // Close connection to DB
    $db_pdo = null;
}

header('Content-Type: application/json');
echo(json_encode($arr));

