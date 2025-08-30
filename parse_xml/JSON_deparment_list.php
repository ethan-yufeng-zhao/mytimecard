<?php
	// parse_xml/JSON_deparment_list.php

	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');




	$json = json_decode(file_get_contents(request_ldap_api("/JSON_list_jfab_users.php")), true);
	$arr = array();



	foreach($json['items'] as $value){ // First load the deparment array
		if(isset($value['departmentNumber']) && strlen($value['departmentNumber']) > 0){
			$arr['department'][intval($value['departmentNumber'])] = $value['department'];
		}
	}





	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
