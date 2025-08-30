<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xml/qual_catalog/index.php

	require_once('phpexcel/Classes/PHPExcel.php');

	$objReader = new PHPExcel_Reader_Excel2007();
	$objReader->setLoadSheetsOnly( array('Qualification_Mfg Catalog') );
	$objReader->setReadDataOnly(true);
	$objPHPExcel = $objReader->load('Qual Catelog_Master_052113.xlsx');
	$objPHPExcel->setActiveSheetIndexByName('Qualification_Mfg Catalog');

	$running_row = 4;
	$arr = array();

	while($running_row < 340) {

		$cell_value =

		if(strlen(trim($objPHPExcel->getActiveSheet()->getCell('H'.$running_row))) > 0) {

			$temp_arr = array();

			$temp_arr['short_name'] = trim($objPHPExcel->getActiveSheet()->getCell('H'.$running_row));





			$arr['items'][] = $temp_arr;
			unset($temp_arr);
		}






		$running_row++;
	}



	while(strlen(trim($objPHPExcel->getActiveSheet()->getCell('A'.$running_row))) > 0){ //Row must have an operation
		if(trim($objPHPExcel->getActiveSheet()->getCell('C'.$running_row)) == 1){
			$operation_override_is_override = true;
		} else {
			$operation_override_is_override = false;
		}
		$operation_overrride_wcrp_operation = trim($objPHPExcel->getActiveSheet()->getCell('A'.$running_row));
		$sth_mysql_overrride_operation_mycount = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM cap.operation_overrride WHERE operation_overrride_wcrp_operation = :operation_overrride_wcrp_operation;');
		$sth_mysql_overrride_operation_mycount->bindParam(':operation_overrride_wcrp_operation', $operation_overrride_wcrp_operation, PDO::PARAM_INT);
		$sth_mysql_overrride_operation_mycount->execute();
		$countrow = $sth_mysql_overrride_operation_mycount->fetch(PDO::FETCH_ASSOC);
		$mycount = $countrow['mycount'];

?>

