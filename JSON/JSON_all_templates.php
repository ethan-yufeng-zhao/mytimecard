<?php
	// JSON/JSON_all_templates.php
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	$db_dept_arr = array();
	$dept_arr = array();

	$querystring='';
	$db_pdo = db_connect();

	if ($GLOBALS['DB_TYPE'] == 'pgsql') {
		//query department name
		$querystring = 'select u.departmentnumber, u.department from tcs."user" u';
		$db_dept_arr = db_query($db_pdo, $querystring);
		//get and check department
		foreach ($db_dept_arr as $key => $value) {
			if (!key_exists($value['departmentnumber'], $dept_arr)){
				$dept_arr[$value['departmentnumber']] = $value['department'];
				continue;
			}
			if ($value['department'] != $dept_arr[$value['departmentnumber']]
				&& !endsWith($dept_arr[$value['departmentnumber']], $value['departmentnumber']) ){
				$dept_arr[$value['departmentnumber']] = $value['department'];
				//logit('['.$value['departmentnumber'].'] :  '.$value['department'].' != '.$dept_arr[$value['departmentnumber']]);
			}
		}
		//$querystring='SELECT t.* FROM tcs.template t WHERE t.is_active = true order by t.template_id;';
		$querystring='SELECT t.* FROM tcs.template t order by t.template_id;';
	} else { // mysql
		//$querystring='SELECT template.template_id, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, (SELECT COUNT(*) FROM tcs.template_user_links WHERE template_user_links.template_id = template.template_id) AS "usercount", (SELECT COUNT(*) FROM tcs.template_cert_links WHERE template_cert_links.template_id = template.template_id) AS "certcount" FROM tcs.template WHERE is_active = 1;';
		$querystring='SELECT template.template_id, template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user, (SELECT COUNT(*) FROM tcs.template_user_links WHERE template_user_links.template_id = template.template_id) AS "usercount", (SELECT COUNT(*) FROM tcs.template_cert_links WHERE template_cert_links.template_id = template.template_id) AS "certcount", is_active FROM tcs.template ;';
	}
	$db_arr = db_query($db_pdo, $querystring);

	foreach ($db_arr as $key => $data ) {
		$temp_arr = array();
		$temp_arr['template_id'] = (int)$data['template_id'];
		$temp_arr['template_name'] = $data['template_name'];
		$temp_arr['template_is_default_for_department'] = (int)$data['template_is_default_for_department'];
		$temp_arr['template_department_number'] = (int)$data['template_department_number'];

		if ($GLOBALS['DB_TYPE'] == 'pgsql') {
			if (key_exists($data['template_department_number'], $dept_arr)) {
				$temp_arr['template_department_name'] = $dept_arr[$data['template_department_number']];
			} else {
				$temp_arr['template_department_name'] = '';
			}
			$temp_arr['usercount'] = count(json_decode($data['users'] ?? '{}', true));
			$temp_arr['certcount'] = count(json_decode($data['certs'] ?? '{}', true));
			$temp_arr['template_when_set'] = $data['template_when_set'];
			$temp_arr['template_when_modified'] = $data['template_when_modified'];
		} else{ // mysql
			$temp_arr['template_department_name'] = '';
			$temp_arr['usercount'] = (int)$data['usercount'];
			$temp_arr['certcount'] = (int)$data['certcount'];
			$temp_arr['template_when_set'] = (int)$data['template_when_set'];
			$temp_arr['template_when_modified'] = (int)$data['template_when_modified'];
		}
		$temp_arr['template_last_user'] = $data['template_last_user'];
		$temp_arr['template_is_active'] = $data['is_active'];

		$arr[$temp_arr['template_id']] = $temp_arr;
		unset($temp_arr);
	}
	// Close connection to DB
	$db_pdo = null;

	header('Content-Type: application/json');
	$json_arr = json_encode($arr);
	//logit($json_arr);
	echo($json_arr);

?>
