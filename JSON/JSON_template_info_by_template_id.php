<?php
	// JSON/JSON_template_info_by_template_id.php?template_id=50
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();
	$arr['items'] = array();
	$arr['count'] = 0;

	if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
		$template_id = $_GET['template_id'];

		$querystring='';
		$db_pdo = db_connect();

		if ($GLOBALS['DB_TYPE'] == 'pgsql'){
			$querystring='select * from tcs.template t where t.template_id = '.$template_id.';';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data) {
				$arr['items'][$template_id]['template_id'] = $template_id;
				$arr['items'][$template_id]['template_name'] = $data['template_name'];

				$arr['items'][$template_id]['template_is_default_for_department'] = (bool)$data['template_is_default_for_department'];
				$arr['items'][$template_id]['template_department_number'] = $data['template_department_number'];
				$arr['items'][$template_id]['template_when_set'] = strtotime($data['template_when_set']);
				$arr['items'][$template_id]['template_when_modified'] = strtotime($data['template_when_modified']);

				$arr['items'][$template_id]['usercount'] = count(json_decode($data['users'] ?? '{}', true));

				$cert_ids = json_decode($data['certs']);
				$arr['items'][$template_id]['certcount'] = count($cert_ids);

				$arr['count']++;

				if (count($cert_ids) > 0) {
					$querystring = 'SELECT * FROM tcs.cert c WHERE c.cert_id in ('.implode(',',$cert_ids).') ORDER BY c.cert_id;';
					$certs_arr = db_query($db_pdo, $querystring);
					if ($certs_arr != null && count($certs_arr) >0 ) {
						foreach ($certs_arr as $key => $certdata) {
							$temp_arr = array();
							//$temp_arr['template_cert_links_id'] = (int)$certdata['template_cert_links_id'];
							$temp_arr['cert_id'] = (int)$certdata['cert_id'];
							$temp_arr['cert_name'] = $certdata['cert_name'];
							$temp_arr['cert_description'] = $certdata['cert_description'];
							$temp_arr['cert_notes'] = $certdata['cert_notes'];
							$temp_arr['cert_days_active'] = (int)$certdata['cert_days_active'];
							$temp_arr['cert_never_expires'] = (int)$certdata['cert_never_expires'];
							$temp_arr['cert_is_ert'] = (int)$certdata['cert_is_ert'];
							$temp_arr['cert_is_iso'] = (int)$certdata['cert_is_iso'];
							$temp_arr['cert_is_safety'] = (int)$certdata['cert_is_safety'];

							$arr['items'][$template_id]['certs'][$temp_arr['cert_id']] = $temp_arr;
							unset($temp_arr);
							$arr['items'][$template_id]['certcount']++;
						}
					} else {
						$arr['items'][$template_id]['certs'] = [];
						$arr['items'][$template_id]['certcount'] = 0;
					}
				} else {
					$arr['items'][$template_id]['certs'] = [];
					$arr['items'][$template_id]['certcount'] = 0;
				}
			}
		} else { // mysql
			$querystring = 'SELECT template_id, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user FROM tcs.template WHERE is_active = 1 AND template_id = ' . $template_id . ';';
			$db_arr = db_query($db_pdo, $querystring);

			foreach ($db_arr as $key => $data) {
				$arr['items'][$template_id]['template_id'] = $template_id;
				$arr['items'][$template_id]['template_name'] = $data['template_name'];

				$arr['items'][$template_id]['template_is_default_for_department'] = (bool)$data['template_is_default_for_department'];
				$arr['items'][$template_id]['template_department_number'] = $data['template_department_number'];
				$arr['items'][$template_id]['template_when_set'] = $data['template_when_set'];
				$arr['items'][$template_id]['template_when_modified'] = $data['template_when_modified'];

				$querystring = "SELECT COUNT(*) AS 'mycount' FROM tcs.template_user_links WHERE template_id = " . $template_id . ";";
				$usercountdata = db_query($db_pdo, $querystring);
				$arr['items'][$template_id]['usercount'] = intval($usercountdata[0]['mycount']);
				$arr['items'][$template_id]['certcount'] = 0;
				$arr['count']++;

				$querystring = 'SELECT template_cert_links_id, cert.cert_id, cert_name, cert_description, cert_days_active, cert_never_expires, cert_is_ert, cert_is_safety FROM tcs.template_cert_links JOIN cert ON (cert.cert_id = template_cert_links.cert_id) WHERE cert.is_active = 1 AND template_cert_links.template_id = ' . $template_id . ' ORDER BY template_cert_links.cert_id;';
				$certs_arr = db_query($db_pdo, $querystring);
				$mycount = 0;
				foreach ($certs_arr as $key => $certdata) {
					$temp_arr = array();
					$temp_arr['template_cert_links_id'] = (int)$certdata['template_cert_links_id'];
					$temp_arr['cert_id'] = (int)$certdata['cert_id'];
					$temp_arr['cert_name'] = $certdata['cert_name'];
					$temp_arr['cert_description'] = $certdata['cert_description'];
					//$temp_arr['cert_notes'] = $certdata['cert_notes'];
					$temp_arr['cert_days_active'] = (int)$certdata['cert_days_active'];
					$temp_arr['cert_never_expires'] = (int)$certdata['cert_never_expires'];
					$temp_arr['cert_is_ert'] = (int)$certdata['cert_is_ert'];
					//$temp_arr['cert_is_iso'] = (int)$certdata['cert_is_iso'];
					$temp_arr['cert_is_safety'] = (int)$certdata['cert_is_safety'];

					$arr['items'][$template_id]['certs'][$temp_arr['template_cert_links_id']] = $temp_arr;

					unset($temp_arr);
					$arr['items'][$template_id]['certcount']++;
				}
			}
		}

		// Close connection to DB
		$db_pdo = null;
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));
?>
