<?php
	// JSON/JSON_ACTION_add_cert_to_template.php?template_id=41&cert_id=12
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if (isset($_GET['template_id']) && is_numeric($_GET['template_id']) && $_GET['template_id'] > 0
		&& isset($_GET['cert_id']) && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0) {
		$template_id = (int)$_GET['template_id'];
		$cert_id = (int)$_GET['cert_id'];

		$querystring = '';
		$updatestring = '';
		$db_pdo = db_connect();
		$count_arr = array();

		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			$querystring='SELECT t.certs FROM tcs.template t WHERE t.template_id = '.$template_id.';';
			$count_arr = db_query($db_pdo, $querystring);
			$certs = array();
			foreach ($count_arr as $k => $v) {
				$certs = json_decode($v['certs'] ?? '{}', true);
			}

			if (!in_array($cert_id, $certs, true)) {
				$updatestring = "UPDATE tcs.template set certs='";
				$certs[] = $cert_id;
				sort($certs);
				$updatestring .= json_encode($certs);
				$updatestring .= "'::jsonb where template_id = ";
				$updatestring .= $template_id.';';

				if (db_update($db_pdo, $updatestring)) {
					$arr['success'] = true;
					$arr['message'] = 'Cert added to template';
				} else {
					$arr['success'] = false;
					$arr['error'] = 'Database execute failed';
				}
			} else {
				$arr['success'] = false;
				$arr['error'] = 'Cert is already assigned to this template.';
			}
		} else {
			$querystring='SELECT COUNT(*) as "mycount" FROM tcs.template_cert_links WHERE template_id = '.$template_id.' AND cert_id = '.$cert_id.';';
			$count_arr = db_query($db_pdo, $querystring);

			if ($count_arr[0]['mycount'] < 1) {
				$updatestring = 'INSERT INTO tcs.template_cert_links ';
				$updatestring .= ' (template_id, cert_id) ';
				$updatestring .= ' VALUES ( ';
				$updatestring .= $template_id.', ';
				$updatestring .= $cert_id.' );';

				if (db_insert($db_pdo, $updatestring)) {
					$arr['success'] = true;
					$arr['message'] = 'Cert added to template';
				} else {
					$arr['success'] = false;
					$arr['error'] = 'Database execute failed';
				}
			} else {
				$arr['success'] = false;
				$arr['error'] = 'Cert is already assigned to this template.';
			}
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid GET values passed for template_cert_links insert';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
