<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xml/sync_xml_to_db.php

	try{
        $pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
	$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


	$json = json_decode(file_get_contents(request_json_api('/parse_xml/JSON_xml_parser.php')), true);
	$current_timestamp = time();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sync XML to database</title>
    <link rel="stylesheet" href="css/style.css">
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body id="home">

<h2>Sync xml to database</h2>
<div style="margin-left:4em;">
<?php
	$cert_arr = array();
	foreach($json['certs'] as $key => $value){
		$cert_name = $key;
		$cert_description = $value['name'];
		$cert_days_active = intval($value['days']);
		$cert_notes = 'default warning text goes here.';
		if($value['expire'] == 'Y'){
			$cert_never_expires = 0;
		} else {
			$cert_never_expires = 1;
		}
		if($value['ert'] == 'Y'){
			$cert_is_ert = 1;
		} else {
			$cert_is_ert = 0;
		}
		if($value['safety'] == 'Y'){
			$cert_is_safety = 1;
		} else {
			$cert_is_safety = 0;
		}
		$cert_when_set = $current_timestamp;
		$cert_when_modified = $current_timestamp;
		$cert_last_user = 'auto';


		$sth_mysql_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.cert WHERE cert_name = :cert_name AND is_active = 1;');
		$sth_mysql_count->bindParam(':cert_name', $cert_name, PDO::PARAM_STR);
		$sth_mysql_count->execute();
		$count_mysql_cert = $sth_mysql_count->fetch(PDO::FETCH_ASSOC);
		if($count_mysql_cert['mycount'] < 1){
			$sth_mysql_cert_insert = $pdo_mysql->prepare('INSERT INTO tcs.cert (cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user) VALUES (:cert_name, :cert_description, :cert_days_active, :cert_notes, :cert_never_expires, :cert_is_ert, :cert_is_safety, :cert_when_set, :cert_when_modified, :cert_last_user);');
			$sth_mysql_cert_insert->bindParam(':cert_name', $cert_name, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_description', $cert_description, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_days_active', $cert_days_active, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_notes', $cert_notes, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_never_expires', $cert_never_expires, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_is_ert', $cert_is_ert, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_is_safety', $cert_is_safety, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_when_set', $cert_when_set, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_when_modified', $cert_when_modified, PDO::PARAM_STR);
			$sth_mysql_cert_insert->bindParam(':cert_last_user', $cert_last_user, PDO::PARAM_STR);
			$sth_mysql_cert_insert->execute();
			$cert_id = $pdo_mysql->lastInsertId();
			echo('Cert: '.$cert_name.' Inserted into database - cert_id: '.$cert_id);
			echo('<br>');
			$warning_number_of_days = 60;
			$warning_when_set = $current_timestamp;
			$warning_when_modified = $current_timestamp;
			$warning_last_user = 'auto';
			if($cert_never_expires == 0){ //Only set a warning if the cert expires
				$sth_mysql_warn_insert = $pdo_mysql->prepare('INSERT INTO tcs.warning (cert_id, warning_number_of_days, warning_when_set, warning_when_modified, warning_last_user) VALUES (:cert_id, :warning_number_of_days, :warning_when_set, :warning_when_modified, :warning_last_user);');
				$sth_mysql_warn_insert->bindParam(':cert_id', $cert_id, PDO::PARAM_STR);
				$sth_mysql_warn_insert->bindParam(':warning_number_of_days', $warning_number_of_days, PDO::PARAM_STR);
				$sth_mysql_warn_insert->bindParam(':warning_when_set', $warning_when_set, PDO::PARAM_STR);
				$sth_mysql_warn_insert->bindParam(':warning_when_modified', $warning_when_modified, PDO::PARAM_STR);
				$sth_mysql_warn_insert->bindParam(':warning_last_user', $warning_last_user, PDO::PARAM_STR);
				$sth_mysql_warn_insert->execute();
			}
		} else {
			$sth_mysql_get_cert_id = $pdo_mysql->prepare('SELECT cert_id FROM tcs.cert WHERE is_active = 1 AND cert_name = :cert_name;');
			$sth_mysql_get_cert_id->bindParam(':cert_name', $cert_name, PDO::PARAM_STR);
			$sth_mysql_get_cert_id->execute();
			$cert_mysql_get_id = $sth_mysql_get_cert_id->fetch(PDO::FETCH_ASSOC);
			$cert_id = $cert_mysql_get_id['cert_id'];
			echo('Cert: '.$cert_name.' already in database - cert_id: '.$cert_id);
			echo('<br>');
		}
		$cert_arr[$cert_name] = $cert_id;
	}

	$json_ldap = json_decode(file_get_contents(request_ldap_api("/JSON_list_jfab_users.php")), true);
	$user_ldap_arr = array();
	foreach($json_ldap['items'] as $value){  // Get all user info and order it by samaccountname
		if(isset($value['samaccountname']) && strlen(trim($value['samaccountname'])) > 0){
			//$user_ldap_arr[trim($value['samaccountname'])] = array('samaccountname' => trim($value['samaccountname']), 'firstname' => trim($value['givenname']), 'lastname' => trim($value['sn']), 'email' => trim($value['mail']));
			$user_ldap_arr[trim($value['samaccountname'])] = array('firstname' => trim($value['givenname']), 'lastname' => trim($value['sn']), 'email' => trim($value['mail']));
		}
	}

	$user_arr = array();

	foreach($json['items'] as $key => $value){  // make sure the users are in the system
		$user_samaccountname = $key;
		if(isset($user_ldap_arr[$key])){  //Prefer to get info from ldap
			$user_firstname = $user_ldap_arr[$key]['firstname'];
			$user_lastname = $user_ldap_arr[$key]['lastname'];
			$user_email = $user_ldap_arr[$key]['email'];
		} else {
			echo('WARNING: User "'.$key.'" is not in LDAP.');
			echo('<br>');
			$user_firstname = $json['backup_user_info'][$key]['firstname'];
			$user_lastname = $json['backup_user_info'][$key]['lastname'];
			$user_email = $json['backup_user_info'][$key]['email'];
		}


		$sth_mysql_user_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.user WHERE is_active = 1 AND user_samaccountname = :user_samaccountname;');
		$sth_mysql_user_count->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
		$sth_mysql_user_count->execute();
		$usercount_mysql_cert = $sth_mysql_user_count->fetch(PDO::FETCH_ASSOC);
		if($usercount_mysql_cert['mycount'] < 1){
			$sth_mysql_user_insert = $pdo_mysql->prepare('INSERT INTO tcs.user (user_samaccountname, user_firstname, user_lastname, user_email) VALUES (:user_samaccountname, :user_firstname, :user_lastname, :user_email);');
			$sth_mysql_user_insert->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
			$sth_mysql_user_insert->bindParam(':user_firstname', $user_firstname, PDO::PARAM_STR);
			$sth_mysql_user_insert->bindParam(':user_lastname', $user_lastname, PDO::PARAM_STR);
			$sth_mysql_user_insert->bindParam(':user_email', $user_email, PDO::PARAM_STR);
			$sth_mysql_user_insert->execute();
			$user_id = $pdo_mysql->lastInsertId();
			echo('User: '.$user_samaccountname.' Inserted into database - user_id: '.$user_id);
			echo('<br>');
		} else {
			$sth_mysql_get_user_id = $pdo_mysql->prepare('SELECT user_id FROM tcs.user WHERE is_active = 1 AND user_samaccountname = :user_samaccountname;');
			$sth_mysql_get_user_id->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
			$sth_mysql_get_user_id->execute();
			$user_mysql_get_id = $sth_mysql_get_user_id->fetch(PDO::FETCH_ASSOC);
			$user_id = $user_mysql_get_id['user_id'];
			echo('User: '.$user_samaccountname.' already in database - user_id: '.$user_id);
			echo('<br>');
		}
		$user_arr[$user_samaccountname] = $user_id;
	}

	foreach($json['items'] as $samkey => $valuearr){  // update the users certs
		foreach($valuearr as $value){  // update the users certs
			if(isset($cert_arr[trim($value['shortname'])])){
				$cert_id = $cert_arr[trim($value['shortname'])];
			} else {
				exit('ERROR: cert_id unable to be found for: '.$value['shortname']);
			}
			if(isset($user_arr[$samkey])){
				$user_id = $user_arr[$samkey];
			} else {
				exit('ERROR: user_id unable to be found for: '.$samkey);
			}
			$user_cert_date_granted = $value['start_timestamp_midnight'];
			//$user_cert_date_expire = $value['end_timestamp'];
			$user_cert_date_set = $current_timestamp;
			$user_cert_date_modified = $current_timestamp;
			$user_cert_last_user = 'auto';
			$sth_mysql_user_cert_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.user_cert WHERE is_active = 1 AND user_id = :user_id AND cert_id = :cert_id AND user_cert_date_granted = :user_cert_date_granted;');
			$sth_mysql_user_cert_count->bindParam(':user_id', $user_id, PDO::PARAM_STR);
			$sth_mysql_user_cert_count->bindParam(':cert_id', $cert_id, PDO::PARAM_STR);
			$sth_mysql_user_cert_count->bindParam(':user_cert_date_granted', $user_cert_date_granted, PDO::PARAM_STR);
			$sth_mysql_user_cert_count->execute();
			$count_mysql_user_cert = $sth_mysql_user_cert_count->fetch(PDO::FETCH_ASSOC);
			if($count_mysql_user_cert['mycount'] < 1){
				$sth_mysql_user_cert_insert = $pdo_mysql->prepare('INSERT INTO tcs.user_cert (cert_id, user_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user) VALUES (:cert_id, :user_id, :user_cert_date_granted, :user_cert_date_set, :user_cert_date_modified, :user_cert_last_user);');
				$sth_mysql_user_cert_insert->bindParam(':cert_id', $cert_id, PDO::PARAM_INT);
				$sth_mysql_user_cert_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
				$sth_mysql_user_cert_insert->bindParam(':user_cert_date_granted', $user_cert_date_granted, PDO::PARAM_INT);
				// $sth_mysql_user_cert_insert->bindParam(':user_cert_date_expire', $user_cert_date_expire, PDO::PARAM_INT);
				$sth_mysql_user_cert_insert->bindParam(':user_cert_date_set', $user_cert_date_set, PDO::PARAM_INT);
				$sth_mysql_user_cert_insert->bindParam(':user_cert_date_modified', $user_cert_date_modified, PDO::PARAM_INT);
				$sth_mysql_user_cert_insert->bindParam(':user_cert_last_user', $user_cert_last_user, PDO::PARAM_STR);
				$sth_mysql_user_cert_insert->execute();
				echo('user_cert inserted into database - cert_id: '.$cert_id.' - user_id: '.$user_id.' - user_cert_date_granted: '.$user_cert_date_granted);
				echo('<br>');
			} else {
				echo('user_cert already in database - cert_id: '.$cert_id.' - user_id: '.$user_id.' - user_cert_date_granted: '.$user_cert_date_granted);
				echo('<br>');
			}
		}
	}
?>
</div>
</body>
</html>
<?php
	// Close connection to DB
	$pdo_mysql = null;
?>
