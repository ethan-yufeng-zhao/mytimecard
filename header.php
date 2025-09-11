<?php
    // header.php
    include_once('base.php');

    set_the_cookies();

    if (DEBUG) {
        $_SERVER["REMOTE_USER"] = "\\sstout"; // "\\aj.ty"; // "\\aaliyah.harrison"; // "\\dnickles";
//	if(!isset($_SERVER["REMOTE_USER"]) || $_SERVER["REMOTE_USER"] == '') {
//		header('HTTP/1.1 401 Unauthorized');
//		header('WWW-Authenticate: Negotiate');
//		header('WWW-Authenticate: NTLM', false);
//		exit();
//	}
//
        $REMOTE_USER = explode("\\", $_SERVER["REMOTE_USER"]);
        logit('$REMOTE_USER = '.$REMOTE_USER[1]);
    } else {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])){
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: NTLM');
            exit;
        }

        $auth = $headers['Authorization'];
        if (substr($auth,0,5) == 'NTLM ') {
            $msg = base64_decode(substr($auth, 5));
            if (substr($msg, 0, 8) != "NTLMSSP\x00") {
                die('error header not recognised');
            }

            if ($msg[8] == "\x01") {
                $msg2 = "NTLMSSP\x00\x02\x00\x00\x00".
                    "\x00\x00\x00\x00". // target name len/alloc
                    "\x00\x00\x00\x00". // target name offset
                    "\x01\x02\x81\x00". // flags
                    "\x00\x00\x00\x00\x00\x00\x00\x00". // challenge
                    "\x00\x00\x00\x00\x00\x00\x00\x00". // context
                    "\x00\x00\x00\x00\x00\x00\x00\x00"; // target info len/alloc/offset
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: NTLM ' . trim(base64_encode($msg2)));
                exit;

            } else if ($msg[8] == "\x03") {
                function get_msg_str($msg, $start, $unicode = true) {
                    $len = (ord($msg[$start+1]) * 256) + ord($msg[$start]);
                    $off = (ord($msg[$start+5]) * 256) + ord($msg[$start+4]);
                    if ($unicode) {
                        return str_replace("\0", '', substr($msg, $off, $len));
                    } else {
                        return substr($msg, $off, $len);
                    }
                }
                $username = strtolower(get_msg_str($msg, 36));
                $REMOTE_USER[1] = strtolower(get_msg_str($msg, 36));
                /* Kent
                print_r( $REMOTE_USER );
                $REMOTE_USER[1] = 'dkennedy';
                print_r( $REMOTE_USER );
                 */

                //  $domain = strtolower(get_msg_str($msg, 28));
                //  $workstation = strtolower(get_msg_str($msg, 44));
                //  $logger->debug("NTLM authenticated: username=$username, domain=$domain, workstation=$workstation");
            }
        }
    }

	if($REMOTE_USER[1] == 'jcubic' && isset($_GET['guilty_spark'])) {
		$REMOTE_USER = array('jfab', $_GET['guilty_spark']); // backdoor for testing only
	}

	// "Now remember, this is only a temporary fix - unless it works." - Red Green

	$current_time = time();
	$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]), false, getContextCookies()), true);
	if($user != null && count($user) < 1){
		unset($user);
		$json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($REMOTE_USER[1])), false, getContextCookies()), true);
		if($json_add['success'] != true) {
			echo('<p style ="background-color:red;">ERROR: Unable to add user '.$REMOTE_USER[1]);
			echo('<br>');
			echo($json_add['error']);
			echo('</p>');
		}
		unset($json_add);
		$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]), false, getContextCookies()), true);
	}

    // check if admin or supervisor
    //[{"user_group":"TCS Admin","config":{"systems": ["tcs"]},"users":["sstout", "ethan.zhao"],"update_time":"2023-10-09T16:39:42","update_user":"jiong.zhu","update_comment":null},
    //{"user_group":"TCS Supervisor","config":{"systems": ["tcs"]},"users":["sstout", "ethan.zhao"],"update_time":"2023-10-09T16:39:50","update_user":"jiong.zhu","update_comment":null}]
//    echo($REMOTE_USER[1]);
//    echo('https://hydrogen.jfab.aosmd.com/rptp/api/get_authorization.php?user='.$REMOTE_USER[1]);
//
//https://hydrogen.jfab.aosmd.com/rptp/api/execute_api.php?api_id=authorization
    $user_roles = json_decode(file_get_contents('https://hydrogen.jfab.aosmd.com/rptp/cache/authorization.json', false, getContextCookies()), true);
//    logit($user_roles);
//    $user_roles = json_decode(getHttpsContents('http://hydrogen.jfab.aosmd.com/rptp/api/get_authorization.php?user='.$REMOTE_USER[1]));
//    echo($user_roles);
    $user['user_is_admin'] = false;
    $user['user_is_supervisor'] = false;
    if ($user_roles != null && count($user_roles) > 0) {
        foreach( $user_roles as $uk => $uv) {
            if ($uk == 'TCS Admin') {
                if (in_array($REMOTE_USER[1], $uv)) {
                    $user['user_is_admin'] = true;
                }
            }
            if ($uk == 'TCS Supervisor') {
                if (in_array($REMOTE_USER[1], $uv)) {
                    $user['user_is_supervisor'] = true;
                }
            }
        }
    }

