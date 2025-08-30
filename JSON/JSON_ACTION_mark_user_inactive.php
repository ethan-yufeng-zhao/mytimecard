<?php
	// JSON/JSON_ACTION_mark_user_inactive.php?user_id=1
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	$arr = array();

	if(isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 && is_numeric($_GET['user_id'])) {
		$user_id = intval($_GET['user_id']);

		try{
			$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
		} catch(PDOException $e) {
			exit($e->getMessage());
		}
		$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$sth_mysql = $pdo_mysql->prepare('UPDATE tcs.`user` SET is_active = 0 WHERE user_id = :user_id;');
		$sth_mysql->bindParam(':user_id', $user_id, PDO::PARAM_INT);
		if($sth_mysql->execute()){
			$arr['success'] = true;
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Database execute failed';
		}

		// Close connection to DB
		unset($pdo_mysql);

	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid get values passed';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
