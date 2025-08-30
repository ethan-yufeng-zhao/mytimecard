<?php
	// JSON/JSON_team_users_templates.php?user_supervisor_id=5
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();
	$user_template_arr = array();

	//xdebug
	//$_GET['user_supervisor_id'] = 'oliver.li';

	if(isset($_GET['user_supervisor_id']) && strlen($_GET['user_supervisor_id']) > 0) {
		$user_supervisor_id = $_GET['user_supervisor_id'];
		$querystring = '';
		$db_arr = array();

		$db_pdo = db_connect();
		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			//1. get team members
			$querystring='select * from tcs."user" u where u.manager=\''.$user_supervisor_id.'\' order by u.ad_account;';
			$team_arr = db_query($db_pdo, $querystring);
			//2. get templates
			$querystring='select * from tcs.template t where t.is_active order by t.template_id;';
			$template_arr = db_query($db_pdo, $querystring);
			//3. parse all user's template
			foreach ($template_arr as $key => $data ) {
				$template_id = (int)$data['template_id'];

				$temp_arr = array();
				$temp_arr['template_name'] = $data['template_name'];
				$temp_arr['template_is_default_for_department'] = (int)$data['template_is_default_for_department'];
				$temp_arr['template_department_number'] = (int)$data['template_department_number'];
				$temp_arr['template_when_set'] = $data['template_when_set'];
				$temp_arr['template_when_modified'] = $data['template_when_modified'];
				$temp_arr['template_last_user'] = $data['template_last_user'];
				$temp_arr['template_cert_count'] = count(json_decode($data['certs']));
//				$temp_arr['template_users'] = json_decode($data['users']);

				$allusersintemplate = json_decode($data['users']);
				foreach ($allusersintemplate as $key => $data) {
					if (!key_exists($data, $user_template_arr)){
						$user_template_arr[$data] = array();
					}
					if (!key_exists($template_id, $user_template_arr[$data])){
						$user_template_arr[$data][$template_id] = $temp_arr;
					}
				}
				unset($temp_arr);
			}
			foreach ($team_arr as $key => $data ) {
				if (key_exists($data['ad_account'], $user_template_arr)){
					$querystring = 'SELECT ad_account FROM tcs.user WHERE ad_account = \''.$data['ad_account'].'\';';
					$ad_account_arr = db_query($db_pdo, $querystring);
					$ad_account = '';
					if ($ad_account_arr != null && $ad_account_arr[0]['ad_account'] != ''){
						$ad_account = $ad_account_arr[0]['ad_account'];
					}
					$arr[$ad_account] = $user_template_arr[$data['ad_account']];
				}
			}

		} else { // mysql
			$querystring='SELECT template_user_links_id, template.template_id, `user`.`user_id`, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, user_samaccountname, user_supervisor_id, user_firstname, user_lastname, user_email, (SELECT COUNT(*) FROM tcs.template_cert_links WHERE template_cert_links.template_id = template_user_links.template_id) AS "template_cert_count" FROM tcs.template_user_links JOIN template ON (template.template_id = template_user_links.template_id) JOIN `user` ON (`user`.`user_id` = template_user_links.user_id AND `user`.`user_supervisor_id` = '.$user_supervisor_id.') WHERE `user`.`is_active` = 1 ORDER BY `user`.`user_samaccountname`;';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data ) {
				$temp_arr = array();
				$temp_arr['template_user_links_id'] = (int)$data['template_user_links_id'];
				$temp_arr['template_id'] = (int)$data['template_id'];
				$temp_arr['user_id'] = (int)$data['user_id'];
				$temp_arr['template_name'] = $data['template_name'];
				$temp_arr['template_is_default_for_department'] = (int)$data['template_is_default_for_department'];
				$temp_arr['template_department_number'] = (int)$data['template_department_number'];
				$temp_arr['template_when_set'] = (int)$data['template_when_set'];
				$temp_arr['template_when_modified'] = (int)$data['template_when_modified'];
				$temp_arr['template_last_user'] = $data['template_last_user'];
				$temp_arr['template_cert_count'] = (int)$data['template_cert_count'];
				$temp_arr['user_samaccountname'] = $data['user_samaccountname'];
				$temp_arr['user_supervisor_id'] = (int)$data['user_supervisor_id'];
				$temp_arr['user_firstname'] = $data['user_firstname'];
				$temp_arr['user_lastname'] = $data['user_lastname'];
				$temp_arr['user_email'] = $data['user_email'];
				$arr[$temp_arr['template_user_links_id']] = $temp_arr;
				unset($temp_arr);
			}
		}
		// Close connection to DB
		$db_pdo = null;
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