//	if($user['user_last_ldap_check']+(60*60*60*24) < ($current_time)) { // re-sync users with ldap every 24 hours when the individual user checks
//		$json_sync_user = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_ldap_sync_user.php?user_id='.$user['user_id']), false, getContextCookies()), true);
//		unset($json_sync_user);
//		unset($user);
//		$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]), false, getContextCookies()), true);
//	}
?>

<!DOCTYPE html>
<html>
<head>
	<title>My Timecard</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link href="css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap (this must be before ie8 concessions) -->
	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="js/html5shiv.js"></script>
		<script src="js/respond.min.js"></script>
	<![endif]-->
	<script src="js/jquery-1.10.2.min.js"></script> <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="js/bootstrap.min.js"></script>
	<!-- Include all compiled plugins (below), or include individual files as needed -->
	<link rel="stylesheet" href="css/theme.bootstrap.css"> <!-- tablesorter -->
	<script src="js/jquery.tablesorter.js"></script> <!-- tablesorter -->
	<script src="js/jquery.tablesorter.widgets.js"></script> <!-- tablesorter -->

    <link rel="icon" type="image/png" href="./favicon.png">

	<style type="text/css" media="print">
		a[href]:after {
			content:none;
		}
		.tablesorter-filter-row { display:none; }
		.container {
            margin: 0;
            padding: 0;
        }
	</style>

	<script type="text/javascript">
        // function callback_switchDB() {
        //     document.getElementById("id_db_type").innerHTML = tips;
        //     // $('#id_db_type').innerHTML = tips;
        // }

        // function switchDB(msg, callback) {
        function switchDB() {
            const jsVar = "<?php echo $GLOBALS['DB_TYPE']; ?>";
            let tips = '';
            if (jsVar == 'pgsql')
            {
                tips = 'mysql';
            } else {
                tips = 'pgsql';
            }
            const reply = confirm("Do you want to switch the DB to " + tips.toUpperCase() + " ? ");
            if (reply) {
                document.getElementById("id_db_type").innerHTML = tips.toUpperCase();
                document.cookie = 'DB_TYPE=' + tips;
            }
        }

        function certValidateDelete(){
            let confirmMsg;
            //alert($('#delete_cert').val());
            if ($('#delete_cert').val() < 1) {
                confirmMsg = "Disable this Certificate?";
            } else {
                confirmMsg = "Reactivate this Certificate?";
            }
            return confirm(confirmMsg);
        }

        $.tablesorter.addParser({
			// set a unique id
			id: 'ignore_labels',
			is: function(s) {
				// return false so this parser is not auto detected
				return false;
			},
			format: function(s) {
				return(s.split('||||')[0].toUpperCase());
			},
			// set type, either numeric or text
			type: 'text'
		});

		$(document).ready(function() {
			$.extend($.tablesorter.themes.bootstrap, {
				// these classes are added to the table. To see other table classes available,
				// look here: http://twitter.github.com/bootstrap/base-css.html#tables
				table      : 'table table-bordered table-striped',
                header     : 'bootstrap-header', // give the header a gradient background
				footerRow  : '',
				footerCells: '',
				icons      : '', // add "icon-white" to make them white; this icon class is added to the <i> in the header
				sortNone   : 'glyphicon glyphicon-sort',
				sortAsc    : 'glyphicon glyphicon-sort-by-attributes',
				sortDesc   : 'glyphicon glyphicon-sort-by-attributes-alt',
				// sortNone   : 'bootstrap-icon-unsorted',
				// sortAsc    : 'glyphicon glyphicon-chevron-up',
				// sortDesc   : 'glyphicon glyphicon-chevron-down',
				active     : '', // applied when column is sorted
				hover      : '', // use custom css here - bootstrap class may not override it
				filterRow  : '', // filter row class
				even       : '', // odd row zebra striping
				odd        : ''  // even row zebra striping
			});

			$(".tablesorter").tablesorter({
				// this will apply the bootstrap theme if "uitheme" widget is included
				// the widgetOptions.uitheme is no longer required to be set
				theme : "bootstrap",

				widthFixed: true,

				headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!

				// widget code contained in the jquery.tablesorter.widgets.js file
				// use the zebra stripe widget if you plan on hiding any rows (filter widget)
				widgets : [ "uitheme", "filter" ],

				widgetOptions : {
					// using the default zebra striping class name, so it actually isn't included in the theme variable above
					// this is ONLY needed for bootstrap theming if you are using the filter widget, because rows are hidden
					// zebra : ["even", "odd"],

					// reset filters button
					filter_reset : ".reset"

					// set the uitheme widget to use the bootstrap theme class names
					// this is no longer required, if theme is set
					// ,uitheme : "bootstrap"

				}
			});

			// filter button demo code
			$('button.filter').click(function(){
				var col = $(this).data('column'),
					txt = $(this).data('filter');
				$('table').find('.tablesorter-filter').val('').eq(col).val(txt);
				$('table').trigger('search', false);
				return false;
			});
		});

		function saveToExcel(){
			$('#dataToDisplay').val($('#jfabtable').html());
			return true;
		}
	</script>

	<link type="text/css" href="css/redmond/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"> <!-- used only for datepicker -->
	<script type="text/javascript" src="js/jquery-ui-1.9.2.custom.min.js"></script> <!-- used only for datepicker -->

    <link type="text/css" href="css/my.css" rel="stylesheet">
    <script type="text/javascript" src="js/my.js"></script>
