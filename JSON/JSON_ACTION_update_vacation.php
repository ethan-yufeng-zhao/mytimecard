<?php
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$day_of_month = $_POST['day_of_month'] ?? null; // Row ID or unique identifier
        $ad_account = $_POST['ad_account'] ?? null;
		$newvacation = $_POST['vacation'] ?? null;
//        $user_cert_date = $_POST['user_cert_date'] ?? null;
        $modified_user = $_POST['modified_user'] ?? null;
        $modified_comments = $_POST['modified_comments'] ?? null;
		if ($day_of_month === null || $newvacation === null || $ad_account === null || $modified_user === null) {
			echo json_encode(['success' => false, 'message' => 'Invalid input.']);
			exit;
		}
        $modifiedComments = urldecode($modified_comments);
        $modifiedComments = pg_escape_string($modified_comments);

		$updatestring = '';
		$db_pdo = db_connect();

        $updatestring = 'INSERT INTO hr.vacation (day_of_month, ad_account, vacation, modified_user, modified_comments) VALUES ( ';
        $updatestring .= "$day_of_month, '$ad_account',  $newvacation, '$modified_user', '$modified_comments' ) ";
//        $updatestring .= ":day_of_month, :ad_account, :vacation,  :modified_user, :modified_comments )";
//        $params[':day_of_month'] = $day_of_month;
//        $params[':ad_account'] = $ad_account;
//        $params[':vacation'] = $newvacation;
//        $params[':modified_user'] = $modified_user;
//        $params[':modified_comments'] = $modified_comments;

		if(db_update($db_pdo, $updatestring)){
			$arr['success'] = true;
		} else {
			$arr['success'] = false;
			$arr['error'] = 'Failed on database execute';
		}
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid get values passed';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));
