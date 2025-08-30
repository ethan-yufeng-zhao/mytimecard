<?php
	// JSON/JSON_ACTION_add_user_to_template.php?template_id=41&user_id=1
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if (isset($_GET['template_id']) && isset($_GET['user_id']) ) {
		$template_id = $_GET['template_id'];
		$user_id = $_GET['user_id'];

		$querystring = '';
		$insertstring = '';
		$db_pdo = db_connect();
		$count_arr = array();

		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$querystring='SELECT t.users FROM tcs.template t WHERE t.template_id = '.$template_id.';';
			$count_arr = db_query($db_pdo, $querystring);
			$users = array();
			foreach ($count_arr as $k => $v) {
				$users = json_decode($v['users']);
			}

			$insertstring = "UPDATE tcs.template set users='";
			if (!in_array($user_id, $users)) {
				array_push($users, $user_id);
				sort($users);
				$insertstring .= json_encode($users);
				$insertstring .= "'::jsonb where template_id = ";
				$insertstring .= $template_id.';';

				if(db_insert($db_pdo, $insertstring)){
					$arr['success'] = true;
					$arr['message'] = 'User added to template';
				} else {
					$arr['success'] = false;
					$arr['error'] = 'Database execute failed';
				}
			} else {
				$arr['success'] = false;
				$arr['error'] = 'User is already assigned to this template.';
			}
			// Close connection to DB
			$db_pdo = null;
		} else {
			$querystring = 'SELECT COUNT(*) as "mycount" FROM tcs.template_user_links WHERE template_id = '.$template_id.' AND user_id = '.$user_id.';';
			$count_arr = db_query($db_pdo, $querystring);

			if ($count_arr[0]['mycount'] < 1) {
				$insertstring = 'INSERT INTO tcs.template_user_links ';
				$insertstring .= ' (template_id, user_id) ';
				$insertstring .= ' VALUES ( ';
				$insertstring .= $template_id . ' , ';
				$insertstring .= $user_id . ' );';
				if(db_insert($db_pdo, $insertstring)){
					$arr['success'] = true;
					$arr['message'] = 'User added to template';
				} else {
					$arr['success'] = false;
					$arr['error'] = 'Database execute failed';
				}
			} else {
				$arr['success'] = false;
				$arr['error'] = 'User is already assigned to this template.';
			}
			// Close connection to DB
			$db_pdo = null;
		}
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid GET values passed for template creation';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
