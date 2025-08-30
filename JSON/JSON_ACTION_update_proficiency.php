<?php
	// JSON/JSON_ACTION_update_user.php?user_samaccountname=jcubic&user_firstname=Jason&user_lastname=Cubic&user_email=jason.cubic@jfab.aosmd.com&user_supervisor_id=137
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$cert_id = $_POST['cert_id'] ?? null; // Row ID or unique identifier
        $ad_account = $_POST['ad_account'] ?? null;
		$newProficiency = $_POST['proficiency'] ?? null;
//        $user_cert_date = $_POST['user_cert_date'] ?? null;
        $modified_user = $_POST['modified_user'] ?? null;
        $modified_comments = $_POST['modified_comments'] ?? null;
		if ($cert_id === null || $newProficiency === null || $ad_account === null || $modified_user === null || $modified_comments === null) {
			echo json_encode(['success' => false, 'message' => 'Invalid input.']);
			exit;
		}
//        $modifiedComments = urldecode($modified_comments);
//        $modifiedComments = pg_escape_string($modified_comments);

		$updatestring = '';
		$db_pdo = db_connect();

        $updatestring = 'INSERT INTO tcs.proficiency_his (cert_id, ad_account, proficiency, modified_user, modified_comments) VALUES ( ';
        $updatestring .= "$cert_id, '$ad_account',  $newProficiency, '$modified_user', '$modified_comments' ) ";
//        $updatestring .= ":cert_id, :ad_account, :proficiency,  :modified_user, :modified_comments )";
//        $params[':cert_id'] = $cert_id;
//        $params[':ad_account'] = $ad_account;
//        $params[':proficiency'] = $newProficiency;
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
