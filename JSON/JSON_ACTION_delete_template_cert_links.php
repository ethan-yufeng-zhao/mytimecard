<?php
	// JSON/JSON_ACTION_delete_template_cert_links.php?template_cert_links_id=3
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['template_cert_links_id']) && strlen($_GET['template_cert_links_id']) > 0 && is_numeric($_GET['template_cert_links_id'])) {
		$template_cert_links_id = (int)$_GET['template_cert_links_id'];
		$template_id = (int)$_GET['template_id'];

		$querystring = '';
		$updatestring = '';
		$db_pdo = db_connect();
		$count_arr = array();

		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$querystring='SELECT t.certs FROM tcs.template t WHERE t.template_id = '.$template_id.';';
			$count_arr = db_query($db_pdo, $querystring);
			$certs = array();
			foreach ($count_arr as $k => $v) {
				$certs = json_decode($v['certs']);
			}

			if (in_array($template_cert_links_id, $certs, true)) {
				$updatestring = "UPDATE tcs.template set certs='";
				$key = array_search($template_cert_links_id, $certs);
				if ($key > -1){
					unset($certs[$key]);
				}
				sort($certs);
				$updatestring .= json_encode($certs);
				$updatestring .= "'::jsonb where template_id = ";
				$updatestring .= $template_id . ';';
			}
		} else {
			$updatestring = 'DELETE FROM tcs.template_cert_links WHERE template_cert_links_id = '.$template_cert_links_id.';';
		}

		if (db_update($db_pdo, $updatestring)) {
			$arr['success'] = true;
			$arr['message'] = 'Cert has been unlinked from template';
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
