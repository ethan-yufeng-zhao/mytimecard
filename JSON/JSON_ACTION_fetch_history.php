<?php
	// JSON/JSON_ACTION_update_user.php?user_samaccountname=jcubic&user_firstname=Jason&user_lastname=Cubic&user_email=jason.cubic@jfab.aosmd.com&user_supervisor_id=137
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['cert_id']) && isset($_GET['ad_account'])) {
//        $cert_id = pg_escape_string($_GET['cert_id']);
//        $ad_account = pg_escape_string($_GET['ad_account']);
        $cert_id = $_GET['cert_id'] ?? null;
        $ad_account = $_GET['ad_account'] ?? null;

    	if ($cert_id === null || $ad_account === null) {
			echo json_encode(['success' => false, 'message' => 'Invalid input.']);
			exit;
		}

        $db_pdo = db_connect();
        $querystring = "SELECT * FROM tcs.proficiency_his WHERE cert_id = $cert_id and ad_account = '$ad_account' ORDER BY modified_time ASC";
        $db_arr = db_query($db_pdo, $querystring);

        $counter = 0;
        foreach ($db_arr as $key => $data ) {
            $counter++;
            $temp_arr = array();
            $arr['cert_id'] = (int)$data['cert_id'];
            $arr['ad_account'] = $data['ad_account'];
            $temp_arr['proficiency'] = floatval($data['proficiency']);
            $temp_arr['modified_user'] = $data['modified_user'];
            $temp_arr['modified_comments'] = $data['modified_comments'];
            $temp_arr['modified_time'] = $data['modified_time'];
            $arr['history'][$counter] = $temp_arr;
            unset($temp_arr);
        }
        $arr['success'] = true;
		// Close connection to DB
		$db_pdo = null;
	} else {
		$arr['success'] = false;
		$arr['error'] = 'invalid get values passed';
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));
