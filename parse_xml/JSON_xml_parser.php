<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xml/JSON_xml_parser.php


	$json = json_decode(file_get_contents(request_ldap_api("/JSON_list_jfab_users.php")), true);
	$email_to_sam_arr = array();
	foreach($json['items'] as $value){
		if(isset($value['mail']) && strlen(trim($value['mail'])) > 0){
			$email_to_sam_arr[trim($value['mail'])] = trim($value['samaccountname']);
		}
	}



	$xml = json_decode(json_encode(simplexml_load_file('cert_info.xml')), true); // all the json coding is to make it an array rather than an object

	$arr = array();
	$arr['errors'] = array();
	$arr['certs_check_problem_list'] = array();
	$arr['certs_check_problem_list']['name'] = array();
	$arr['certs_check_problem_list']['days'] = array();
	$arr['certs_check_problem_list']['expire'] = array();
	$arr['certs_check_problem_list']['ert'] = array();
	$arr['certs_check_problem_list']['safety'] = array();
	$arr['certs_check'] = array();
	$arr['certs'] = array();
	$arr['backup_user_info'] = array();
	$arr['items'] = array();
	foreach($xml['row'] as $value){
		if(isset($email_to_sam_arr[trim($value['email'])])){
			$temp_arr = array();
			$temp_arr['shortname'] = trim($value['shortname']);
			$temp_arr['raw_startdate'] = intval($value['startdate']);
			$temp_arr['start_timestamp'] = (intval($value['startdate']) - 25569) * 86400;
			$temp_arr['startdate'] = date('Y-m-d', $temp_arr['start_timestamp']);
			$temp_arr['start_timestamp_midnight'] = strtotime($temp_arr['startdate']);
			$temp_arr['dev_start_timestamp'] = date('Y-m-d H:i:s', $temp_arr['start_timestamp_midnight']);

			// $temp_arr['dev_2038_timestamp'] = strtotime('2038-1-1');

			$temp_arr['raw_enddate'] = intval($value['enddate']);


			if((intval($value['enddate']) - 25569) * 86400 < mktime(0, 0, 0, 1, 1, 2038)){ // Biggest date 32 bit PHP can handle 2038 . . . I will be 63
				$temp_arr['end_timestamp'] = (intval($value['enddate']) - 25569) * 86400;
				$temp_arr['enddate'] = date('Y-m-d H:i:s', (intval($value['enddate']) - 25569) * 86400);
			} else {
				$temp_arr['end_timestamp'] = mktime(0, 0, 0, 1, 1, 2038);
				$temp_arr['enddate'] = '2038-01-01';
			}
			if(isset($value['expire']) && strtolower(trim($value['expire'])) == 'y'){
				$temp_arr['expire'] = true;
			} else {
				$temp_arr['expire'] = false;
			}
			$arr['items'][$email_to_sam_arr[trim($value['email'])]][] = $temp_arr;
			unset($temp_arr);
			$temp_arr = array();
			if(isset($value['email']) && strlen(trim($value['email'])) > 0){
				$temp_arr['email'] = trim($value['email']);
			} else {
				$temp_arr['email'] = '';
			}
			if(isset($value['firstname']) && strlen(trim($value['firstname'])) > 0){
				$temp_arr['firstname'] = trim($value['firstname']);
			} else {
				$temp_arr['firstname'] = '';
			}
			if(isset($value['lastname']) && strlen(trim($value['lastname'])) > 0){
				$temp_arr['lastname'] = trim($value['lastname']);
			} else {
				$temp_arr['lastname'] = '';
			}
			if(!isset($arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]]['email']) || strlen(trim($arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]]['email'])) < 1 || !isset($arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]]['firstname']) || strlen(trim($arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]]['firstname'])) < 1 || !isset($arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]]['lastname']) || strlen(trim($arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]]['lastname'])) < 1){ // do not set if all values are already set
				$arr['backup_user_info'][$email_to_sam_arr[trim($value['email'])]] = $temp_arr;
			}
			unset($temp_arr);
		} else {
			$arr['errors'][] = 'No samaccountname found for: '.$value['email'];
		}
		if(isset($value['shortname']) && strlen(trim($value['shortname'])) > 0){
			$temp_arr = array();
			$temp_arr['name'] = $value['name'];
			if(!isset($arr['certs_check'][trim($value['shortname'])]['name']) || !in_array($temp_arr['name'], $arr['certs_check'][trim($value['shortname'])]['name'])){
				$arr['certs_check'][trim($value['shortname'])]['name'][] = $temp_arr['name'];
			}
			$temp_arr['days'] = $value['days'];
			if(!isset($arr['certs_check'][trim($value['shortname'])]['days']) || !in_array($temp_arr['days'], $arr['certs_check'][trim($value['shortname'])]['days'])){
				$arr['certs_check'][trim($value['shortname'])]['days'][] = $temp_arr['days'];
			}
			if(isset($value['expire']) && strtolower(trim($value['expire'])) == 'y'){
				$temp_arr['expire'] = 'Y';
			} else {
				$temp_arr['expire'] = 'N';
			}
			if(!isset($arr['certs_check'][trim($value['shortname'])]['expire']) || !in_array($temp_arr['expire'], $arr['certs_check'][trim($value['shortname'])]['expire'])){
				$arr['certs_check'][trim($value['shortname'])]['expire'][] = $temp_arr['expire'];
			}
			if(isset($value['ert']) && strtolower(trim($value['ert'])) == 'y'){
				$temp_arr['ert'] = 'Y';
			} else {
				$temp_arr['ert'] = 'N';
			}
			if(!isset($arr['certs_check'][trim($value['shortname'])]['ert']) || !in_array($temp_arr['ert'], $arr['certs_check'][trim($value['shortname'])]['ert'])){
				$arr['certs_check'][trim($value['shortname'])]['ert'][] = $temp_arr['ert'];
			}
			if(isset($value['safety']) && strtolower(trim($value['safety'])) == 'y'){
				$temp_arr['safety'] = 'Y';
			} else {
				$temp_arr['safety'] = 'N';
			}
			if(!isset($arr['certs_check'][trim($value['shortname'])]['safety']) || !in_array($temp_arr['safety'], $arr['certs_check'][trim($value['shortname'])]['safety'])){
				$arr['certs_check'][trim($value['shortname'])]['safety'][] = $temp_arr['safety'];
			}
			$arr['certs'][trim($value['shortname'])] = $temp_arr;
			unset($temp_arr);
		} else {
			$arr['errors'][] = $value;

		}
	}

	ksort($arr['certs']);


	//Now check the certs for Disimilarities
	foreach($arr['certs_check'] as $key => $value){
		if(count(array_count_values($value['name'])) > 1){
			//$arr['certs_check_problem_list'][$key][] = 'name';
			$arr['certs_check_problem_list']['name'][] = $key;
		}
		if(count(array_count_values($value['days'])) > 1){
			//$arr['certs_check_problem_list'][$key][] = 'days';
			$arr['certs_check_problem_list']['days'][] = $key;
		}

		if(count(array_count_values($value['expire'])) > 1){
			//$arr['certs_check_problem_list'][$key][] = 'expire';
			$arr['certs_check_problem_list']['expire'][] = $key;
		}

		if(count(array_count_values($value['ert'])) > 1){
			//$arr['certs_check_problem_list'][$key][] = 'ert';
			$arr['certs_check_problem_list']['ert'][] = $key;
		}

		if(count(array_count_values($value['safety'])) > 1){
			//$arr['certs_check_problem_list'][$key][] = 'safety';
			$arr['certs_check_problem_list']['safety'][] = $key;
		}
	}









	header('Content-Type: application/json');
	echo(json_encode($arr));



?>
