<?php
	// JSON/JSON_ACTION_add_user_cert.php?cert_id=2&user_id=1&user_cert_last_user=jcubic&user_cert_date_granted=1


	// JSON/JSON_ACTION_add_user_cert.php?add_user_cert=1&cert_id=6&add_user_cert_cert_name=BiMedEval&add_user_cert_username=jcubic&user_id=1&user_cert_last_user=jcubic&user_cert_date_granted=10%2F10%2F2013


    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	$arr = array();

	if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0 && isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 && is_numeric($_GET['user_id']) && $_GET['user_id'] > 0


		&& isset($_GET['user_cert_date_granted']) && strlen($_GET['user_cert_date_granted']) > 0 && is_numeric($_GET['user_cert_date_granted']) && $_GET['user_cert_date_granted'] > 0 && isset($_GET['user_cert_last_user'])) {



		try{
			$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
		} catch(PDOException $e) {
			exit($e->getMessage());
		}
		$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$cert_id = $_GET['cert_id'];
		$user_id = $_GET['user_id'];
		$user_cert_date_granted = $_GET['user_cert_date_granted'];
		$current_time = time();
		$user_cert_date_set = $current_time;
		$user_cert_date_modified = $current_time;
		$user_cert_last_user = $_GET['user_cert_last_user'];







		$sth_mysql_user_cert_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.user_cert WHERE is_active = 1 AND user_id = :user_id AND cert_id = :cert_id AND user_cert_date_granted = :user_cert_date_granted;');
		$sth_mysql_user_cert_count->bindParam(':user_id', $user_id, PDO::PARAM_INT);
		$sth_mysql_user_cert_count->bindParam(':cert_id', $cert_id, PDO::PARAM_INT);
		$sth_mysql_user_cert_count->bindParam(':user_cert_date_granted', $user_cert_date_granted, PDO::PARAM_INT);
		$sth_mysql_user_cert_count->execute();
		$count_mysql_user_cert = $sth_mysql_user_cert_count->fetch(PDO::FETCH_ASSOC);
		if($count_mysql_user_cert['mycount'] < 1){
			$sth_mysql_user_cert_insert = $pdo_mysql->prepare('INSERT INTO tcs.user_cert (cert_id, user_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user) VALUES (:cert_id, :user_id, :user_cert_date_granted, :user_cert_date_set, :user_cert_date_modified, :user_cert_last_user);');
			$sth_mysql_user_cert_insert->bindParam(':cert_id', $cert_id, PDO::PARAM_INT);
			$sth_mysql_user_cert_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
			$sth_mysql_user_cert_insert->bindParam(':user_cert_date_granted', $user_cert_date_granted, PDO::PARAM_INT);
			$sth_mysql_user_cert_insert->bindParam(':user_cert_date_set', $user_cert_date_set, PDO::PARAM_INT);
			$sth_mysql_user_cert_insert->bindParam(':user_cert_date_modified', $user_cert_date_modified, PDO::PARAM_INT);
			$sth_mysql_user_cert_insert->bindParam(':user_cert_last_user', $user_cert_last_user, PDO::PARAM_STR);
			if($sth_mysql_user_cert_insert->execute()){
				$arr['success'] = true;

			} else {
				$arr['success'] = false;
				$arr['error'] = 'Database execute failed';
			}
		} else {
			$arr['success'] = false;
			$arr['error'] = 'user_cert already in database - cert_id: '.$cert_id.' - user_id: '.$user_id.' - user_cert_date_granted: '.$user_cert_date_granted;
		}





















		// $sth_mysql = $pdo_mysql->prepare('INSERT INTO tcs.user_cert (cert_id, user_id, user_cert_date_granted, user_cert_date_set, user_cert_date_modified, user_cert_last_user) VALUES (:cert_id, :user_id, :user_cert_date_granted, :user_cert_date_set, :user_cert_date_modified, :user_cert_last_user);');
		// $sth_mysql->bindParam(':cert_id', $cert_id, PDO::PARAM_INT);
		// $sth_mysql->bindParam(':user_id', $user_id, PDO::PARAM_INT);
		// $sth_mysql->bindParam(':user_cert_date_granted', $user_cert_date_granted, PDO::PARAM_INT);
		// $sth_mysql->bindParam(':user_cert_date_set', $user_cert_date_set, PDO::PARAM_INT);
		// $sth_mysql->bindParam(':user_cert_date_modified', $user_cert_date_modified, PDO::PARAM_INT);
		// $sth_mysql->bindParam(':user_cert_last_user', $user_cert_last_user, PDO::PARAM_STR);



		// Close connection to DB
		unset($pdo_mysql);

	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid POST values passed for user cert update';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
