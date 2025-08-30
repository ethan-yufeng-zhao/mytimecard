<?php
	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	// Admin/all_certs.php



	// if(!isset($_SERVER["REMOTE_USER"]) || $_SERVER["REMOTE_USER"] == ''){
	// 	header('HTTP/1.1 401 Unauthorized');
	// 	header('WWW-Authenticate: Negotiate');
	// 	header('WWW-Authenticate: NTLM', false);
	// 	exit;
	// }
	// $REMOTE_USER = explode("\\", $_SERVER["REMOTE_USER"]);

	// $admin_user_arr = array('jcubic', 'dkennedy');

	// if(!in_array($REMOTE_USER[1], $admin_user_arr)){
	// 	exit('You must be an admin user to view this page.');
	// }

	// $json_analytics = json_decode(file_get_contents(request_json_api('/JSON/JSON_jfab_analytics.php?server_name='.urlencode($_SERVER["SERVER_NAME"]).'&request_uri='.urlencode($_SERVER["REQUEST_URI"]).'&remote_addr='.urlencode($_SERVER["REMOTE_ADDR"]).'&remote_user='.urlencode($_SERVER["REMOTE_USER"]).'&http_user_agent='.urlencode($_SERVER["HTTP_USER_AGENT"])));

	$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php'));



?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="css/blueprint/screen.css" type="text/css" media="screen, projection">
	<!--[if lt IE 9]>
		<link rel="stylesheet" href="css/blueprint/ie.css" type="text/css" media="screen, projection">
	<![endif]-->
	<link rel="stylesheet" href="css/tablesorter/style.css" type="text/css" media="screen, projection"/>
	<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.22.custom.css" rel="stylesheet">
	<link rel="stylesheet" href="css/style.css" type="text/css" media="all">
	<!-- <link rel="stylesheet" href="css/colorbox.css" type="text/css" media="all" /> -->
	<title>Training Report</title>
	<link type="text/css" href="css/smoothness/jquery-ui-1.8.21.custom.css" rel="stylesheet">
	<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.20.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
	<!--

	<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>

	-->




	<script type="text/javascript">
		$(document).ready(function() {
			$("body").removeClass("noscript"); // No javascript = No Page
		});
	</script>
</head>
<body class="noscript">
	<div class="container">
		<div class="span-5 prepend-1">
			<p id="logo"><a href="http://www.jfab.aosmd.com/" title="Jireh Semiconductor"><img alt="JFab" src="img/Jireh_Blue_Logo_small.jpg"><span></span></a></p>
		</div>
		<div class="span-17 append-1 last">
			<ul id="headerlinks">
				<li><a href="https://webmail.jfab.aosmd.com/owa" title="webmail" target="_blank" >Email</a></li>
				<li class="headerdivider">|</li>
				<li><a href="http://hilreports.jfab.aosmd.com" title="Hilreports" target="_blank" >Reports</a></li>
				<li class="headerdivider">|</li>
				<li><a href="http://www.jfab.aosmd.com" title="Go to JFab homepage" target="_blank" >Home</a></li>
			</ul>
		</div>
	</div>

	<div class="container" style='padding-top:1em;'>
		<div class="span-24 last">
			<table>


			</table>
		</div>

		<div class="span-22 append-1 prepend-1 last">


			<p>
			The standard Lorem Ipsum passage, used since the 1500s
			</p>

			<p>
			"Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."
			Section 1.10.32 of "de Finibus Bonorum et Malorum", written by Cicero in 45 BC
			</p>

			<p>
			"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?"
			1914 translation by H. Rackham
			</p>

			<p>
			"But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?"
			Section 1.10.33 of "de Finibus Bonorum et Malorum", written by Cicero in 45 BC
			</p>

			<p>
			"At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat."
			1914 translation by H. Rackham
			</p>

			<p>
			"On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire, that they cannot foresee the pain and trouble that are bound to ensue; and equal blame belongs to those who fail in their duty through weakness of will, which is the same as saying through shrinking from toil and pain. These cases are perfectly simple and easy to distinguish. In a free hour, when our power of choice is untrammelled and when nothing prevents our being able to do what we like best, every pleasure is to be welcomed and every pain avoided. But in certain circumstances and owing to the claims of duty or the obligations of business it will frequently occur that pleasures have to be repudiated and annoyances accepted. The wise man therefore always holds in these matters to this principle of selection: he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains."
			</p>


		</div>

	</div>
	<p>&nbsp;</p>
</body>
</html>
