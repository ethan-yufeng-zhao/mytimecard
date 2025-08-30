<?php
	// JSON/JSON_ACTION_add_warning.php?cert_id=1&warning_number_of_days=21&warning_last_user=jcubic
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');

	$arr = array();

	// if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id']) && $_GET['cert_id'] > 0 && isset($_GET['warning_number_of_days']) && strlen($_GET['warning_number_of_days']) > 0 && is_numeric($_GET['warning_number_of_days']) && $_GET['warning_number_of_days'] >= 0 && $_GET['warning_number_of_days'] < 18251 && isset($_GET['warning_last_user'])) {
	if( isset( $_GET['cert_id'] )
		&& strlen( $_GET['cert_id'] ) > 0
		&& is_numeric( $_GET['cert_id'] )
		&& $_GET['cert_id'] > 0
		&& isset( $_GET['warning_number_of_days'] )
		&& strlen( $_GET['warning_number_of_days'] ) > 0
		&& is_numeric( $_GET['warning_number_of_days'] )
		// && $_GET['warning_number_of_days'] >= 0
		&& $_GET['warning_number_of_days'] < 18251
		&& isset( $_GET['warning_last_user'] )
		) {

		try{
			$pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
		} catch(PDOException $e) {
			exit($e->getMessage());
		}
		$pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$cert_id = $_GET['cert_id'];
		$warning_number_of_days = $_GET['warning_number_of_days'];
		$warning_last_user = $_GET['warning_last_user'];
		$current_time = time();
		$warning_when_set = $current_time;
		$warning_when_modified = $current_time;

		$sth_mysql_count = $pdo_mysql->prepare('SELECT count(*) AS "mycount" FROM tcs.warning WHERE is_active = 1 AND cert_id = :cert_id AND warning_number_of_days = :warning_number_of_days;');
		$sth_mysql_count->bindParam(':cert_id', $cert_id, PDO::PARAM_INT);
		$sth_mysql_count->bindParam(':warning_number_of_days', $warning_number_of_days, PDO::PARAM_INT);
		$sth_mysql_count->execute();
		$count_mysql_warn = $sth_mysql_count->fetch(PDO::FETCH_ASSOC);
		if($count_mysql_warn['mycount'] < 1){
			$sth_mysql = $pdo_mysql->prepare('INSERT INTO tcs.warning (cert_id, warning_number_of_days, warning_when_set, warning_when_modified, warning_last_user) VALUES (:cert_id, :warning_number_of_days, :warning_when_set, :warning_when_modified, :warning_last_user);');
			$sth_mysql->bindParam(':cert_id', $cert_id, PDO::PARAM_INT);
			$sth_mysql->bindParam(':warning_number_of_days', $warning_number_of_days, PDO::PARAM_INT);
			$sth_mysql->bindParam(':warning_when_set', $warning_when_set, PDO::PARAM_INT);
			$sth_mysql->bindParam(':warning_when_modified', $warning_when_modified, PDO::PARAM_STR);
			$sth_mysql->bindParam(':warning_last_user', $warning_last_user, PDO::PARAM_STR);
			if($sth_mysql->execute()){
				$arr['success'] = true;
			} else {
				$arr['success'] = false;
				$arr['error'] = 'Database execute failed';
			}
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Another warning already exists with the same warning date.';
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