</head>
<body>
<div class="container">
    <?php if (DEBUG): ?>
        <div class="watermark-text">FOR TESTING ONLY!</div>
    <?php endif; ?>
	<div class="navbar navbar-inverse">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand visible-xs visible-sm" href="<?php echo($mybaseurl); ?>/index.php" title="My Timecard">MTR</a>
            <a class="navbar-brand visible-md visible-lg" href="<?php echo($mybaseurl); ?>/index.php">My Timecard</a>
        </div>
        <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a href="<?php echo($mybaseurl); ?>/index.php"><?php echo($user['user_firstname'].' '.$user['user_lastname']); ?></a></li>
                <?php
                    if($user['user_is_admin']) {
                        echo('<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <b class="caret"></b></a>');
                        echo('<ul class="dropdown-menu">');
                        echo('<li><a href="'.$mybaseurl.'/all_users.php">All Users</a></li>');
                        // echo('<li><a href="'.$mybaseurl.'/all_users_templates.php">Users Templates Report</a></li>');
                        echo('<li class="divider"></li>');
                        echo('<li><a target="_blank" href="https://hydrogen.jfab.aosmd.com/rptp/public/authorization_center/index.html?system=tcs">Manage Admin</a></li>');
//                        echo('<li><a href="'.$mybaseurl.'/rebuild_template_users_by_department.php">Rebuild template users by Dept.</a></li>');
//                        echo('<li><a href="'.$mybaseurl.'/rebuild_template_certs.php">Rebuild template certs</a></li>');
                        echo('</ul>');
                        echo('</li>');
                    }

                    if($user['user_is_admin'] || $user['user_is_supervisor']) {
                        echo('<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Supervisor <b class="caret"></b></a>');
                        echo('<ul class="dropdown-menu">');
                        echo('<li><a href="'.$mybaseurl.'/team_users.php">Team Users</a></li>');
//                            echo('<li><a href="'.$mybaseurl.'/sync_users_with_ldap.php">Re-sync user list with LDAP</a></li>');
//                            echo('<li class="divider"></li>');
//                            echo('<li><a target="_blank" href="https://hydrogen.jfab.aosmd.com/rptp/public/authorization_center/index.html?system=tcs">Manage Supervisor</a></li>');
                        echo('</ul>');
                        echo('</li>');
                    }
                ?>
                <li><a target="_blank" href="https://jireh.smarteru.com/remote-login/login.cfm">SmarterU</a></li>
                <li><a target="_blank" href="http://www.jfab.aosmd.com">HilWiki</a></li>
<!--                    <li>--><?php //echo(time()); ?><!--</li>-->
<!--                    <li><a id="id_db_type" href="" onclick="switchDB()">--><?php //echo(strtoupper($GLOBALS['DB_TYPE'])); ?><!--</a></li>-->
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>
<div class="container" style="margin-top:5px; margin-bottom:5px;">
    <form method="get" class="form-inline" role="form" style="display:flex; align-items:center; flex-wrap:nowrap; gap:10px;">

        <!-- Mode Selector -->
        <label for="mode" class="mb-0">Mode:</label>
        <select name="mode" id="mode" class="form-control input-sm">
            <option value="strict"   <?php echo ($_GET['mode'] ?? '') === 'strict' ? 'selected' : ''; ?>>Strict</option>
            <option value="balanced" <?php echo ($_GET['mode'] ?? 'balanced') === 'balanced' ? 'selected' : ''; ?>>Balanced</option>
            <option value="generous" <?php echo ($_GET['mode'] ?? '') === 'generous' ? 'selected' : ''; ?>>Generous</option>
        </select>

        <!-- Start Date -->
        <label for="start" class="mb-0">Start:</label>
        <input type="date" name="start" id="start" class="form-control input-sm"
               value="<?php echo htmlspecialchars($_GET['start'] ?? date('Y-m-01')); ?>">

        <!-- End Date -->
        <label for="end" class="mb-0">End:</label>
        <input type="date" name="end" id="end" class="form-control input-sm"
               value="<?php echo htmlspecialchars($_GET['end'] ?? date('Y-m-d')); ?>">

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </form>
</div>

<div class="container">
    <hr style="border:0; height:1px; background: lightgrey; margin-top:5px; margin-bottom:10px;">
</div>

<div class="container">
