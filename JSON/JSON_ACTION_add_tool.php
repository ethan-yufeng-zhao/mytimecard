<?php
	//JSON/JSON_ACTION_add_tool.php?cert_id=1&selected_Tools=COFA_IX725&tool_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0
		&& isset($_GET['selected_Tools']) && strlen($_GET['selected_Tools']) > 0
		&& isset($_GET['tool_last_user']) && strlen($_GET['tool_last_user']) > 0 ) {
		$cert_id = (int)$_GET['cert_id'];
		$selected_Tools = $_GET['selected_Tools'];
		$tool_last_user = $_GET['tool_last_user'];

		$selected = explode(',', $selected_Tools);
		$tools = array();
		$updatestring = '';
		$db_pdo = db_connect();

		$querystring = 'select c.entities from tcs.cert c where c.cert_id = '. $cert_id. ';';
		$tool_arr = db_query($db_pdo, $querystring);
		if ($tool_arr[0]['entities'] != null) {
			$tools = json_decode($tool_arr[0]['entities']);
		}

		$allinarray = true;
		$updatestring = "UPDATE tcs.cert set entities='";
		foreach ($selected as $k => $which_tool){
			if (!in_array($which_tool, $tools, true)) {
				$allinarray = false;
				$tools[] = $which_tool;
			}
		}
		sort($tools);
		$updatestring .= json_encode($tools);
		$updatestring .= "'::jsonb where cert_id = ";
		$updatestring .= $cert_id.';';

		if(!$allinarray){
			if (db_update($db_pdo, $updatestring)) {
				$arr['success'] = true;
				$arr['message'] = 'Tools have been added to cert';
			} else {
				$arr['success'] = false;
				$arr['error'] = 'Database execute failed';
			}
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Tools are already assigned to this cert.';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid POST values passed for cert update';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
