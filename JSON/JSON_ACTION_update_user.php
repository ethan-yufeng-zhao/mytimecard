<?php
	// JSON/JSON_ACTION_update_user.php?user_samaccountname=jcubic&user_firstname=Jason&user_lastname=Cubic&user_email=jason.cubic@jfab.aosmd.com&user_supervisor_id=137
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['user_samaccountname']) && strlen($_GET['user_samaccountname']) > 0
		&& isset($_GET['user_firstname']) && strlen($_GET['user_firstname']) > 0
		&& isset($_GET['user_lastname']) && strlen($_GET['user_lastname']) > 0
		&& isset($_GET['user_email']) && strlen($_GET['user_email']) > 0
		&& isset($_GET['user_supervisor_id']) && strlen($_GET['user_supervisor_id']) > 0 && is_numeric($_GET['user_supervisor_id'])) {
		$user_samaccountname = $_GET['user_samaccountname'];
		$user_firstname = $_GET['user_firstname'];
		$user_lastname = $_GET['user_lastname'];
		$user_email = $_GET['user_email'];
		$user_supervisor_id = intval($_GET['user_supervisor_id']);

		$updatestring = '';
		$db_pdo = db_connect();

		$updatestring = 'UPDATE `tcs`.`user` SET ';
		$updatestring .= ' user_supervisor_id = '.$user_supervisor_id.', ';
		$updatestring .= ' user_firstname = \''.$user_firstname.'\', ';
		$updatestring .= ' user_lastname = \''.$user_lastname.'\', ';
		$updatestring .= ' user_email = \''.$user_email.'\' ';
		$updatestring .= ' WHERE user_samaccountname = \''.$user_samaccountname.'\' AND is_active = 1;';

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Failed on database execute';
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
