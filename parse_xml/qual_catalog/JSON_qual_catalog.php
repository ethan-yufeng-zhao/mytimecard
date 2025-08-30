<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xml/qual_catalog/JSON_qual_catalog.php

	require_once('phpexcel/Classes/PHPExcel.php');

	$objReader = new PHPExcel_Reader_Excel2007();
	$objReader->setLoadSheetsOnly( array('Qualification_Mfg Catalog') );
	$objReader->setReadDataOnly(true);
	$objPHPExcel = $objReader->load('Qual Catelog_Master_052113.xlsx');
	$objPHPExcel->setActiveSheetIndexByName('Qualification_Mfg Catalog');

	$running_row = 4;
	$arr = array();
	$short_name_arr = array();
	$arr['duplicates'] = array();


	while($running_row < 340) {
		if(strlen(trim($objPHPExcel->getActiveSheet()->getCell('H'.$running_row))) > 0) {
			$temp_arr = array();
			$temp_arr['short_name'] = trim($objPHPExcel->getActiveSheet()->getCell('H'.$running_row));
			if(in_array($temp_arr['short_name'], $short_name_arr)) {
				$arr['duplicates'][] = $temp_arr['short_name'];
				$dup = true;
			} else {
				$short_name_arr[] = $temp_arr['short_name'];
				$dup = false;
			}
			$temp_arr['long_name'] = trim($objPHPExcel->getActiveSheet()->getCell('G'.$running_row));
			$temp_arr['validity'] = trim($objPHPExcel->getActiveSheet()->getCell('I'.$running_row));
			if(!$dup) {
				$arr['items'][] = $temp_arr;
			}
			unset($temp_arr);
		}
		$running_row++;
	}

	header('Content-Type: application/json');
	echo(json_encode($arr));



?>
