<?php
	// JSON/JSON_ACTION_delete_user_cert.php?user_cert_id=2&user_cert_last_user=bob.dole
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if (isset($_GET['user_cert_id']) && is_numeric($_GET['user_cert_id']) && $_GET['user_cert_id'] > 0
		&& isset($_GET['user_cert_last_user']) && strlen($_GET['user_cert_last_user']) > 0
		&& isset($_GET['user_cert_date']) && strlen($_GET['user_cert_date']) > 0
		&& isset($_GET['cert_history_user']) && strlen($_GET['cert_history_user']) > 0) { // Do a delete from the database

		$user_cert_id = $_GET['user_cert_id'];
		$user_cert_last_user = $_GET['user_cert_last_user'];
		$user_cert_date_modified = time();

		$user_cert_date = date('Y-m-d H:i:s', $_GET['user_cert_date']);
		$cert_history_user = $_GET['cert_history_user'];

		$updatestring = '';
		$db_pdo = db_connect();

		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$updatestring = 'DELETE from tcs.user_cert ';
			$updatestring .= ' WHERE cert_id = '.$user_cert_id;
			$updatestring .= ' and ad_account = \''.$cert_history_user.'\'';
			$updatestring .= ' and user_cert_date = \''.$user_cert_date.'\';';
		} else {
			$updatestring = 'UPDATE tcs.user_cert ';
			$updatestring .= ' SET user_cert_date_modified = '.$user_cert_date_modified.', ';
			$updatestring .= ' user_cert_last_user = \''.$user_cert_last_user.'\', ';
			$updatestring .= ' is_active = 0 ';
			$updatestring .= ' WHERE user_cert_id = '.$user_cert_id.';';
		}

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
			$arr['message'] = 'Certificate has been removed from this user';
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
		}

		// Close connection to DB
		$db_pdo = null;

	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid POST values passed to remove the cert from template';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
