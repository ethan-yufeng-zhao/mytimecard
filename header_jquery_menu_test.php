<?php

	// header.php

	if(!isset($_SERVER["REMOTE_USER"]) || $_SERVER["REMOTE_USER"] == ''){
		header('HTTP/1.1 401 Unauthorized');
		header('WWW-Authenticate: Negotiate');
		header('WWW-Authenticate: NTLM', false);
		exit();
	}
	$REMOTE_USER = explode("\\", $_SERVER["REMOTE_USER"]);
	// $REMOTE_USER = array('jfab', 'terence.huang');
	// $REMOTE_USER = array('jfab', 'bmcgucki');
	$REMOTE_USER = array('jfab', 'jhogg');


	$current_time = time();


	$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]) , false, getContextCookies()), true);
	if(count($user) < 1){
		unset($user);
		$json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($REMOTE_USER[1])) , false, getContextCookies()), true);
		if($json_add['success'] != true) {
			echo('<p style ="background-color:red;">ERROR: Unable to add user '.$REMOTE_USER[1]);
			echo('<br>');
			echo($json_add['error']);
			echo('</p>');
		}
		unset($json_add);
		$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]) , false, getContextCookies()), true);
	}

	if($user['user_last_ldap_check']+(60*60*60*24) < ($current_time)) { // re-sync users with ldap every 24 hours when the individual user checks
		$json_sync_user = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_ldap_sync_user.php?user_id='.$user['user_id']) , false, getContextCookies()), true);
		unset($json_sync_user);
		unset($user);
		$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]) , false, getContextCookies()), true);
	}


	$myurl = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80"){
		$myurl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["SCRIPT_NAME"];
	} else {
		$myurl .= $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
	}
	$mybaseurl = dirname($myurl);
?>
<!DOCTYPE html>
<html>
<head>
	<title>Training Certification System</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="css/blueprint/screen.css" type="text/css" media="screen, projection">
	<!--[if lt IE 9]>
		<link rel="stylesheet" href="css/blueprint/ie.css" type="text/css" media="screen, projection">
	<![endif]-->
	<!--[if lte IE 7]>
        <link rel="stylesheet" type="text/css" href="css/ie.css" media="screen" />
    <![endif]-->

	<link rel="stylesheet" href="css/tablesorter/style.css" type="text/css" media="screen, projection"/>

	<link rel="stylesheet" href="css/colorbox.css" type="text/css" media="all" />

	<!-- <link type="text/css" href="css/ui-lightness/jquery-ui-1.8.22.custom.css" rel="stylesheet"> -->

	<link type="text/css" href="css/smoothness/jquery-ui-1.9.2.custom.min.css" rel="stylesheet">

	<link rel="stylesheet" href="css/style.css" type="text/css" media="screen, projection"/>
	<!-- <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.22.custom.min.js"></script> -->

	<script type="text/javascript" src="js/jquery-1.8.3.js"></script>



	<script type="text/javascript" src="js/jquery-ui-1.9.2.custom.min.js"></script>


	<script type="text/javascript" src="js/jquery.colorbox.js"></script>
	<script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>


	<script type="text/javascript">
		$(document).ready(function() {
			// $("ul.dropdown li").hover(function(){
			// 	$(this).addClass("hover");
			// 	$('ul:first',this).css('visibility', 'visible');
			// }, function(){
			// 	$(this).removeClass("hover");
			// 	$('ul:first',this).css('visibility', 'hidden');
			// });
			// $("ul.dropdown li ul li:has(ul)").find("a:first").append(" &raquo; ");
			$(".sortme").tablesorter();

			$(".jfab_menubar_button").button().click(function(event) {
				event.preventDefault();
			});


			$( "#nav" ).menu({position: {at: "left bottom"}});

		});
		function saveToExcel(){
			$('#dataToDisplay').val($('#jfabtable').html());
			return true;
		}
	</script>

<style>
.ui-menu {
    overflow: hidden;
    padding: 0px;
    float:right;
    margin:0px;
}


.ui-menu > li {
	float: left;
	display: block;
	width: auto !important;
}




.ui-menu ul li {
    display:block;
    float:none;
}




</style>

</head>
<body>
	<div class="container" style='background: url(./img/bg-html.jpg) no-repeat; margin-top:1em;'>
		<div class="span-9">
			<h2 style='margin:0px; padding:0px; color:white;'><strong>Training Certification System</strong></h2>
		</div>
		<div class="span-15 last">
			<ul id="nav" class="ui-menu">
				<li><a href="<?php echo($mybaseurl); ?>/index.php"><?php echo($user['user_samaccountname']); ?></a></li>

				<!-- <li><a href="<?php echo($mybaseurl); ?>/index.php"><?php echo($user['user_firstname'].' '.$user['user_lastname']); ?></a></li> -->


				<?php
					if($user['user_is_admin']){
						echo('<li><a href="'.$mybaseurl.'/all_users.php">Admin</a>');  // TODO: Should we have a link for this?
						// echo('<ul class="sub_menu">');
						echo('<ul>');
						echo('<li><a href="'.$mybaseurl.'/all_users.php">Users</a></li>');

						// echo('<ul class="sub_menu">');
						// echo('<li><a href="'.$mybaseurl.'/cert.php?edit=1">Add Certification</a>');
						// echo('</ul>');


						echo('</li>');  // Can click on one cert to get details and add, edit or delete the cert
							// on individual cert can add, or delete a certs notification days
							// lists team members
						echo('<li><a href="'.$mybaseurl.'/all_users_certs.php">Users Certifications</a></li>'); // Can click on one user_cert to get details and edit, or delete the user_cert
							// can add a cert, delete a cert, add a template, delete a template, make an admin
						echo('<li><a href="'.$mybaseurl.'/all_templates.php">Templates</a></li>'); // can add, edit, or delete templates
						echo('<li><a href="'.$mybaseurl.'/all_users_templates.php">Users Templates</a></li>'); // can add or delete templates from users
							// can link or unlink a cert to the template
						//echo('<li><a href="'.$mybaseurl.'/sync_users_with_ldap.php">Re-sync user list with LDAP</a></li>');
						echo('</ul>');
						echo('</li>');
					}
					if($user['user_is_admin'] || $user['user_is_supervisor']){
						echo('<li><a href="'.$mybaseurl.'/team_users_certs.php">Supervisor</a>');
						// echo('<ul class="sub_menu">');
						echo('<ul>');
						echo('<li><a href="'.$mybaseurl.'/team_users_certs.php">Team Certifications</a></li>'); // can add a cert to a user
						// echo('<li><a href="#">Extended Team Certs</a></li>'); // can add a cert to a user
						echo('<li><a href="'.$mybaseurl.'/team_users_templates.php">Team Templates</a></li>'); // can add a template to a user
						//echo('<li><a href="'.$mybaseurl.'/sync_users_with_ldap.php">Re-sync user list with LDAP</a></li>');

						echo('</ul>');
						echo('</li>');
					}
				?>
				<li><a href="http://hillearning1.jfab.aosmd.com">Moodle</a></li>
				<li><a href="http://www.jfab.aosmd.com">JFab</a></li>
			</ul>
		</div>
	</div>
	<div class="container" style="background-color:white;">