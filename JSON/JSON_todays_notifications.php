<?php
    // JSON/JSON_todays_notifications.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    //$notification_sent_date =strtotime('2010-3-1');//for xdebug
    $notification_sent_date = strtotime(date('Y-m-d'));

    $arr = array();
    $arr['items'] = array();

    $querystring = '';
    $db_arr = array();

    $db_pdo = db_connect();

    $querystring='SELECT notification_id, cert_id, user_cert_id, notification_sent_date FROM tcs.notification WHERE notification_sent_date >= '.$notification_sent_date.' ORDER BY notification_sent_date ASC;';
    $db_arr = db_query($db_pdo, $querystring);

    $mycount = 0;

    foreach ($db_arr as $key => $data ) {
        $temp_arr = array();
        $temp_arr['notification_id'] = (int)$data['notification_id'];
        $temp_arr['cert_id'] = (int)$data['cert_id'];
        $temp_arr['user_cert_id'] = (int)$data['user_cert_id'];
        $temp_arr['notification_sent_date'] = (int)$data['notification_sent_date'];
        $temp_arr['notification_sent_date_YMD'] = date('Y-m-d H:i:s', $data['notification_sent_date']);
        $arr['items'][$temp_arr['user_cert_id']] = $temp_arr;
        unset($temp_arr);
    }
    // Close connection to DB
    $db_pdo = null;

    header('Content-Type: application/json');
    echo(json_encode($arr));
