<?php
	// JSON/JSON_ACTION_delete_template.php?remove_template_id=34&remove_template_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['remove_template_id']) && strlen($_GET['remove_template_id']) > 0 && is_numeric($_GET['remove_template_id']) && isset($_GET['remove_template_last_user']) && strlen($_GET['remove_template_last_user']) > 0) {
		$template_id = $_GET['remove_template_id'];
		$template_when_modified = time();
		$template_last_user = $_GET['remove_template_last_user'];

		$updatestring = '';
		$db_pdo = db_connect();

		$updatestring = "UPDATE tcs.template SET ";
		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$updatestring .= " is_active = false, ";
			$updatestring .= " template_when_modified = '".date('Y-m-d H:i:s', $template_when_modified)."', ";
		} else { // mysql
			$updatestring .= " is_active = 0, ";
			$updatestring .= " template_when_modified = ".$template_when_modified.", ";
		}
		$updatestring .= " template_last_user = '".$template_last_user."' ";
		$updatestring .= " WHERE template_id = ".$template_id.";";

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
			$arr['message'] = 'Template has been removed';
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid GET values passed to remove template';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
