<?php
	// JSON/JSON_all_users_templates.php
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	$querystring='';
	$db_pdo = db_connect();

	if ($GLOBALS['DB_TYPE'] == 'pgsql') {
		$querystring='SELECT template_user_links_id, template.template_id, `user`.`user_id`, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, user_samaccountname, user_supervisor_id, user_firstname, user_lastname, user_email FROM tcs.template_user_links JOIN template ON (template.template_id = template_user_links.template_id) JOIN `user` ON (`user`.`user_id` = template_user_links.user_id) WHERE `template`.`is_active` = 1 AND `user`.`is_active` = 1;';
	} else {
		$querystring='SELECT template_user_links_id, template.template_id, `user`.`user_id`, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, user_samaccountname, user_supervisor_id, user_firstname, user_lastname, user_email FROM tcs.template_user_links JOIN template ON (template.template_id = template_user_links.template_id) JOIN `user` ON (`user`.`user_id` = template_user_links.user_id) WHERE `template`.`is_active` = 1 AND `user`.`is_active` = 1;';
	}
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
		$temp_arr['user_samaccountname'] = $data['user_samaccountname'];
		$temp_arr['user_supervisor_id'] = (int)$data['user_supervisor_id'];
		$temp_arr['user_firstname'] = $data['user_firstname'];
		$temp_arr['user_lastname'] = $data['user_lastname'];
		$temp_arr['user_email'] = $data['user_email'];
		$arr[$temp_arr['template_user_links_id']] = $temp_arr;
		unset($temp_arr);
	}
	// Close connection to DB
	$db_pdo = null;

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
