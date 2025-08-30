<?php
    // JSON/JSON_ACTION_add_user_cert.php?add_user_cert=1&cert_id=39&add_user_cert_cert_name=TRH-0007&add_user_cert_username=bmcgucki&user_id=153&user_cert_last_user=dmooney&user_cert_date_granted=10%2F18%2F2013
    // JSON/JSON_ACTION_add_user_cert.php?add_user_cert=1&cert_id=6&add_user_cert_cert_name=BiMedEval&add_user_cert_username=jcubic&user_id=1&user_cert_last_user=jcubic&user_cert_date_granted=10%2F10%2F2013
    // JSON/JSON_ACTION_add_user_cert.php?cert_id=11&user_id=438&user_cert_last_user=jcubic&user_cert_date_granted=2023-05-05
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    $arr = array();

    if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && (int)$_GET['cert_id'] > 0
        && isset($_GET['user_id']) && strlen($_GET['user_id']) > 0
        && isset($_GET['user_cert_date_granted']) && strlen($_GET['user_cert_date_granted']) > 0
        && isset($_GET['user_cert_last_user']) && strlen($_GET['user_cert_last_user']) > 0) {

        $cert_id = $_GET['cert_id'];
        $user_id = $_GET['user_id'];
        $user_cert_date_granted = strtotime($_GET['user_cert_date_granted']);
        $current_time = time();
        $user_cert_date_set = $current_time;
        $user_cert_date_modified = $current_time;
        $user_cert_last_user = $_GET['user_cert_last_user'];

        $user_cert_exception = 0;

        if(isset($_GET['user_cert_exception']) && strtolower($_GET['user_cert_exception']) == 'on') {
            $user_cert_exception = 1;
        }

        $querystring = '';
        $insertstring = '';
        $db_pdo = db_connect();
        $count_arr = array();

        if ($GLOBALS['DB_TYPE'] == 'pgsql') {
            $querystring = 'SELECT COUNT(*) AS "mycount" FROM tcs.user_cert WHERE ad_account = \''.$user_id.'\' AND cert_id = '.$cert_id.' AND user_cert_date = \''.date('Y-m-d H:i:s', $user_cert_date_granted).'\';';
            $count_arr = db_query($db_pdo, $querystring);

            if ($count_arr[0]['mycount'] < 1) {
                $querystring = 'SELECT ws_account FROM tcs.user WHERE ad_account = \''.$user_id.'\';';
                $ws_account_arr = db_query($db_pdo, $querystring);
                $ws_account = '';
                if ($ws_account_arr != null && $ws_account_arr[0]['ws_account'] != null && $ws_account_arr[0]['ws_account'] != ''){
                    $ws_account = $ws_account_arr[0]['ws_account'];
                } else {
                    $max_retries = 3;
                    for ($i = 0; $i < $max_retries; $i++) {
                        sleep(5);
                        $ws_account_arr = db_query($db_pdo, $querystring);
                        if ($ws_account_arr != null && $ws_account_arr[0]['ws_account'] != null && $ws_account_arr[0]['ws_account'] != '') {
                            $ws_account = $ws_account_arr[0]['ws_account'];
                            break;
                        }
                    }
                }

                $insertstring = 'INSERT INTO tcs.user_cert ';
                $insertstring .= ' (cert_id, ad_account, user_cert_date, ws_account, user_cert_update_user, user_cert_exception) ';
                $insertstring .= ' VALUES ( ';
                $insertstring .= $cert_id.' , ';
                $insertstring .= '\''.$user_id.'\' , ';
                $insertstring .= '\''.date('Y-m-d H:i:s',$user_cert_date_granted).'\' , ';
                if ($ws_account == '') {
                    $insertstring .= ' NULL , ';
                } else {
                    $insertstring .= '\''.$ws_account.'\' , ';
                }
                $insertstring .= '\''.$user_cert_last_user.'\' , ';
                $insertstring .= ($user_cert_exception == 1 ? "true" : "false").");";

                if (db_insert($db_pdo, $insertstring)) {
                    $arr['success'] = true;
                } else {
                    $arr['success'] = false;
                    $arr['error'] = 'Database execute failed';
                }
            } else {
                $arr['success'] = false;
                $arr['error'] = 'user_cert already in database - cert_id: '.$cert_id.' - user_id: '.$user_id.' - user_cert_date_granted: '.$user_cert_date_granted;
            }
        } else {
            $querystring = 'SELECT COUNT(*) AS "mycount" FROM tcs.user_cert WHERE is_active = 1 AND user_id = '.$user_id.' AND cert_id = '.$cert_id.' AND user_cert_date_granted = '.$user_cert_date_granted.';';
            $count_arr = db_query($db_pdo, $querystring);

            if ($count_arr[0]['mycount'] < 1) {
                $insertstring = 'INSERT INTO tcs.user_cert ';
                $insertstring .= ' (cert_id, user_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user, user_cert_exception) ';
                $insertstring .= ' VALUES ( ';
                $insertstring .= $cert_id.' , ';
                $insertstring .= $user_id.' , ';
                $insertstring .= $user_cert_date_granted.' , ';
                $insertstring .= $user_cert_date_set.' , ';
                $insertstring .= $user_cert_date_modified.' , ';
                $insertstring .= '\''.$user_cert_last_user.'\' , ';
                $insertstring .= $user_cert_exception.' );';

                if (db_insert($db_pdo, $insertstring)) {
                    $arr['success'] = true;
                } else {
                    $arr['success'] = false;
                    $arr['error'] = 'Database execute failed';
                }
            } else {
                $arr['success'] = false;
                $arr['error'] = 'user_cert already in database - cert_id: '.$cert_id.' - user_id: '.$user_id.' - user_cert_date_granted: '.$user_cert_date_granted;
            }
        }

        // Close connection to DB
        $db_pdo = null;
    } else {
        $arr['success'] = false;
        $arr['error'] = 'invalid POST values passed for user cert update';
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));
