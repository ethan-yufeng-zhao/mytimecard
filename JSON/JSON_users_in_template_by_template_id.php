<?php
	// JSON/JSON_users_in_template_by_template_id.php?template_id=50
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();
	$arr['items'] = array();
	$arr['count'] = 0;

	if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
		$template_id = $_GET['template_id'];

		$querystring = '';
		$db_arr = array();
		$user_arr = array();
		$users = array();
		$users_string = '';
		$user_arr_by_ad = array();

		$db_pdo = db_connect();

		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$querystring = 'SELECT * FROM tcs.template WHERE is_active = true AND template_id = '.$template_id.';';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data ) {
				$arr['items'][$template_id]['template_id'] = $template_id;
				$arr['items'][$template_id]['template_name'] = $data['template_name'];

				$arr['items'][$template_id]['template_is_default_for_department'] = (bool)$data['template_is_default_for_department'];
				$arr['items'][$template_id]['template_department_number'] = $data['template_department_number'];
				$arr['items'][$template_id]['template_when_set'] = strtotime($data['template_when_set']);
				$arr['items'][$template_id]['template_when_modified'] = strtotime($data['template_when_modified']);
				$users = json_decode($data['users']);
				sort($users);
				$users_string = $data['users'];
				$users_string = str_replace("[", "(", $users_string);
				$users_string = str_replace(']', ')', $users_string);
				$users_string = str_replace('"', '\'', $users_string);

				//$arr['items'][$template_id]['users'] = $users;
				$arr['count']++;
			}

			if (count($users) == 0) {
				$arr['items'][$template_id]['users'] = array();
			} else {
				$querystring = 'SELECT * FROM tcs.user WHERE ad_account in ' . $users_string. ';';
				$user_arr = db_query($db_pdo, $querystring);
				if ($user_arr != null && count($user_arr) > 0) {
					foreach ($user_arr as $k => $v){
						$user_arr_by_ad[$v['ad_account']] = $v;
					}
				}

				foreach ($users as $key => $userdata ) {
					$temp_arr = array();
//					$temp_arr['template_user_links_id'] = (int)$userdata['template_user_links_id'];
//					$temp_arr['template_id'] = (int)$userdata['template_id'];
					$temp_arr['user_id'] = $userdata;
					if (key_exists($userdata, $user_arr_by_ad)) {
						$temp_arr['user_samaccountname'] = $user_arr_by_ad[$userdata]['ad_account'];
						$temp_arr['user_wsaccount'] = $user_arr_by_ad[$userdata]['ws_account'];

						$temp_arr['user_supervisor_id'] = $user_arr_by_ad[$userdata]['manager'];//(int)$userdata['manager'];
						$temp_arr['user_firstname'] = $user_arr_by_ad[$userdata]['first_name'];
						$temp_arr['user_lastname'] = $user_arr_by_ad[$userdata]['last_name'];
						$temp_arr['user_email'] = $user_arr_by_ad[$userdata]['ad_account'].'@jfab.aosmd.com';

	//					$temp_arr['user_last_ldap_check'] = $userdata['user_last_ldap_check'];
	//					$temp_arr['user_is_admin'] = $userdata['user_is_admin'];
						$arr['items'][$template_id]['users'][$temp_arr['user_id']] = $temp_arr;
					} else {
						$temp_arr['user_samaccountname'] = '';

						$temp_arr['user_supervisor_id'] = '';
						$temp_arr['user_firstname'] = '';
						$temp_arr['user_lastname'] = '';
						$temp_arr['user_email'] = '';
					}
					unset($temp_arr);
//					$arr['items'][$template_id]['usercount']++;
				}

//				foreach ($user_arr as $key => $userdata ) {
//					$arr['items'][$template_id]['users'][$userdata] = $userdata;
//					$arr['items'][$template_id]['usercount']++;
//				}

			}
			$arr['items'][$template_id]['usercount'] = count($arr['items'][$template_id]['users']);
		} else {
			$querystring='SELECT template_id, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user FROM tcs.template WHERE is_active = 1 AND template_id = '.$template_id.';';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data ) {
				$arr['items'][$template_id]['template_id'] = $template_id;
				$arr['items'][$template_id]['template_name'] = $data['template_name'];

				$arr['items'][$template_id]['template_is_default_for_department'] = (bool)$data['template_is_default_for_department'];
				$arr['items'][$template_id]['template_department_number'] = $data['template_department_number'];
				$arr['items'][$template_id]['template_when_set'] = $data['template_when_set'];
				$arr['items'][$template_id]['template_when_modified'] = $data['template_when_modified'];

				$arr['items'][$template_id]['usercount'] = 0;
				$arr['count']++;

				$querystring='SELECT template_user_links_id, template_id, `user`.user_id, user_samaccountname, user_supervisor_id, user_firstname, user_lastname, user_email, user_last_ldap_check, user_is_admin FROM tcs.template_user_links JOIN `user` ON (`user`.`user_id` = `template_user_links`.`user_id`) WHERE `user`.`is_active` = 1 AND template_user_links.template_id = '.$template_id.';';
				$user_arr = db_query($db_pdo, $querystring);

				$mycount = 0;

				foreach ($user_arr as $key => $userdata ) {
					$temp_arr = array();
					$temp_arr['template_user_links_id'] = (int)$userdata['template_user_links_id'];
					$temp_arr['template_id'] = (int)$userdata['template_id'];
					$temp_arr['user_id'] = (int)$userdata['user_id'];

					$temp_arr['user_samaccountname'] = $userdata['user_samaccountname'];
					$temp_arr['user_supervisor_id'] = (int)$userdata['user_supervisor_id'];
					$temp_arr['user_firstname'] = $userdata['user_firstname'];
					$temp_arr['user_lastname'] = $userdata['user_lastname'];
					$temp_arr['user_email'] = $userdata['user_email'];
					$temp_arr['user_last_ldap_check'] = $userdata['user_last_ldap_check'];
					$temp_arr['user_is_admin'] = $userdata['user_is_admin'];

					$arr['items'][$template_id]['users'][$temp_arr['template_user_links_id']] = $temp_arr;
					unset($temp_arr);
					$arr['items'][$template_id]['usercount']++;
				}
			}
		}

		// Close connection to DB
		$db_pdo = null;
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));
?>
