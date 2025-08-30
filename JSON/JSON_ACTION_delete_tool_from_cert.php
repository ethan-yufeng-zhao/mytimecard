<?php
	// JSON/JSON_ACTION_delete_cert.php?cert_id=333&delete_cert=0&cert_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0
		&& isset($_GET['tool_name']) && strlen($_GET['tool_name']) > 0
		&& isset($_GET['tool_last_user']) && strlen($_GET['tool_last_user']) > 0 ) {
		$cert_id = (int)$_GET['cert_id'];
		$tool_name = $_GET['tool_name'];
		$tool_last_user = $_GET['tool_last_user'];

		$updatestring = '';
		$db_pdo = db_connect();

		$querystring = 'select c.entities from tcs.cert c where c.cert_id = '. $cert_id. ';';
		$tool_arr = db_query($db_pdo, $querystring);
		if ($tool_arr[0]['entities'] != null) {
			$tools = json_decode($tool_arr[0]['entities']);
		}

		if (in_array($tool_name, $tools, true)) {
			$updatestring = "UPDATE tcs.cert set entities='";
			$key = array_search($tool_name, $tools);
			if ($key > -1){
				unset($tools[$key]);
			}
			sort($tools);
			$updatestring .= json_encode($tools);
			$updatestring .= "'::jsonb where cert_id = ";
			$updatestring .= $cert_id . ';';
		}

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
			$arr['message'] = 'Tool has been deleted from the cert';
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
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
