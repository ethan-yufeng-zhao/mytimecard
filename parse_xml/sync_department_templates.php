<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xml/sync_department_templates.php

	try{
        $pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
	$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


	$json = json_decode(file_get_contents(request_json_api('/parse_xml/JSON_deparment_list.php')), true);
	$current_timestamp = time();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sync LDAP departments to database</title>
    <link rel="stylesheet" href="css/style.css">
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body id="home">

<h2>Sync LDAP departments to database</h2>
<div style="margin-left:4em;">
<?php
	foreach($json['department'] as $key => $value){
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
			echo('Department: '.$template_name.' Inserted into database');
			echo('<br>');
		} else {
			echo('Department: '.$template_name.' already in database');
			echo('<br>');
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