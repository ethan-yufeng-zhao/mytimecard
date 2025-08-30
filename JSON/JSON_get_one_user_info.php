<?php
	// JSON/JSON_get_one_user_info.php?user_samaccountname=jburke
	// JSON/JSON_get_one_user_info.php?user_id=40
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	// body
	$arr = array();
	$querystring = '';

	//xdebug
//	$_GET['user_id'] = 'ssouthav';
//	$_GET['user_samaccountname'] = 'ssouthav';

	if ($GLOBALS['DB_TYPE'] == 'pgsql') {
		if (isset($_GET['user_id']) && strlen($_GET['user_id']) > 0) {
			$_GET['user_samaccountname'] = $_GET['user_id'];
		}
	}

	if(isset($_GET['user_samaccountname']) && strlen($_GET['user_samaccountname']) > 0) {
		$user_samaccountname = $_GET['user_samaccountname'];

		// TODO: get user_cert count with the user.  So that if it is zero the system can pop up a message saying the user has no certifications in the system
		$db_pdo = db_connect();
		if ($GLOBALS['DB_TYPE'] == 'pgsql'){
			$querystring='SELECT a.*, (SELECT COUNT(*) from tcs."user" b WHERE b.manager=a.ad_account) AS "teamcount" FROM tcs."user" a WHERE a.ad_account=\''.$user_samaccountname.'\';';
		} else{ // mysql
			$querystring='SELECT a.user_id, a.user_samaccountname, a.user_supervisor_id, a.user_firstname, a.user_lastname, a.user_email, a.user_is_admin, a.user_last_ldap_check, (SELECT COUNT(*) FROM tcs.`user` b WHERE b.user_supervisor_id = a.user_id) AS "teamcount" FROM tcs.`user` a WHERE a.user_samaccountname = "'.$user_samaccountname.'" AND a.is_active = 1;';
		}
		//$sth_mysql->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
		$db_arr = db_query($db_pdo, $querystring);
		foreach ($db_arr as $key => $data ) {
			$arr['success'] = true;
			if ($GLOBALS['DB_TYPE'] == 'pgsql'){
				$arr['user_id'] = $data['ad_account'];
				$arr['user_samaccountname'] = $data['ad_account'];
				$arr['user_supervisor_id'] = $data['manager'];
				$arr['user_firstname'] = $data['first_name'];
				$arr['user_lastname'] = $data['last_name'];
				$arr['user_email'] = $data['ad_account'].'@'.'jfab.aosmd.com';
				$arr['user_last_ldap_check'] = time();
				$arr['user_is_admin'] = true;
			} else {
				$arr['user_id'] = (int)$data['user_id'];
				$arr['user_samaccountname'] = $data['user_samaccountname'];
				$arr['user_supervisor_id'] = (int)$data['user_supervisor_id'];
			    $arr['user_firstname'] = $data['user_firstname'];
			    $arr['user_lastname'] = $data['user_lastname'];
				$arr['user_email'] = $data['user_email'];
				$arr['user_last_ldap_check'] = intval($data['user_last_ldap_check']);
				if(intval($data['user_is_admin']) == 1){
					$arr['user_is_admin'] = true;
				} else {
					$arr['user_is_admin'] = false;
				}
				// $arr['user_is_admin'] = (int)$data['user_is_admin'];
			}

			$arr['teamcount'] = (int)$data['teamcount'];
			if($arr['teamcount'] > 0) {
				$arr['user_is_supervisor'] = true;
			} else {
				$arr['user_is_supervisor'] = false;
			}
		}
		// Close connection to DB
		$db_pdo = null;
	} else if(isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 && is_numeric($_GET['user_id'])) {
		$user_id = $_GET['user_id'];

		$querystring = '';
		$db_arr = array();

		$db_pdo = db_connect();

		$querystring='SELECT a.user_id, a.user_samaccountname, a.user_supervisor_id, a.user_firstname, a.user_lastname, a.user_email, a.user_is_admin, a.user_last_ldap_check, (SELECT COUNT(*) FROM tcs.`user` b WHERE b.user_supervisor_id = a.user_id) AS "teamcount" FROM tcs.`user` a WHERE a.user_id = '.$user_id.' AND a.is_active = 1;';
		$db_arr = db_query($db_pdo, $querystring);

		foreach ($db_arr as $key => $data) {
			$arr['success'] = true;
			$arr['user_id'] = (int)$data['user_id'];
			$arr['user_samaccountname'] = $data['user_samaccountname'];
			$arr['user_supervisor_id'] = (int)$data['user_supervisor_id'];
			$arr['user_firstname'] = $data['user_firstname'];
			$arr['user_lastname'] = $data['user_lastname'];
			$arr['user_email'] = $data['user_email'];
			$arr['user_last_ldap_check'] = intval($data['user_last_ldap_check']);
			if(intval($data['user_is_admin']) == 1){
				$arr['user_is_admin'] = true;
			} else {
				$arr['user_is_admin'] = false;
			}
			// $arr['user_is_admin'] = (int)$data['user_is_admin'];
			$arr['teamcount'] = (int)$data['teamcount'];
			if($arr['teamcount'] > 0) {
				$arr['user_is_supervisor'] = true;
			} else {
				$arr['user_is_supervisor'] = false;
			}
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid get values passed';
	}

	// API RETURN
	header('Content-Type: application/json');
	echo(json_encode($arr));
?>
