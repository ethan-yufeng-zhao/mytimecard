<?php
	// JSON/JSON_all_users_certs.php
	// JSON/JSON_all_users_certs.php?template_id=3
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$script_timer = time();
	$current_timestamp = time();
	$arr = array();
	$querystring='';
	$db_arr = array();
	$cert_arr = array();
	$user_arr = array();
	$supervisor_arr = array();

	$db_pdo = db_connect();
	if ($GLOBALS['DB_TYPE'] == 'pgsql') {
		$querystring='select * from tcs.user_cert uc order by uc.ad_account, uc.cert_id, uc.user_cert_date desc';
		$db_arr = db_query($db_pdo, $querystring);

		$querystring='select * from tcs.cert c order by c.cert_id';
		$cert_arr_t = db_query($db_pdo, $querystring);
		foreach ($cert_arr_t as $k => $v) {
			$cert_arr[$v['cert_id']] = $v; //transform
		}

		$querystring='select * from tcs.user u order by u.ad_account';
		$users_db_arr = db_query($db_pdo, $querystring);
//		logit($user_arr);
		foreach ($users_db_arr as $uk => $uv ) {
			array_push($user_arr, $uv['ad_account']);
			$supervisor_arr[$uv['ad_account']] = $uv['manager'];
		}

		if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {

		}
	} else { // mysql
		$cert_sql = 'SELECT cert.cert_id, `user`.`user_id`, user_cert_id, user_supervisor_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user, user_cert_exception, user_samaccountname, user_firstname, user_lastname, user_email, user_is_admin, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.user_cert JOIN `user` ON (`user`.`user_id` = `user_cert`.`user_id`) JOIN cert ON (cert.cert_id = user_cert.cert_id) WHERE user_cert.is_active = 1 AND `user`.`is_active` = 1';
		if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
			$tempid = intval($_GET['template_id']);
			$cert_sql .= ' AND cert.cert_id IN (SELECT template_cert_links.cert_id FROM tcs.template_cert_links WHERE template_cert_links.template_id = '.$tempid.')';
		}
		$cert_sql .= ' ORDER BY user_cert.cert_id ;';
		$querystring = $cert_sql;
		$db_arr = db_query($db_pdo, $querystring);
	}

	if ($GLOBALS['DB_TYPE'] == 'pgsql') {
		foreach ($db_arr as $key => $data ) {
//			$temp_arr = array();
//			$temp_arr['user_id'] =$data['ad_account'];
//			$temp_arr['cert_id'] = $data['cert_id'];
//			$temp_arr['cert_date'] = $data['user_cert_date'];
//			$arr = $temp_arr;
//						if (!in_array($user_id, array_column($arr, 'user_id')))

//			logit($data['cert_id']);
			$cid = intval($data['cert_id']);
			if (key_exists($cid, $cert_arr)) {
				$user_id = $data['ad_account'];
				if (!in_array($user_id, $user_arr)){
					continue;
				}
				$cert_id = $data['cert_id'];
				$cert_date = $data['user_cert_date'];
				if (!key_exists($user_id, $arr)){
					$arr[$user_id] = array();
				}
				if (!key_exists($cert_id, $arr[$user_id])){
					$arr[$user_id][$cert_id] = array();
				}
				if (!key_exists('cert_date', $arr[$user_id][$cert_id])){
	//				$arr[$user_id][$cert_id] = array();
					$arr[$user_id][$cert_id]['cert_date']= array();
				}
				if (!in_array($cert_date, $arr[$user_id][$cert_id]['cert_date'])){
					$arr[$user_id][$cert_id]['cert_date'][] = $cert_date;
				}
				if (!key_exists('cert_id', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['cert_id']= intval($data['cert_id']);
				}
				if (!key_exists('cert_name', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['cert_name']= $cert_arr[intval($data['cert_id'])]['cert_name'];
				}
				if (!key_exists('cert_description', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['cert_description']= $cert_arr[intval($data['cert_id'])]['cert_description'];
				}
				if (!key_exists('user_cert_exception', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['user_cert_exception']= 0;//$cert_arr[intval($data['cert_id'])]['user_cert_exception'];
				}
				if (!key_exists('cert_never_expires', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['cert_never_expires']= $cert_arr[intval($data['cert_id'])]['cert_never_expires'];
				}
				if (!key_exists('cert_days_active', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['cert_days_active']= $cert_arr[intval($data['cert_id'])]['cert_days_active'];
				}
				//calc expire days
				if ($arr[$user_id][$cert_id]['cert_never_expires'] == 0) {
					$temp_arr = array();
					$temp_arr['calculated_expire'] = strtotime($cert_date) + ($arr[$user_id][$cert_id]['cert_days_active'] * 24 * 60 * 60);
					$temp_arr['calculated_expire_ymd'] = date('Y-m-d', $temp_arr['calculated_expire']);
					$temp_arr['calculated_days_until_expire'] = floor(($temp_arr['calculated_expire'] - $current_timestamp) / (24 * 60 * 60));
					$arr[$user_id][$cert_id]['expire'][$cert_date] = $temp_arr;
					unset($temp_arr);
				}
				if (!key_exists('supervisor', $arr[$user_id][$cert_id])){
					$arr[$user_id][$cert_id]['supervisor'] = $supervisor_arr[$user_id];
				}
			}
			// simply it
//			$temp_arr['cert_name'] = $cert_arr[intval($data['cert_id'])+1]['cert_name'];
//			$temp_arr['cert_description'] = $cert_arr[intval($data['cert_id'])+1]['cert_description'];

//			$arr[$temp_arr['user_samaccountname']][$temp_arr['cert_id']] = $temp_arr;
//			unset($temp_arr);
		}
	} else{ // mysql
		foreach ($db_arr as $key => $data ) {
			$user_id = intval($data['user_id']);
			if(!array_key_exists($user_id, $arr)){
				$arr[$user_id]['user_id'] = $user_id;
				$arr[$user_id]['user_samaccountname'] = $data['user_samaccountname'];
				$arr[$user_id]['user_firstname'] = $data['user_firstname'];
				$arr[$user_id]['user_lastname'] = $data['user_lastname'];
				$arr[$user_id]['user_email'] = $data['user_email'];
				$arr[$user_id]['user_supervisor_id'] = (int)$data['user_supervisor_id'];
				$arr[$user_id]['user_is_admin'] = (int)$data['user_is_admin'];
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

			//$temp_arr['user_samaccountname'] = $data['user_samaccountname'];
			//$temp_arr['user_firstname'] = $data['user_firstname'];
			//$temp_arr['user_lastname'] = $data['user_lastname'];
			//$temp_arr['user_email'] = $data['user_email'];

			$temp_arr['cert_id'] = intval($data['cert_id']);
			$temp_arr['cert_name'] = $data['cert_name'];
			$temp_arr['cert_description'] = $data['cert_description'];
			$temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
			$temp_arr['cert_notes'] = $data['cert_notes'];
			$temp_arr['cert_never_expires'] = (int)$data['cert_never_expires'];
			$temp_arr['cert_is_ert'] = (int)$data['cert_is_ert'];
			$temp_arr['cert_is_safety'] = (int)$data['cert_is_safety'];
			$temp_arr['cert_when_set'] = (int)$data['cert_when_set'];
			$temp_arr['cert_when_modified'] = (int)$data['cert_when_modified'];
			$temp_arr['cert_last_user'] = $data['cert_last_user'];
			if($temp_arr['cert_never_expires'] == 0){
				$temp_arr['calculated_expire'] = $temp_arr['user_cert_date_granted'] + ($temp_arr['cert_days_active']*24*60*60);
				$temp_arr['calculated_expire_ymd'] = date('Y-m-d', $temp_arr['calculated_expire']);
				$temp_arr['calculated_days_until_expire'] = floor(($temp_arr['calculated_expire'] - $current_timestamp) / (24*60*60));
			}
			$arr[$user_id]['certs'][$cert_id][intval($data['user_cert_date_granted'])] = $temp_arr;
			unset($temp_arr);
		}
	}
	// echo(count($arr[1]['certs']));
	//$arr['script_time'] = time() - $script_timer;

	// Close connection to DB
	$db_pdo = null;

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
