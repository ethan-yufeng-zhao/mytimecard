<?php
	// JSON/JSON_all_users_department.php
	require_once('..'.DIRECTORY_SEPARATOR.'base.php');
	require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

	$arr = array();
    $out = array();

	$querystring='';
	$db_pdo = db_connect();

	// select * from tcs.user where departmentnumber is not null order by departmentnumber asc, ad_account asc;
	$querystring='SELECT * FROM tcs."user" WHERE departmentnumber is not null order by departmentnumber asc, ad_account asc;';
	$db_arr = db_query($db_pdo, $querystring);

	$arr[1] = array();
	foreach ($db_arr as $key => $data ) {
		$temp_arr = array();

		$temp_arr['ad_account'] = $data['ad_account'];
		$temp_arr['departmentnumber'] = (int)$data['departmentnumber'];

		$arr[1][] = $temp_arr['ad_account'];
		if (array_key_exists($temp_arr['departmentnumber'], $arr)) {
			$arr[$temp_arr['departmentnumber']][] = $temp_arr['ad_account'];
		} else {
			$arr[$temp_arr['departmentnumber']] = [$temp_arr['ad_account']];
		}
		unset($temp_arr);
	}

    //-- Auto-generated SQL script #202402051418
	// UPDATE tcs."template"
	//	SET users='["ethan.zhao", "dsimon"]'::jsonb
	//	WHERE template_id=51;
    foreach ($arr as $department => $users) {
		$updatestring = 'update tcs.template set users=';
        $updatestring .= "'".json_encode($users)."'::jsonb";
        if ($department == 1) {
            $updatestring .= ' where template_id=1';
        } else {
            $updatestring .= ' where template_department_number='.$department;
        }
		$db_arr = db_update($db_pdo, $updatestring);
        if ($db_arr) {
            $out[$department] = count($users);
        } else {
            $out[$department] = -1;
        }
	}


	// Close connection to DB
	$db_pdo = null;

	header('Content-Type: application/json');
	echo(json_encode($out));
