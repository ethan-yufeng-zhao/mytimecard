<?php
	// JSON/JSON_ACTION_delete_template_user_links.php?template_user_links_id=3
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['template_user_links_id']) && is_numeric($_GET['template_user_links_id']) && strlen($_GET['template_user_links_id']) > 0
		&& isset($_GET['remove_user_id']) && strlen($_GET['remove_user_id']) > 0 ) {
		$template_user_links_id = $_GET['template_user_links_id'];
		$remove_user_id = $_GET['remove_user_id'];

		$sqlstring = '';
		$db_pdo = db_connect();


		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$querystring='SELECT t.users FROM tcs.template t WHERE t.template_id = '.$template_user_links_id.';';
			$count_arr = db_query($db_pdo, $querystring);
			$users = array();
			foreach ($count_arr as $k => $v) {
				$users = json_decode($v['users']);
			}

			$sqlstring = "UPDATE tcs.template set users='";
			$new_users = array();
			if (in_array($remove_user_id, $users)) {
				$new_users = array_diff($users, array($remove_user_id));
			}
			sort($new_users);
			$sqlstring .= json_encode($new_users);
			$sqlstring .= "'::jsonb where template_id = ";
			$sqlstring .= $template_user_links_id.';';
		} else {
			$sqlstring = 'DELETE FROM tcs.template_user_links WHERE template_user_links_id = '.$template_user_links_id.';';
		}

		if(db_update($db_pdo, $sqlstring)){
			$arr['success'] = true;
			$arr['message'] = 'User has been unlinked from the template';
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid POST values passed to remove the cert from template';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
