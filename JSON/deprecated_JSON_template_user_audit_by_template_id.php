<?php
	// JSON/JSON_template_user_audit_by_template_id.php?template_id=3

	require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	try{
		$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
	$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$arr = array();
	$arr['items'] = array();



	if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
		$template_id = intval($_GET['template_id']);


		$json_certs_in_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_template_info_by_template_id.php?template_id='.$template_id) , false, getContextCookies()), true);

		$json_users_in_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_users_in_template_by_template_id.php?template_id='.$template_id) , false, getContextCookies()), true);

		$json_user_certs_that_match_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users_certs.php?template_id='.$template_id)), true);


		foreach($json_users_in_template['items'][$template_id]['users'] as $template_user_links_id => $user_value) {
			$user_id = $user_value['user_id'];
			foreach($json_certs_in_template['items'][$template_id]['certs'] as $template_cert_links_id => $cert_value) {
				$cert_id = $cert_value['cert_id'];


				if(isset($json_user_certs_that_match_template['items'][$user_id]) && isset($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id])) {

					$largest_key = max(array_keys($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id])); // This makes sure that we are always looking at the newest certification




				} else {

					// This means that the user does not have the cert


				}


				// $arr['items'][$template_id]['certs'][$template_cert_links_id]





			}
		}














	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

	// Close connection to DB
	unset($pdo_mysql);
?>
