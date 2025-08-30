<?php
	// JSON/JSON_all_users_department_templates.php
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	$querystring='';
	$db_pdo = db_connect();

	if ($GLOBALS['DB_TYPE'] == 'pgsql') {
		$querystring='SELECT `user`.`user_id`, user_samaccountname, template_department_number, template.template_id, template.template_name FROM tcs.template_user_links JOIN `user` ON (`user`.`user_id` = `template_user_links`.`user_id`) JOIN template ON (template.template_id = template_user_links.template_id) WHERE `user`.`is_active` = 1 AND template.template_is_default_for_department = 1;';
	} else {
		$querystring='SELECT `user`.`user_id`, user_samaccountname, template_department_number, template.template_id, template.template_name FROM tcs.template_user_links JOIN `user` ON (`user`.`user_id` = `template_user_links`.`user_id`) JOIN template ON (template.template_id = template_user_links.template_id) WHERE `user`.`is_active` = 1 AND template.template_is_default_for_department = 1;';
	}
	$db_arr = db_query($db_pdo, $querystring);

	foreach ($db_arr as $key => $data ) {
		$temp_arr = array();
		$temp_arr['user_id'] = (int)$data['user_id'];
		$temp_arr['user_samaccountname'] = $data['user_samaccountname'];
		$temp_arr['template_department_number'] = (int)$data['template_department_number'];
		$temp_arr['template_name'] = $data['template_name'];
		$temp_arr['template_id'] = (int)$data['template_id'];
		$arr[$temp_arr['user_id']] = $temp_arr;
		unset($temp_arr);
	}
	// Close connection to DB
	$db_pdo = null;

	header('Content-Type: application/json');
	echo(json_encode($arr));
?>
