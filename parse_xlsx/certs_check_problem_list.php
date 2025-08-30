<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xlsx/certs_check_problem_list.php

	$json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parser.php')), true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certs parse mismatch report</title>
    <link rel="stylesheet" href="css/style.css">
    <!--[if IE
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body id="home">

<?php
	echo('<h2>Na]>me Mismatches</h2>');
	echo('<div style="margin-left:4em;">');
	foreach($json['certs_check_problem_list']['name'] as $value){
		echo('<li>');
		echo($value);
		echo('<ul>');
		foreach($json['certs_check'][$value]['name'] as $namevalue){
			echo('<li>');
			echo($namevalue);
			echo('</li>');
		}
		echo('</ul>');
		echo('</li>');
	}
	echo('</div>');


	echo('<h2>Days Mismatches</h2>');
	echo('<div style="margin-left:4em;">');
	foreach($json['certs_check_problem_list']['days'] as $value){
		echo('<li>');
		echo($value);
		echo('<ul>');
		foreach($json['certs_check'][$value]['days'] as $namevalue){
			echo('<li>');
			echo($namevalue);
			echo('</li>');
		}
		echo('</ul>');
		echo('</li>');
	}
	echo('</div>');


	echo('<h2>Expire Mismatches</h2>');
	echo('<div style="margin-left:4em;">');
	foreach($json['certs_check_problem_list']['expire'] as $value){
		echo('<li>');
		echo($value);
		echo('<ul>');
		foreach($json['certs_check'][$value]['expire'] as $namevalue){
			echo('<li>');
			echo($namevalue);
			echo('</li>');
		}
		echo('</ul>');
		echo('</li>');
	}
	echo('</div>');


	echo('<h2>ERT Mismatches</h2>');
	echo('<div style="margin-left:4em;">');
	foreach($json['certs_check_problem_list']['ert'] as $value){
		echo('<li>');
		echo($value);
		echo('<ul>');
		foreach($json['certs_check'][$value]['ert'] as $namevalue){
			echo('<li>');
			echo($namevalue);
			echo('</li>');
		}
		echo('</ul>');
		echo('</li>');
	}
	echo('</div>');


	echo('<h2>Safety Mismatches</h2>');
	echo('<div style="margin-left:4em;">');
	foreach($json['certs_check_problem_list']['safety'] as $value){
		echo('<li>');
		echo($value);
		echo('<ul>');
		foreach($json['certs_check'][$value]['safety'] as $namevalue){
			echo('<li>');
			echo($namevalue);
			echo('</li>');
		}
		echo('</ul>');
		echo('</li>');
	}
	echo('</div>');


?>



</body>
</html>