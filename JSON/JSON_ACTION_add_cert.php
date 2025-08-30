<?php
    // JSON/JSON_ACTION_add_cert.php // Requires POST data to return a value
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    $arr = array();
    $current_time = time();

    if (isset($_POST['cert_name'])
        && strlen($_POST['cert_name']) > 0
        && ((isset($_POST['cert_days_active'])
            && strlen($_POST['cert_days_active']) > 0
            && is_numeric($_POST['cert_days_active'])
            && $_POST['cert_days_active'] > 0)
        || (isset($_POST['cert_never_expires'])
            && strtolower($_POST['cert_never_expires']) == 'on'))
        && isset($_POST['cert_description'])
        && isset($_POST['cert_notes'])
    ) {
        if(isset($_POST['cert_never_expires']) && strtolower($_POST['cert_never_expires']) == 'on'){
            $cert_never_expires = 1;
        } else {
            $cert_never_expires = 0;
        }
        if(isset($_POST['cert_is_ert']) && strtolower($_POST['cert_is_ert']) == 'on'){
            $cert_is_ert = 1;
        } else {
            $cert_is_ert = 0;
        }
        if(isset($_POST['cert_is_iso']) && strtolower($_POST['cert_is_iso']) == 'on'){
            $cert_is_iso = 1;
        } else {
            $cert_is_iso = 0;
        }
        if(isset($_POST['cert_is_safety']) && strtolower($_POST['cert_is_safety']) == 'on'){
            $cert_is_safety = 1;
        } else {
            $cert_is_safety = 0;
        }
        $cert_name = $_POST['cert_name'];
        if($cert_never_expires == 1 || $_POST['cert_days_active'] > 18250) {
            $cert_days_active = 18250;
        } else {
            $cert_days_active = $_POST['cert_days_active'];
        }
        $cert_description = $_POST['cert_description'];
        $cert_notes = $_POST['cert_notes'];
        $cert_when_set = $current_time;
        $cert_when_modified = $current_time;
        $cert_last_user = $_POST['cert_last_user'];
        $cert_points = $_POST['cert_points'];
        $parameters_found = true;
    } elseif (isset($_GET['cert_name'])
        && strlen($_GET['cert_name']) > 0
        && isset($_GET['cert_description'])
        // && isset($_GET['cert_notes'])
        && (
            (isset($_GET['cert_days_active'])
                && strlen($_GET['cert_days_active']) > 0
                && is_numeric($_GET['cert_days_active'])
                && $_GET['cert_days_active'] > 0)
            || (isset($_GET['cert_never_expires'])
                && strtolower($_GET['cert_never_expires']) == 'on')
            )
    ) {
        if(isset($_GET['cert_never_expires']) && strtolower($_GET['cert_never_expires']) == 'on'){
            $cert_never_expires = 1;
        } else {
            $cert_never_expires = 0;
        }
        if(isset($_GET['cert_is_ert']) && strtolower($_GET['cert_is_ert']) == 'on'){
            $cert_is_ert = 1;
        } else {
            $cert_is_ert = 0;
        }
        if(isset($_GET['cert_is_iso']) && strtolower($_GET['cert_is_iso']) == 'on'){
            $cert_is_iso = 1;
        } else {
            $cert_is_iso = 0;
        }
        if(isset($_GET['cert_is_safety']) && strtolower($_GET['cert_is_safety']) == 'on'){
            $cert_is_safety = 1;
        } else {
            $cert_is_safety = 0;
        }
        $cert_name = $_GET['cert_name'];
        if($cert_never_expires == 1 || $_GET['cert_days_active'] >= 18250) {
            $cert_days_active = 18250;
        } else {
            $cert_days_active = $_GET['cert_days_active'];
        }
        $cert_description = $_GET['cert_description'];
        $cert_notes = '';
        $cert_when_set = $current_time;
        $cert_when_modified = $current_time;
        $cert_last_user = $_GET['cert_last_user'];
        $cert_points = $_GET['cert_points'];
        $parameters_found = true;
    } else {
        $parameters_found = false;
    }

    if ($parameters_found) {
        $querystring = '';
        $insertstring = '';
        $db_pdo = db_connect();
        $count_arr = array();

        $querystring = "SELECT COUNT(*) AS \"mycount\" FROM tcs.cert WHERE cert_name='".$cert_name."';";
        $count_arr = db_query($db_pdo, $querystring);

        if ($count_arr[0]['mycount'] < 1) {
            $insertstring = " INSERT INTO tcs.cert ";
            $insertstring .= " (cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user, cert_points) ";
            $insertstring .= " VALUES ( ";
            $insertstring .= "'".$cert_name."', ";
            $insertstring .= "'".$cert_description."', ";
            $insertstring .= $cert_days_active.", ";
            $insertstring .= "'".$cert_notes."', ";
            if ($GLOBALS['DB_TYPE'] == 'pgsql') {
                $insertstring .= ($cert_never_expires?"true":"false").", ";
                $insertstring .= ($cert_is_ert?"true":"false").", ";
                $insertstring .= ($cert_is_iso?"true":"false").", ";
                $insertstring .= ($cert_is_safety?"true":"false").", ";
                $insertstring .= "'".date('Y-m-d H:i:s', $cert_when_set)."', ";
                $insertstring .= "'".date('Y-m-d H:i:s', $cert_when_modified)."', ";
            } else {
                $insertstring .= $cert_never_expires.", ";
                $insertstring .= $cert_is_ert.", ";
                $insertstring .= $cert_is_iso.", ";
                $insertstring .= $cert_is_safety.", ";
                $insertstring .= $cert_when_set.", ";
                $insertstring .= $cert_when_modified.", ";
            }
            $insertstring .= "'".$cert_last_user."', ";
            $insertstring .= $cert_points." );";

            if (db_insert($db_pdo, $insertstring)) {
                $arr['success'] = true;
                $arr['cert_id'] = $db_pdo->lastInsertId();
                $arr['cert_name'] = $cert_name;
                $arr['cert_description'] = $cert_description;
                $arr['cert_days_active'] = $cert_days_active;
                $arr['cert_notes'] = $cert_notes;
                $arr['cert_never_expires'] = $cert_never_expires;
                $arr['cert_is_ert'] = $cert_is_ert;
                $arr['cert_is_iso'] = $cert_is_iso;
                $arr['cert_is_safety'] = $cert_is_safety;
                $arr['cert_when_set'] = $cert_when_set;
                $arr['cert_when_modified'] = $cert_when_modified;
                $arr['cert_last_user'] = $cert_last_user;
                $arr['cert_points'] = $cert_points;
            } else {
                $arr['success'] = false;
                $arr['error'] = 'Database execute failed';
            }
        } else {
            $arr['success'] = false;
            $arr['error'] = 'Another certification already exists with the same name.';
        }
		// Close connection to DB
		$db_pdo = null;
    } else {
        $arr['success'] = false;
        $arr['error'] = 'invalid POST values passed for cert update';
        if(isset($_POST['cert_days_active']) && $_POST['cert_days_active'] < 1) {
            $arr['error'] .= ' - cert_days_active is set to less than 1';
        }
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));

