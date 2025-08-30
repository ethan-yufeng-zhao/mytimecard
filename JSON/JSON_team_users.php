<?php
	// JSON/JSON_team_users.php?user_supervisor_id=5
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['user_supervisor_id']) && strlen($_GET['user_supervisor_id']) > 0 && is_numeric($_GET['user_supervisor_id'])) {
		$user_supervisor_id = $_GET['user_supervisor_id'];

		$querystring='';
		$db_pdo = db_connect();

		if ($GLOBALS['DB_TYPE'] == 'pgsql'){
			$querystring='SELECT a.user_id, a.user_samaccountname, a.user_supervisor_id, a.user_firstname, a.user_lastname, a.user_email, a.user_is_admin, (SELECT COUNT(*) FROM tcs.`user` b WHERE b.user_supervisor_id = a.user_id) AS "teamcount", (SELECT COUNT(DISTINCT cert_id) FROM tcs.user_cert WHERE is_active = 1 AND user_id = a.user_id) AS "speccount" FROM tcs.`user` a WHERE a.is_active = 1 AND a.user_supervisor_id = '.$user_supervisor_id.';';
		} else { // mysql
			$querystring='SELECT a.user_id, a.user_samaccountname, a.user_supervisor_id, a.user_firstname, a.user_lastname, a.user_email, a.user_is_admin, (SELECT COUNT(*) FROM tcs.`user` b WHERE b.user_supervisor_id = a.user_id) AS "teamcount", (SELECT COUNT(DISTINCT cert_id) FROM tcs.user_cert WHERE is_active = 1 AND user_id = a.user_id) AS "speccount" FROM tcs.`user` a WHERE a.is_active = 1 AND a.user_supervisor_id = '.$user_supervisor_id.';';
		}
		$db_arr = db_query($db_pdo, $querystring);

		foreach ($db_arr as $key => $data ) {
			$temp_arr = array();
			$temp_arr['user_id'] = (int)$data['user_id'];
			$temp_arr['user_samaccountname'] = $data['user_samaccountname'];
			$temp_arr['user_supervisor_id'] = (int)$data['user_supervisor_id'];
			$temp_arr['user_firstname'] = $data['user_firstname'];
			$temp_arr['user_lastname'] = $data['user_lastname'];
			$temp_arr['user_email'] = $data['user_email'];
			$temp_arr['user_is_admin'] = (int)$data['user_is_admin'];
			$temp_arr['teamcount'] = (int)$data['teamcount'];
			$temp_arr['speccount'] = (int)$data['speccount'];
			$arr[$temp_arr['user_id']] = $temp_arr;
			unset($temp_arr);
		}
		// Close connection to DB
		$db_pdo = null;
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
