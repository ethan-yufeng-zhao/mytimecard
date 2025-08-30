<?php
	// JSON/JSON_ACTION_delete_cert.php?cert_id=333&delete_cert=0&cert_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0
		&& isset($_GET['delete_cert']) && strlen($_GET['delete_cert']) > 0 && is_numeric($_GET['delete_cert'])) {
		$is_active = $_GET['delete_cert'];
		$cert_id = $_GET['cert_id'];
		$cert_when_modified = time();
		$cert_last_user = $_GET['cert_last_user'];

		$updatestring = '';
		$db_pdo = db_connect();

		$updatestring = "UPDATE tcs.cert SET ";
		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$updatestring .= " is_active = ".($is_active?"true":"false").", ";
			$updatestring .= " cert_when_modified = '".date('Y-m-d H:i:s', $cert_when_modified)."', ";
		} else { // mysql
			$updatestring .= " is_active = ".$is_active.", ";
			$updatestring .= " cert_when_modified = ".$cert_when_modified.", ";
		}
		$updatestring .= " cert_last_user = '".$cert_last_user."' ";
		$updatestring .= " WHERE cert_id = ".$cert_id.";";

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
			if($is_active == 0) {
				$arr['message'] = 'Cert has been disabled';
			} else {
				$arr['message'] = 'Cert has been reactivated';
			}
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

?>
