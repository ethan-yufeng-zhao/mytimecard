<?php
	// JSON/JSON_all_certs.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    // body
	$arr = array();

    $querystring='';
    $db_pdo = db_connect();

	//$querystring='SELECT cert_id, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.cert WHERE is_active = 1;';
    if ($GLOBALS['DB_TYPE'] == 'pgsql'){
        //$querystring='SELECT * FROM tcs.cert t WHERE t.is_active is TRUE order by t.cert_id;';
        $querystring='SELECT * FROM tcs.cert t order by t.cert_id;';
    } else { // mysql
        //$querystring='SELECT * FROM tcs.cert WHERE is_active = 1;';
        $querystring='SELECT * FROM tcs.cert;';
    }
    $db_arr = db_query($db_pdo, $querystring);

    foreach ($db_arr as $key => $data ) {
		$temp_arr = array();
		$temp_arr['cert_id'] = (int)$data['cert_id'];
		$temp_arr['cert_name'] = $data['cert_name'];
		$temp_arr['cert_description'] = $data['cert_description'];
		$temp_arr['cert_days_active'] = (int)$data['cert_days_active'];
		$temp_arr['cert_notes'] = $data['cert_notes'];
		$temp_arr['cert_never_expires'] = (int)$data['cert_never_expires'];
		$temp_arr['cert_is_ert'] = (int)$data['cert_is_ert'];
		$temp_arr['cert_is_iso'] = (int)$data['cert_is_iso'];
		$temp_arr['cert_is_safety'] = (int)$data['cert_is_safety'];
        $temp_arr['cert_is_active'] = (int)$data['is_active'];
        if ($GLOBALS['DB_TYPE'] == 'pgsql'){
            $temp_arr['cert_when_set'] = strtotime($data['cert_when_set']);
            $temp_arr['cert_when_modified'] = strtotime($data['cert_when_modified']);
        } else{ // mysql
            $temp_arr['cert_when_set'] = (int)$data['cert_when_set'];
            $temp_arr['cert_when_modified'] = (int)$data['cert_when_modified'];
        }
		$temp_arr['cert_last_user'] = $data['cert_last_user'];
		$temp_arr['warning'] = array();
        $temp_arr['tool'] = array();
        $cert_id = intval($data['cert_id']);
        if ($GLOBALS['DB_TYPE'] == 'pgsql'){
            $temp_arr['tool'] = json_decode($data['entities'] ?? '{}', true);
            $temp_arr['cert_points'] = json_decode($data['cert_points'] ?? '{}', true);
        } else {
             //get warning
            $querystring = 'SELECT warning_id, cert_id, warning_number_of_days, warning_when_set, warning_when_modified, warning_last_user FROM tcs.warning WHERE is_active = 1 AND cert_id = '.$cert_id.';';
            $warning_arr = db_query($db_pdo, $querystring);
            foreach ($warning_arr as $key => $warning_data ) {
                $dummy = array();
                $dummy['warning_id'] = (int)$warning_data['warning_id'];
                $dummy['warning_number_of_days'] = (int)$warning_data['warning_number_of_days'];
                $dummy['warning_when_set'] = (int)$warning_data['warning_when_set'];
                $dummy['warning_when_modified'] = (int)$warning_data['warning_when_modified'];
                $dummy['warning_last_user'] = $warning_data['warning_last_user'];
                $temp_arr['warning'][] = $dummy;
                unset($dummy);
            }
        }
 		$arr[$cert_id] = $temp_arr;
		unset($temp_arr);
	}
    // Close connection to DB
    $db_pdo = null;

    // API RETURN
	header('Content-Type: application/json');
    $jsonarr = json_encode($arr);
    //logit($jsonarr); //xdebug
	echo($jsonarr);
	
