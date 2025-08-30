<?php
	// JSON/JSON_user_cert_history.php?user_id=40&cert_id=1
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$script_timer = time();
	$current_timestamp = time();

	$arr = array();
	$arr['items'] = array();
	$arr['success'] = false;

	if(isset($_GET['user_id']) && strlen($_GET['user_id']) > 0
		&& isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id'])) {
		$user_id = $_GET['user_id'];
		$cert_id = $_GET['cert_id'];

		$querystring = '';
		$db_arr = array();

		$db_pdo = db_connect();

		if ($GLOBALS['DB_TYPE'] == 'pgsql'){
			$querystring='SELECT c.*, uc.* FROM tcs.user_cert uc JOIN cert c ON (c.cert_id = uc.cert_id) WHERE uc.ad_account = \''.$user_id.'\' AND c.cert_id = '.$cert_id.' ORDER BY uc.user_cert_date;';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data ) {
				$arr['items']['user_samaccountname'] = $user_id;

				$temp_arr = array();
				$temp_arr['user_cert_id'] = $cert_id;
				$temp_arr['user_cert_date_granted'] = strtotime($data['user_cert_date']);
				$temp_arr['user_cert_date_granted_ymd'] = date('Y-m-d', $temp_arr['user_cert_date_granted']);
				$temp_arr['user_cert_date_set'] = strtotime($data['cert_when_set']);
				$temp_arr['user_cert_date_modified'] = strtotime($data['cert_when_modified']);
				$temp_arr['user_cert_last_user'] = $data['user_cert_update_user']; //$data['cert_last_user'];
				$temp_arr['user_cert_exception'] = 0;//(int)$data['user_cert_exception'];

				$temp_arr['cert_id'] = $cert_id;
				$temp_arr['cert_name'] = $data['cert_name'];
				$temp_arr['cert_description'] = $data['cert_description'];

				$arr['items']['cert_id'] = $cert_id;
				$arr['items']['cert_name'] = $data['cert_name'];
				$arr['items']['cert_description'] = $data['cert_description'];

				$temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
				$temp_arr['cert_notes'] = $data['cert_notes'];
				$temp_arr['cert_never_expires'] = (int)$data['cert_never_expires'];
				$temp_arr['cert_is_ert'] = (int)$data['cert_is_ert'];
				$temp_arr['cert_is_iso'] = (int)$data['cert_is_iso'];
				$temp_arr['cert_is_safety'] = (int)$data['cert_is_safety'];
				$temp_arr['cert_when_set'] = strtotime($data['cert_when_set']);
				$temp_arr['cert_when_modified'] = strtotime($data['cert_when_modified']);
				$temp_arr['cert_last_user'] = $data['cert_last_user'];

				if($temp_arr['cert_never_expires'] == 0) {
					$temp_arr['calculated_expire'] =$temp_arr['user_cert_date_granted'] + ($temp_arr['cert_days_active']*24*60*60);
					$temp_arr['calculated_expire_ymd'] = date('Y-m-d', $temp_arr['calculated_expire']);
					$temp_arr['calculated_days_until_expire'] = floor(($temp_arr['calculated_expire'] - $current_timestamp) / (24*60*60));
				}

				$arr['items']['certs'][$temp_arr['user_cert_date_granted']] = $temp_arr;
				unset($temp_arr);
				$arr['success'] = true;
			}
		} else { // mysql
			$querystring='SELECT cert.cert_id, `user`.`user_id`, user_cert_id, user_supervisor_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user, user_cert_exception, user_samaccountname, user_firstname, user_lastname, user_email, user_is_admin, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.user_cert JOIN `user` ON (`user`.`user_id` = `user_cert`.`user_id`) JOIN cert ON (cert.cert_id = user_cert.cert_id) WHERE  user_cert.user_id = '.$user_id.' AND cert.cert_id = '.$cert_id.';';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data ) {
				$user_id = intval($data['user_id']);

				if(!array_key_exists($user_id, $arr['items'])) {
					$arr['items']['user_samaccountname'] = $data['user_samaccountname'];
					$arr['items']['user_firstname'] = $data['user_firstname'];
					$arr['items']['user_lastname'] = $data['user_lastname'];
					$arr['items']['user_email'] = $data['user_email'];
					$arr['items']['user_supervisor_id'] = (int)$data['user_supervisor_id'];
					$arr['items']['user_is_admin'] = (int)$data['user_is_admin'];
				}
				$cert_id = intval($data['cert_id']);

				$temp_arr = array();
				$temp_arr['user_cert_id'] = (int)$data['user_cert_id'];
				$temp_arr['user_cert_date_granted'] = (int)$data['user_cert_date_granted'];
				$temp_arr['user_cert_date_granted_ymd'] = date('Y-m-d', $data['user_cert_date_granted']);
				$temp_arr['user_cert_date_set'] = (int)$data['user_cert_date_set'];
				$temp_arr['user_cert_date_modified'] = (int)$data['user_cert_date_modified'];
				$temp_arr['user_cert_last_user'] = $data['user_cert_last_user'];
				$temp_arr['user_cert_exception'] = (int)$data['user_cert_exception'];

				$temp_arr['cert_id'] = intval($data['cert_id']);
				$temp_arr['cert_name'] = $data['cert_name'];
				$temp_arr['cert_description'] = $data['cert_description'];

				$arr['items']['cert_id'] = intval($data['cert_id']);
				$arr['items']['cert_name'] = $data['cert_name'];
				$arr['items']['cert_description'] = $data['cert_description'];

				$temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
				$temp_arr['cert_notes'] = $data['cert_notes'];
				$temp_arr['cert_never_expires'] = (int)$data['cert_never_expires'];
				$temp_arr['cert_is_ert'] = (int)$data['cert_is_ert'];
				$temp_arr['cert_is_safety'] = (int)$data['cert_is_safety'];
				$temp_arr['cert_when_set'] = (int)$data['cert_when_set'];
				$temp_arr['cert_when_modified'] = (int)$data['cert_when_modified'];
				$temp_arr['cert_last_user'] = $data['cert_last_user'];
				if($temp_arr['cert_never_expires'] == 0) {
					$temp_arr['calculated_expire'] = $temp_arr['user_cert_date_granted'] + ($temp_arr['cert_days_active']*24*60*60);
					$temp_arr['calculated_expire_ymd'] = date('Y-m-d', $temp_arr['calculated_expire']);
					$temp_arr['calculated_days_until_expire'] = floor(($temp_arr['calculated_expire'] - $current_timestamp) / (24*60*60));
				}

				$arr['items']['certs'][intval($data['user_cert_date_granted'])] = $temp_arr;
				unset($temp_arr);
				$arr['success'] = true;
			}
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['error'] = 'cert_id and user_id is not set in get values';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
