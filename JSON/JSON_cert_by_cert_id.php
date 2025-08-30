<?php
	// JSON/JSON_cert_by_cert_id.php?cert_id=1
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id'])) {
		$cert_id = $_GET['cert_id'];
		$querystring='';

		$db_pdo = db_connect();
		//$querystring='SELECT cert_id, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.cert WHERE is_active = 1;';
		if ($GLOBALS['DB_TYPE'] == 'pgsql'){
			$querystring='SELECT * FROM tcs.cert c WHERE c.cert_id = '.$cert_id.';';
		} else { // mysql
			$querystring='SELECT cert_id, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user, is_active FROM tcs.cert WHERE cert_id = '.$cert_id.';';
		}
		$db_arr = db_query($db_pdo, $querystring);

		foreach ($db_arr as $key => $data ) {
			$arr['cert_id'] = (int)$data['cert_id'];
			$arr['cert_name'] = $data['cert_name'];
			$arr['cert_description'] = $data['cert_description'];
			$arr['cert_days_active'] = (int)$data['cert_days_active'];
			$arr['cert_notes'] = $data['cert_notes'];
			$arr['cert_never_expires'] = (int)$data['cert_never_expires'];
			$arr['cert_is_ert'] = (int)$data['cert_is_ert'];
			$arr['cert_is_iso'] = (int)$data['cert_is_iso'];
			$arr['cert_is_safety'] = (int)$data['cert_is_safety'];
			$arr['cert_last_user'] = $data['cert_last_user'];
			$arr['is_active'] = $data['is_active'];
			$arr['cert_points'] = $data['cert_points'];
			$arr['warning'] = array();
			$arr['tool'] = array();
			if ($GLOBALS['DB_TYPE'] == 'pgsql'){
				$arr['cert_when_set'] = strtotime($data['cert_when_set']);
				$arr['cert_when_modified'] = strtotime($data['cert_when_modified']);
				$arr['tool'][] = json_decode($data['entities'] ?? '{}', true);
			} else {
				$arr['cert_when_set'] = (int)$data['cert_when_set'];
				$arr['cert_when_modified'] = (int)$data['cert_when_modified'];

				$querystring = 'SELECT warning_id, cert_id, warning_number_of_days, warning_when_set, warning_when_modified, warning_last_user FROM tcs.warning w WHERE w.is_active = 1 AND w.cert_id = '.$cert_id.';';
				$warning_arr = db_query($db_pdo, $querystring);

				foreach ($warning_arr as $key => $data ) {
					$dummy = array();
					$dummy['warning_id'] = (int)$data['warning_id'];
					$dummy['warning_number_of_days'] = (int)$data['warning_number_of_days'];
					$dummy['warning_when_set'] = (int)$data['warning_when_set'];
					$dummy['warning_when_modified'] = (int)$data['warning_when_modified'];
					$dummy['warning_last_user'] = $data['warning_last_user'];
					$arr['warning'][] = $dummy;
					unset($dummy);
				}
			}
		}
		// Close connection to DB
		$db_pdo = null;
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));
?>
