<?php
	// JSON/JSON_ACTION_update_notification.php?cert_id=1&user_cert_id=1&notification_sent_date=1690788651
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
 	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();
	//$currenttime = time(); //1690788651 (2023-7-31 0:30:0)

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && isset($_GET['user_cert_id']) && strlen($_GET['user_cert_id']) > 0 && is_numeric($_GET['user_cert_id']) && isset($_GET['notification_sent_date']) && strlen($_GET['notification_sent_date']) > 0 && is_numeric($_GET['notification_sent_date'])){
		$cert_id = intval($_GET['cert_id']);
		$user_cert_id = intval($_GET['user_cert_id']);
		$notification_sent_date = intval($_GET['notification_sent_date']);

		$insertstring = '';
		$db_pdo = db_connect();

		$insertstring = 'INSERT INTO tcs.notification ';
		$insertstring .= ' (cert_id, user_cert_id, notification_sent_date) ';
		$insertstring .= ' VALUES ( ';
		$insertstring .= $cert_id.', ';
		$insertstring .= $user_cert_id.', ';
		$insertstring .= $notification_sent_date.' ';
		$insertstring .= ' );';

		if(db_insert($db_pdo, $insertstring)){
			$arr['success'] = true;
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid get values passed';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
