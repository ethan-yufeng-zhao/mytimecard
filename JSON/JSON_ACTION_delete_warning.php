<?php
	// JSON/JSON_ACTION_delete_warning.php?warning_id=1&delete_warning=0&warning_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	$arr = array();

	if(isset($_GET['warning_id']) && strlen($_GET['warning_id']) > 0 && is_numeric($_GET['warning_id']) && $_GET['warning_id'] > 0
		&& isset($_GET['delete_warning']) && strlen($_GET['delete_warning']) > 0 && is_numeric($_GET['delete_warning'])
		&& isset($_GET['warning_last_user'])) {
		try{
			$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
		} catch(PDOException $e) {
			exit($e->getMessage());
		}
		$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$warning_id = $_GET['warning_id'];
		$is_active = $_GET['delete_warning'];
		$warning_when_modified = time();
		$warning_last_user = $_GET['warning_last_user'];

		$sth_mysql = $pdo_mysql->prepare('UPDATE tcs.warning SET warning_when_modified = :warning_when_modified, warning_last_user = :warning_last_user, is_active = :is_active WHERE warning_id = :warning_id;');
		$sth_mysql->bindParam(':warning_id', $warning_id, PDO::PARAM_INT);
		$sth_mysql->bindParam(':is_active', $is_active, PDO::PARAM_INT);
		$sth_mysql->bindParam(':warning_when_modified', $warning_when_modified, PDO::PARAM_INT);
		$sth_mysql->bindParam(':warning_last_user', $warning_last_user, PDO::PARAM_STR);
		$sth_mysql->execute();

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
		$arr['error'] = 'invalid POST values passed for cert update';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));

?>
