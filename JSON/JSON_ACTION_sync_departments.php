<?php
	// JSON/JSON_ACTION_sync_departments.php
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	$current_timestamp = time();
	$arr = array();

	try{
		$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
	$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$json = json_decode(file_get_contents(request_ldap_api("/JSON_list_jfab_users.php")), true);

	foreach($json['items'] as $value){ // First load the deparment array
		if(isset($value['departmentNumber']) && strlen($value['departmentNumber']) > 0){
			$ldap_arr[intval($value['departmentNumber'])] = $value['department'];
		}
	}

	foreach($ldap_arr as $key => $value){
		$template_name = $value;
		$template_is_default_for_department = 1;
		$template_department_number = $key;
		$template_when_set = $current_timestamp;
		$template_when_modified = $current_timestamp;
		$template_last_user = 'auto';
		$sth_mysql_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.template WHERE is_active = 1 AND template_department_number = :template_department_number;');
		$sth_mysql_count->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
		$sth_mysql_count->execute();
		$count_mysql = $sth_mysql_count->fetch(PDO::FETCH_ASSOC);
		if($count_mysql['mycount'] < 1){
			$sth_mysql_insert = $pdo_mysql->prepare('INSERT INTO tcs.template (template_name, template_is_default_for_department, template_department_number, template_when_set, template_when_modified, template_last_user) VALUES (:template_name, :template_is_default_for_department, :template_department_number, :template_when_set, :template_when_modified, :template_last_user);');
			$sth_mysql_insert->bindParam(':template_name', $template_name, PDO::PARAM_STR);
			$sth_mysql_insert->bindParam(':template_is_default_for_department', $template_is_default_for_department, PDO::PARAM_INT);
			$sth_mysql_insert->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
			$sth_mysql_insert->bindParam(':template_when_set', $template_when_set, PDO::PARAM_INT);
			$sth_mysql_insert->bindParam(':template_when_modified', $template_when_modified, PDO::PARAM_INT);
			$sth_mysql_insert->bindParam(':template_last_user', $template_last_user, PDO::PARAM_STR);
			$sth_mysql_insert->execute();
			$arr['items'][$template_department_number]['success'] = true; // 'Department: '.$template_name.' Inserted into database';
			$arr['items'][$template_department_number]['template_id'] = (int)$pdo_mysql->lastInsertId();
		} else {
			$sth_mysql_get_id = $pdo_mysql->prepare('SELECT template_id FROM tcs.template WHERE template_is_default_for_department = 1 AND template_department_number = :template_department_number AND is_active = 1;');
			$sth_mysql_get_id->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
			$sth_mysql_get_id->execute();
			$template_mysql_get_id = $sth_mysql_get_id->fetch(PDO::FETCH_ASSOC);
			$arr['items'][$template_department_number]['success'] = true;
			$arr['items'][$template_department_number]['template_id'] = (int)$template_mysql_get_id['template_id'];
		}
	}

	// Close connection to DB
	unset($pdo_mysql);


	header('Content-Type: application/json');
	echo(json_encode($arr));
?>
