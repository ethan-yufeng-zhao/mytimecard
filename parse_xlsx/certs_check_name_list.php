<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// parse_xlsx/certs_check_name_list.php

	$json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parser.php')), true);








?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certs parse mismatch report</title>
    <link rel="stylesheet" href="css/style.css">
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body id="home">
<h2>Cert Name List</h2>
<?php
	echo('<div style="margin-left:4em;">');
	foreach($json['certs'] as $key => $value){
		echo('<li>');
		echo($key);

		echo('</li>');
	}
	echo('</div>');
?>

</body>
</html>