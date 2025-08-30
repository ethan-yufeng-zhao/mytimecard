<?php
	// JSON/JSON_ACTION_update_user_default_template.php

	require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	$arr = array();

	if(isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 && is_numeric($_GET['user_id']) && isset($_GET['old_template_id']) && strlen($_GET['old_template_id']) > 0 && is_numeric($_GET['old_template_id']) && isset($_GET['template_department_number']) && strlen($_GET['template_department_number']) > 0 && is_numeric($_GET['template_department_number']) && isset($_GET['template_name']) && strlen($_GET['template_name']) > 0) {


		$user_id = $_GET['user_id'];
		$old_old_template_id = $_GET['old_old_template_id']; // Matches the users old template (the department they transferred out of)
		$template_name = $_GET['template_name']; // this is the users new department
		$template_department_number = $_GET['template_department_number']; // this is the users new department
		$template_is_default_for_department = 1;
		$current_time = time();
		$template_when_set = $current_time;
		$template_when_modified = $current_time;
		$template_last_user = 'auto';


		try{
			$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
		} catch(PDOException $e) {
			exit($e->getMessage());
		}
		$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


		$sth_mysql = $pdo_mysql->prepare('DELETE FROM tcs.template_user_links WHERE template_id = :old_template_id AND user_id = :user_id;');
		$sth_mysql->bindParam(':old_template_id', $old_template_id, PDO::PARAM_INT);
		$sth_mysql->bindParam(':user_id', $user_id, PDO::PARAM_INT);
		$sth_mysql->execute();



		$sth_mysql = $pdo_mysql->prepare('SELECT COUNT(*) as "mycount" FROM tcs.template WHERE template_department_number = :template_department_number;');
		$sth_mysql->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
		$sth_mysql->execute();
		$countdata = $sth_mysql->fetch(PDO::FETCH_ASSOC);
		if($countdata['mycount'] > 0) { // the department exists
			$sth_mysql = $pdo_mysql->prepare('SELECT template_id FROM tcs.template WHERE template_department_number = :template_department_number;');
			$sth_mysql->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
			$sth_mysql->execute();
			$templatedata = $sth_mysql->fetch(PDO::FETCH_ASSOC);
			$template_id = $templatedata['template_id'];
		} else { // Need to create the department





		}





		// TODO: Remove user from old_old_template_id
		// TODO: see if need to make new template
		// TODO: make new template if needed
		// TODO: add user to new template for department_number



		// $sth_mysql = $pdo_mysql->prepare('UPDATE `tcs`.`user` SET user_supervisor_id = :user_supervisor_id, user_firstname = :user_firstname, user_lastname = :user_lastname, user_email = :user_email WHERE user_samaccountname = :user_samaccountname AND is_active = 1;');

		// $sth_mysql->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
		// $sth_mysql->bindParam(':user_firstname', $user_firstname, PDO::PARAM_STR);
		// $sth_mysql->bindParam(':user_lastname', $user_lastname, PDO::PARAM_STR);
		// $sth_mysql->bindParam(':user_email', $user_email, PDO::PARAM_STR);
		// $sth_mysql->bindParam(':user_supervisor_id', $user_supervisor_id, PDO::PARAM_INT);

		// if($sth_mysql->execute()) {
		// 	$arr['success'] = true;
		// } else {
		// 	$arr['success'] = false;
		// 	$arr['error'] = 'Failed on database execute';
		// }

		// Close connection to DB
		unset($pdo_mysql);

	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid get values passed';
	}


	header('Content-Type: application/json');
	echo(json_encode($arr));


?>
