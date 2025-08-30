<?php
	// JSON/JSON_ACTION_add_template.php?template_name=test name&template_last_user=jcubic
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if (isset($_GET['template_name']) && strlen($_GET['template_name']) > 2
		&& isset($_GET['template_last_user']) && strlen($_GET['template_last_user']) > 0) {
		// if (isset($_GET['template_name']) && strlen($_GET['template_name']) > 2 && isset($_GET['template_last_user']) && strlen($_GET['template_last_user']) > 0) { // adding a new template
		// if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0 && isset($_GET['warning_number_of_days']) && strlen($_GET['warning_number_of_days']) > 0 && is_numeric($_GET['warning_number_of_days']) && $_GET['warning_number_of_days'] > 1 && $_GET['warning_number_of_days'] < 18251 && isset($_GET['warning_last_user'])) {
		// SELECT template_id, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, is_active FROM tcs.template;
		$template_name = $_GET['template_name'];
		$template_last_user = $_GET['template_last_user'];

		$template_is_default_for_department = 0;
		$template_department_number = 0;

		$current_time = time();
		$template_when_set = $current_time;
		$template_when_modified = $current_time;

		$querystring = '';
		$insertstring = '';
		$db_pdo = db_connect();
		$count_arr = array();

		$querystring = "SELECT COUNT(*) AS \"mycount\" FROM tcs.template WHERE template_name='".$template_name."';";
		$count_arr = db_query($db_pdo, $querystring);

		if ($count_arr[0]['mycount'] < 1) {
			$insertstring = " INSERT INTO tcs.template ";
			if ($GLOBALS['DB_TYPE'] == 'pgsql') {
				$insertstring .= " (template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, certs, users) ";
				$insertstring .= " VALUES ( ";
				$insertstring .= "'".$template_name."', ";
				$insertstring .= ($template_is_default_for_department?"true":"false").", ";
				$insertstring .= $template_department_number.", ";
				$insertstring .= "'".date('Y-m-d H:i:s', $template_when_set)."', ";
				$insertstring .= "'".date('Y-m-d H:i:s', $template_when_modified)."', ";
				$insertstring .= "'".$template_last_user."', ";
				$insertstring .= "'[]'::jsonb, ";
				$insertstring .= "'[]'::jsonb );";
			} else {
				$insertstring .= " (template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user) ";
				$insertstring .= " VALUES ( ";
				$insertstring .= "'".$template_name."', ";
				$insertstring .= $template_is_default_for_department.", ";
				$insertstring .= $template_department_number.", ";
				$insertstring .= $template_when_set.", ";
				$insertstring .= $template_when_modified.", ";
				$insertstring .= "'".$template_last_user."' );";
			}

			if(db_insert($db_pdo, $insertstring)){
				$arr['success'] = true;
			} else {
				$arr['success'] = false;
				$arr['error'] = 'Database execute failed';
			}
		} else {
			$arr['success'] = false;
			$arr['error'] = 'this template name already exists within the database.';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid GET values passed for template creation';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
