<?php
	// JSON/JSON_ACTION_delete_cert.php?cert_id=333&delete_cert=0&cert_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0
		&& isset($_GET['true_delete_cert']) && strlen($_GET['true_delete_cert']) > 0 && is_numeric($_GET['true_delete_cert'])) {
		$is_deleted = (int)$_GET['true_delete_cert'];
		$cert_id = (int)$_GET['cert_id'];

		$deletestring = '';
		$db_pdo = db_connect();

		$deletestring = 'DELETE FROM tcs.cert WHERE cert_id = '.$cert_id.';';

		if(db_update($db_pdo, $deletestring)){
			$arr['success'] = true;
			$arr['message'] = 'Cert has been deleted';
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
