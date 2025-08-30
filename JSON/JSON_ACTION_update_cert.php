<?php
	// JSON/JSON_ACTION_update_cert.php // Requires POST data to return a value
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_POST['cert_id']) && strlen($_POST['cert_id']) > 0 && is_numeric($_POST['cert_id']) && $_POST['cert_id'] > 0
        && isset($_POST['cert_days_active']) && strlen($_POST['cert_days_active']) > 0 && is_numeric($_POST['cert_days_active']) && $_POST['cert_days_active'] > 0
        && isset($_POST['cert_description']) && isset($_POST['cert_notes'])) {
		$cert_id = $_POST['cert_id'];
		$cert_when_modified = time();
		$cert_last_user = $_POST['cert_last_user'];

		$updatestring = '';
		$db_pdo = db_connect();

		if(isset($_POST['cert_never_expires']) && strtolower($_POST['cert_never_expires']) == 'on') {
			$cert_never_expires = 1;
			//$querystring='SELECT cert_id, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.cert WHERE is_active = 1;';
			if ($GLOBALS['DB_TYPE'] == 'pgsql'){
				// TBD
			} else { // mysql
				$updatestring='UPDATE tcs.warning SET is_active = 0, warning_when_modified = '.(int)$warning_when_modified.', warning_last_user = "'.$warning_last_user.'" WHERE cert_id = '.$cert_id.';';
				db_update($db_pdo, $updatestring);
			}
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

		if($cert_never_expires == 1 || $_POST['cert_days_active'] > 18250) {
			$cert_days_active = 18250;
		} else {
			$cert_days_active = $_POST['cert_days_active'];
		}
		$cert_description = $_POST['cert_description'];
		$cert_notes = $_POST['cert_notes'];
        if (isset($_POST['cert_points']) && strlen($_POST['cert_points']) > 0 && is_numeric($_POST['cert_points']) && $_POST['cert_points'] > 0)  {
            $cert_points = intval($_POST['cert_points']);
        } else {
            $cert_points = 0;
        }

		$updatestring = "UPDATE tcs.cert SET ";
		$updatestring .= " cert_description = '".$cert_description."', ";
		$updatestring .= " cert_days_active = ".$cert_days_active.", ";
		$updatestring .= " cert_notes = '".$cert_notes."', ";
		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$updatestring .= " cert_never_expires = ".($cert_never_expires?"true":"false").", ";
			$updatestring .= " cert_is_ert = ".($cert_is_ert?"true":"false").", ";
			$updatestring .= " cert_is_iso = ".($cert_is_iso?"true":"false").", ";
			$updatestring .= " cert_is_safety = ".($cert_is_safety?"true":"false").", ";
			$updatestring .= " cert_when_modified = '".date('Y-m-d H:i:s', $cert_when_modified)."', ";
		} else { // mysql
			$updatestring .= " cert_never_expires = ".$cert_never_expires.", ";
			$updatestring .= " cert_is_ert = ".$cert_is_ert.", ";
			$updatestring .= " cert_is_iso = ".$cert_is_iso.", ";
			$updatestring .= " cert_is_safety = ".$cert_is_safety.", ";
			$updatestring .= " cert_when_modified = ".$cert_when_modified.", ";
		}
		$updatestring .= " cert_last_user = '".$cert_last_user."', ";
        $updatestring .= " cert_points = '".$cert_points."' ";
		$updatestring .= " WHERE cert_id = ".$cert_id.";";

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid POST values passed for cert update';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));
